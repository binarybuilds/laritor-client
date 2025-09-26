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

            if (Str::contains($frame['file'], 'vendor/')) {
                return Str::contains($frame['file'], $this->whitelistedVendors());
            }

            return true;
        });
    }

    /**
     * Get the file paths that should not be used by backtraces.
     *
     * @return array
     */
    protected function whitelistedVendors(): array
    {
        $whitelist = config('laritor.whitelisted_vendors', '') ? explode(',', config('laritor.whitelisted_vendors', '')) : [];

        return array_map(function ($path) {
            return 'vendor/'.$path;
        }, array_merge(['laravel/nova'], $whitelist));
    }
}