<?php

namespace Laritor\LaravelClient\Checks;

use Illuminate\Http\Request;

class BaseHealthCheck
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function run(Request $request)
    {
        if (config('laritor.keys.backend') && $request->input('token') === config('laritor.keys.backend')) {
            try{
                $isSuccess = $this->check($request);

                if ($isSuccess) {
                    return response()->json(['message' => $this->successMessage() ]);
                }

                return response()->json(['message' => $this->failureMessage() ], 500);

            } catch (\Throwable $exception) {
                return response()->json(['message' => $exception->getMessage()], 500);
            }
        }

        return response()->json(['message' => 'token is invalid'], 401);

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