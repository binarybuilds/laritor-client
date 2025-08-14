<?php

namespace BinaryBuilds\LaritorClient;

use Illuminate\Support\Str;

trait SendOutputToLaritor
{
    private $lastLine = [];

    private function addLine($line, $type)
    {
        app(CommandOutput::class)->addLine($line, $type);
        $this->lastLine = ['type' => $type, 'text' => $line];
    }

    private function resetLines()
    {
        app(CommandOutput::class)->resetLines();
    }

    public function table($headers, $rows, $tableStyle = 'default', array $columnStyles = [])
    {
        app(CommandOutput::class)->addTable(array_values($headers), array_values($rows));
        $this->lastLine = ['type' => 'table', 'text' => 'table'];

        parent::table($headers, $rows, $tableStyle, $columnStyles);
    }

    public function info($string, $verbosity = null)
    {
        $this->addLine($string, 'info');
        parent::info($string, $verbosity);
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @param  string|null  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        if(
            ! (Str::startsWith($string, '*') &&  Str::endsWith($string, '*')) &&
            (!isset($this->lastLine['text']) || $this->lastLine['text'] !== $string)
        ) {
            $this->addLine($string, 'plain');
        }

        parent::line($string, $style, $verbosity);
    }

    /**
     * Write a string as comment output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function comment($string, $verbosity = null)
    {
        if(!(Str::startsWith($string, '*') &&  Str::endsWith($string, '*')) && $string ) {
            $this->addLine($string, 'comment');
        }

       parent::comment($string, $verbosity);
    }

    /**
     * Write a string as question output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function question($string, $verbosity = null)
    {
        $this->addLine($string, 'question');
        parent::question($string, $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->addLine($string, 'error');
        parent::error($string, $verbosity);
    }

    /**
     * Write a string as warning output.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        $this->addLine($string, 'warning');
        parent::warn($string, $verbosity);
    }

    /**
     * Write a string in an alert box.
     *
     * @param  string  $string
     * @param  int|string|null  $verbosity
     * @return void
     * @phpstan-ignore arguments.count  */
    public function alert($string, $verbosity = null)
    {
        $this->addLine($string, 'alert');
        parent::alert($string, $verbosity);
    }

    /**
     * Write a blank line.
     *
     * @param  int  $count
     * @return $this
     * @phpstan-ignore return.type  */
    public function newLine($count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addLine('', 'plain');
        }

        /** @phpstan-ignore staticMethod.void */
        return parent::newLine($count);
    }
}
