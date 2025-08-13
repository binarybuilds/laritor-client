<?php

namespace BinaryBuilds\LaritorClient\Redactor;

interface DataRedactor
{
    public function redactEmailAddress(string $address): string;

    public function redactString(string $text): string;

    public function redactArray(array $array): array;

    public function redactArrayValue(string $key, string $text): string;

    public function redactAuthenticatedUser(): array;

    public function redactIPAddress($ip): string;

    public function redactUserAgent($userAgent): string;

}