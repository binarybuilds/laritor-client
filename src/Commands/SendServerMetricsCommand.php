<?php

namespace Laritor\LaravelClient\Commands;

use Illuminate\Console\Command;
use Laritor\LaravelClient\Laritor;

class SendServerMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laritor:send-metrics';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send server CPU, Memory and Disk usage stats to laritor';

    /**
     * Execute the console command.
     */
    public function handle(Laritor $laritor)
    {
        $memory = $this->getMemoryTotal();

        $data = [
            'cpu' => $this->getCpuUsage(),
            'memory' => [
                'total' => $memory,
                'used' => $this->getMemoryUsed($memory)
            ],
            'disk' => $this->getDiskUsage()
        ];

        $laritor->addEvents('server_stats', $data);
    }

    public function getDiskUsage($path = '/')
    {
        $diskUsage = [
            'total' => 0,
            'free' => 0,
            'used' => 0
        ];

        $diskTotalSpace = disk_total_space($path);
        $diskFreeSpace = disk_free_space($path);

        if ($diskTotalSpace === false || $diskFreeSpace === false) {
            return $diskUsage;
        }

        $diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
        $diskUsage['total'] = round($diskTotalSpace / 1024 / 1024 / 1024);
        $diskUsage['free'] = round($diskFreeSpace / 1024 / 1024 / 1024);
        $diskUsage['used'] = round($diskUsedSpace / 1024 / 1024 / 1024);

        return $diskUsage;
    }

    /**
     * @return int
     */
    public function getMemoryTotal()
    {
        switch (PHP_OS_FAMILY) {
            case 'Darwin':
                $output = shell_exec('sysctl hw.memsize | grep -Eo \'[0-9]+\'');
                $memoryTotal = intval($output / 1024 / 1024);
                break;
            case 'Linux':
                $output = shell_exec('cat /proc/meminfo | grep MemTotal | grep -E -o \'[0-9]+\'');
                $memoryTotal = intval($output / 1024);
                break;
            case 'Windows':
                $output = shell_exec('wmic ComputerSystem get TotalPhysicalMemory | more +1');
                $memoryTotal = intval(((int) trim($output)) / 1024 / 1024);
                break;
            case 'BSD':
                $output = shell_exec('sysctl hw.physmem | grep -Eo \'[0-9]+\'');
                $memoryTotal = intval($output / 1024 / 1024);
                break;
            default:
                $memoryTotal = 0;
        }

        return $memoryTotal;
    }

    /**
     * @param $memoryTotal
     * @return int
     */
    public function getMemoryUsed($memoryTotal)
    {
        switch (PHP_OS_FAMILY) {
            case 'Darwin':
                $freePages = shell_exec('vm_stat | grep \'Pages free\' | grep -Eo \'[0-9]+\'');
                $pageSize = shell_exec('pagesize');
                $memoryUsed = $memoryTotal - intval(intval($freePages) * intval($pageSize) / 1024 / 1024);
                break;
            case 'Linux':
                $availableMemory = shell_exec('cat /proc/meminfo | grep MemAvailable | grep -E -o \'[0-9]+\'');
                $memoryUsed = $memoryTotal - intval($availableMemory / 1024);
                break;
            case 'Windows':
                $freePhysicalMemory = shell_exec('wmic OS get FreePhysicalMemory | more +1');
                $memoryUsed = $memoryTotal - intval(((int) trim($freePhysicalMemory)) / 1024);
                break;
            case 'BSD':
                $cacheCount = shell_exec('sysctl vm.stats.vm.v_cache_count | grep -Eo \'[0-9]+\'');
                $inactiveCount = shell_exec('sysctl vm.stats.vm.v_inactive_count | grep -Eo \'[0-9]+\'');
                $activeCount = shell_exec('sysctl vm.stats.vm.v_active_count | grep -Eo \'[0-9]+\'');
                $pageSize = shell_exec('pagesize');

                $aggregateCommand = "( echo '$cacheCount' ; echo '$inactiveCount' ; echo '$activeCount' ) | awk '{s+=\$1} END {print s}'";
                $aggregateOutput = shell_exec($aggregateCommand);

                $memoryUsed = intval(intval($aggregateOutput) * intval($pageSize) / 1024 / 1024);
                break;
            default:
                $memoryUsed = 0;
        }

        return $memoryUsed;
    }

    /**
     * @return int
     */
    public function getCpuUsage()
    {
        switch (PHP_OS_FAMILY) {
            case 'Darwin':
                $usage = shell_exec('top -l 1 | grep -E "^CPU" | tail -1 | awk \'{ print $3 + $5 }\''); break;
            case 'Linux':
                $usage = shell_exec('top -bn1 | grep -E \'^(%Cpu|CPU)\' | awk \'{ print $2 + $4 }\''); break;
            case 'Windows':
                $usage = shell_exec('wmic cpu get loadpercentage | more +1'); break;
            case 'BSD':
                $usage = shell_exec('top -b -d 2| grep \'CPU: \' | tail -1 | awk \'{print$10}\' | grep -Eo \'[0-9]+\.[0-9]+\' | awk \'{ print 100 - $1 }\''); break;
            default: $usage = 0;
        }

        return (int)$usage;
    }
}
