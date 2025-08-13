<?php

namespace BinaryBuilds\LaritorClient\Checks;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Class BaseHealthCheck
 * @package BinaryBuilds\LaritorClient\Checks
 */
class BaseHealthCheck
{
    /**
     * @var int
     * Max no of seconds to run the health check before timing out
     */
    public static $timeout = 10;

    /**
     * @var string
     */
    public static $name = 'Custom Health Check';

    /**
     * @var bool
     */
    protected $ping_back = false;

    /**
     * @param Request $request
     * @return mixed
     */
    public function run(Request $request)
    {
        try{
            $isSuccess = $this->check($request);

            if ($isSuccess) {
                return response()->json([
                    'custom_context' => DataHelper::getRedactedContext(),
                    'message' => Str::limit($this->successMessage(), 200),
                    'ping_back' => $this->ping_back
                ]);
            }

            return response()->json([
                'custom_context' => DataHelper::getRedactedContext(),
                'message' => Str::limit($this->failureMessage(), 200),
                'ping_back' => $this->ping_back
            ], 500);

        } catch (\Throwable $exception) {
            return response()->json([
                'custom_context' => DataHelper::getRedactedContext(),
                'message' => Str::limit($exception->getMessage(), 200),
                'ping_back' => $this->ping_back
            ], 500);
        }
    }

    /**
     * @return bool
     */
    public function check(Request $request)
    {
        return false;
    }

    /**
     * @return string
     */
    public function successMessage()
    {
        return 'health check passed';
    }

    /**
     * @return string
     */
    public function failureMessage()
    {
        return 'health check not configured properly';
    }
}