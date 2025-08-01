<?php

namespace BinaryBuilds\LaritorClient\Redactor;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DefaultRedactor implements DataRedactor
{
    public function redactEmailAddress(string $address): string
    {
        if (config('laritor.anonymize.pii')) {
            return 'redacted-'.Str::random(4).'@redacted.com';
        }

        return $address;
    }

    public function redactString(string $text): string
    {
        $patterns = [

            // API Keys (Stripe, GitHub, Google, JWTs, etc.)
            '/(sk_live|sk_test|pk_live|pk_test|rk_live|rk_test)_[0-9a-zA-Z]{20,40}/' => '***',
            '/ghp_[0-9a-zA-Z]{36}/' => '***',
            '/AIza[0-9A-Za-z\-_]{35}/' => '***',
            '/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/' => '***',
            '/eyJ[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+/' => '***', // JWT

            // Credit Card Numbers (Visa, MasterCard, AmEx, etc.)
            '/\b(?:\d[ -]*?){13,19}\b/' => '***',

            // Bank Account Numbers (generic)
            '/\b\d{9,18}\b/' => '***',

            // U.S. SSN
            '/\b\d{3}-\d{2}-\d{4}\b/' => '***',

            // Canada SIN
            '/\b\d{3} \d{3} \d{3}\b/' => '***',

            // Aadhaar Number
            '/\b\d{4} \d{4} \d{4}\b/' => '***',

            // NHS Number
            '/\b\d{3} \d{3} \d{4}\b/' => '***',

            // Driver's license (some common patterns)
            '/\b[A-Z]{1,2}\d{6,8}\b/' => '***',

            // Medical Record Numbers (MRN)
            '/\b(MRN[:\s]?)\d{6,12}\b/i' => '$1***',

            // Passport Numbers (generic pattern)
            '/\b[A-Z]{1,2}\d{6,9}\b/' => '***',

            // Tax Identification Numbers (TIN, PAN, etc.)
            '/\b[A-Z]{5}\d{4}[A-Z]{1}\b/' => '***', // PAN - India
            '/\b\d{2}-\d{7}\b/' => '***', // US TIN

            // API secrets in .env style
            '/([A-Z_]+_SECRET|API_KEY|ACCESS_TOKEN|PRIVATE_KEY)\s*=\s*["\']?[A-Za-z0-9_\-\/+=]{16,}["\']?/' => '$1=***',

            // Credit card CVV (if labeled)
            '/(CVV|CVC|CVV2)\s*[:=]?\s*\d{3,4}/i' => '$1: ***',
        ];

        if (config('laritor.anonymize.pii')) {
            $patterns = array_merge($patterns, [
                // Email Addresses
                '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i' => '***',

                // Phone Numbers (basic international)
                '/\+?\d{1,3}[ \-]?\(?\d{1,4}\)?[ \-]?\d{3,5}[ \-]?\d{3,5}/' => '***',
            ]);
        }

        return preg_replace(array_keys($patterns), array_values($patterns), $text);
    }

    public function redactArray(array $array): array
    {
        $redacted = [];
        foreach ($array as $key => $value) {
            $redacted[$key] = is_array($value) ? $this->redactArray($value) : $this->redactArrayValue($key, (string)$value);
        }

        return $redacted;
    }

    public function redactArrayValue(string $key, string $text): string
    {
        $sensitiveKeys = [
            'authorization', 'proxy-authorization', 'cookie', 'set-cookie', 'x-api-key',
            'x-auth-token', 'x-csrf-token', 'x-xsrf-token', 'x-access-token', 'x-refresh-token',
            'x-session-id', 'x-signature', 'x-client-secret', 'x-secret-key', 'x-amz-security-token',
            'x-firebase-auth', 'x-sentry-auth', 'x-github-token', 'x-slack-signature', 'x-shopify-hmac-sha256',
            'api_key', 'api_token', 'api-key', 'api-token', 'username', 'password', 'password-hash',
            'client_id', 'client_secret', 'access_token', 'refresh_token', 'refresh-token', 'access-token',
            'secret', 'auth', 'credentials', 'token', 'private_key', 'certificate', 'authorization-token',
            'oauth_token', 'oauth2_token', 'id_token', 'session_token', 'jwt', 'openid', 'login_token',
            'user_token', 'sso_token', 'credit_card', 'creditcard', 'card_number', 'cardnumber',
            'cc_number', 'cvv', 'cvc', 'expiry', 'expiration', 'iban', 'swift', 'routing_number',
            'bank_account', 'current_password', 'new_password', 'password_confirmation', 'pass', 'pwd',
            '2fa_code', 'mfa_code', 'otp', 'pin', 'security_code', 'security_answer', 'recovery_code',
            'encryption_key', 'aws_secret_access_key', 'gcp_private_key', 'azure_secret',
        ];

        if (in_array(strtolower($key), $sensitiveKeys)) {
         return '*****';
        }

        return $this->redactString($text);
    }

    public function redactAuthenticatedUser() : array
    {
        $user = Auth::user();

        return [
            'id' => $user ? $user->id : null,
            'name' => $user ? ( config('laritor.anonymize.user') ? 'User '.$user->id : $user->name) : '',
            'email' => $user ? ( config('laritor.anonymize.user') ? 'user'.$user->id.'@redacted.com' : $user->email) : '',
        ];
    }
}