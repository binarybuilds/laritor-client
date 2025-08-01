<?php

namespace BinaryBuilds\LaritorClient\Helpers;

use BinaryBuilds\LaritorClient\Redactor\DataRedactor;

class DataHelper
{
    public static function redactEmailAddress($address)
    {
        return rescue(function () use ($address) {
            return app(DataRedactor::class)->redactEmailAddress($address);
        }, function () use ($address){
            return $address;
        }, true);
    }

    public static function redactArray(array $array): array
    {
        return rescue(function () use ($array) {
            return app(DataRedactor::class)->redactArray($array);
        }, function () use ($array){
            return $array;
        }, true);
    }

    public static function redactData($text)
    {
        return rescue(function () use ($text) {
            return  app(DataRedactor::class)->redactString((string)$text);
        }, function () use ($text){
            return $text;
        }, true);
    }

    public static function getRedactedUser()
    {
        return rescue(function () {
            return  app(DataRedactor::class)->redactAuthenticatedUser();
        }, function () {
            return [
                'id' => null,
                'name' => null,
                'email' => null
            ];
        }, true);
    }
}