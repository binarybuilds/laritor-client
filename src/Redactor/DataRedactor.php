<?php

namespace BinaryBuilds\LaritorClient\Redactor;

interface DataRedactor
{
    public function redactEmailAddress($address);

    public function redactString($text);

    public function redactArray(array $array): array;

    public function redactArrayValue($key, $text);

    public function redactAuthenticatedUser(): array;

    /**
     * @param string|null $ip
     * @return string
     */
    public function redactIPAddress($ip);

    /**
     * @param string|null $userAgent
     * @return string
     */
    public function redactUserAgent($userAgent);

}