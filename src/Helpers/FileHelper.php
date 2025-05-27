<?php

namespace BinaryBuilds\LaritorClient\Helpers;

use Illuminate\Support\Str;
use RuntimeException;

class FileHelper
{

    /**
     * @var \SplFileObject
     */
    private $file;

    /** @var int */
    private $offset = 1;

    /** @var int */
    private $before = 1000;

    /** @var int */
    private $after = 1000;

    /**
     * @param $offset
     * @return $this
     */
    public function offset( $offset )
    {
        $this->offset = $offset;

        return $this;
    }

    public static function parseFileName($file)
    {
        if (Str::contains($file, 'laravel-serializable-closure')) {
            return 'closure';
        }

        return Str::replaceFirst(base_path().'/', '', $file);
    }

    /**
     * @param $before
     * @return $this
     */
    public function before( $before )
    {
        $this->before = $before;
        return $this;
    }

    /**
     * @param $after
     * @return $this
     */
    public function after( $after )
    {
        $this->after = $after;
        return $this;
    }

    /**
     * @param $fileName
     * @return self
     */
    public static function createFromPath($fileName)
    {
        $helper = new self();
        if (file_exists($fileName)) {
            $helper->file = new \SplFileObject($fileName);
        }

        return $helper;
    }

    public function getLastLineNumber()
    {
        $this->file->seek(PHP_INT_MAX);

        return $this->file->key() + 1;
    }

    public function getContents()
    {
        try {

            $startLineNumber = $this->offset - $this->before;
            if ($startLineNumber <= 0) {
                $startLineNumber = 1;
            }

            $endLineNumber = $this->offset + $this->after;
            $totalLines = $this->getLastLineNumber();
            if ($totalLines <= $endLineNumber) {
                $endLineNumber = $totalLines;
            }

            $code = [];

            $currentLineNumber = $startLineNumber;

            while ($currentLineNumber <= $endLineNumber) {
                $line = $this->getLine($currentLineNumber - 1);
                $code[$currentLineNumber] = rtrim(substr($line, 0, 250));
                $currentLineNumber++;
            }

            return $code;
        } catch (RuntimeException $exception) {
            return [];
        }
    }

    public function getLine($lineNumber)
    {
        $this->file->seek($lineNumber);
        return $this->file->current();
    }
}
