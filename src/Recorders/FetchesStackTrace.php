<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use Illuminate\Support\Str;

trait FetchesStackTrace
{
    /**
     * Find the first frame in the stack.
     *
     * @param int $forgetLines
     * @return array|null
     */
    protected function getCallerFromStackTrace($forgetLines = 0)
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS))->forget($forgetLines);

        return $trace->first(function ($frame) {
            if (! isset($frame['file'])) {
                return false;
            }

            return ! Str::contains($frame['file'], $this->ignoredPaths());
        });
    }

    /**
     * Get the file paths that should not be used by backtraces.
     *
     * @return array
     */
    protected function ignoredPaths(): array
    {
        return [
            'artisan','laritor/', base_path('vendor'.DIRECTORY_SEPARATOR.$this->ignoredVendorPath())
        ];
    }

    /**
     * Choose the frame outside of either Telescope / Laravel or all packages.
     *
     * @return string|null
     */
    protected function ignoredVendorPath()
    {
        if (! ($this->options['ignore_packages'] ?? true)) {
            return 'laravel';
        }

        return '';
    }
}