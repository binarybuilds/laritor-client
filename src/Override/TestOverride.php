<?php

namespace BinaryBuilds\LaritorClient\Override;

class TestOverride extends DefaultOverride
{
    public function recordRequest($request, $response, $status, $duration, $user): bool
    {
        $ignore = [
            'laritor-job',
            'laritor-failed-job'
        ];

        foreach ($ignore as $ignored ) {
            if ($request->is($ignored)) {
                return false;
            }
        }

        return true;
    }

    public function recordException($exception): bool
    {
        return !request()->is('laritor-failed-job');
    }
}