<?php

namespace BinaryBuilds\LaritorClient;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;

class CommandOutput
{
    private $lines = [];

    public function addLine($line, $type)
    {
        $this->lines[] = ['type' => $type, 'timestamp' => now()->toDateTimeString(), 'text' => DataHelper::redactData($line)];
    }

    public function addTable($headers, $rows)
    {
        $rows = array_map(function ($row) {
            return DataHelper::redactData(is_array($row) ? json_encode($row) : $row);
        }, array_values($rows));

        $this->lines[] = [
            'type' => 'table',
            'timestamp' => now()->toDateTimeString(),
            'headers' => array_values($headers),
            'rows' => $rows
        ];
    }

    public function resetLines()
    {
        $this->lines = [];
    }

    public function getLines()
    {
        return $this->lines;
    }
}
