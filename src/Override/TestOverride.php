<?php

namespace BinaryBuilds\LaritorClient\Override;

class TestOverride extends DefaultOverride
{
    public function recordRequest($request): bool
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
}