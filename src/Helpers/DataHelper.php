<?php

namespace BinaryBuilds\LaritorClient\Helpers;

use BinaryBuilds\LaritorClient\Redactor\DataRedactor;

class DataHelper
{
    public static function redactEmailAddress($address)
    {
        return rescue(function () use ($address) {
            return app(DataRedactor::class)->redactEmailAddress($address);
        }, $address);
    }

    public static function redactHeaders(array $array): array
    {
        $array = array_map(function ($value) {
            return is_array($value) ? implode(', ', $value) : $value;
        }, $array);

        return self::redactArray( $array );
    }

    public static function redactArray(array $array): array
    {
        return rescue(function () use ($array) {
            return app(DataRedactor::class)->redactArray($array);
        }, $array);
    }

    public static function redactData($text)
    {
        return rescue(function () use ($text) {
            return  app(DataRedactor::class)->redactString((string)$text);
        }, $text);
    }

    public static function getRedactedContext()
    {
        if (config('laritor.context') && class_exists(\Illuminate\Support\Facades\Context::class)) {
            return app(DataRedactor::class)->redactArray(
                \Illuminate\Support\Facades\Context::all()
            );
        }

        return [];
    }

    public static function getRedactedUser()
    {
        return rescue(function () {
            return  app(DataRedactor::class)->redactAuthenticatedUser();
        }, [
            'id' => null,
            'name' => null,
            'email' => null
        ]);
    }

    public static function redactIPAddress($ip)
    {
        return rescue(function () use ($ip) {
            return  app(DataRedactor::class)->redactIPAddress($ip);
        }, $ip);
    }

    public static function redactUserAgent($userAgent)
    {
        return rescue(function () use ($userAgent) {
            return  app(DataRedactor::class)->redactUserAgent($userAgent);
        }, $userAgent);
    }
}