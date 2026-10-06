<?php

namespace App\Support;

class Pin
{
    /** Unambiguous characters only: no 0/O, 1/I/L. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public static function generate(int $length = 8): string
    {
        $pin = '';
        for ($i = 0; $i < $length; $i++) {
            $pin .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $pin;
    }

    /** Uppercase and strip anything that isn't a letter or digit, so "abcd-1234" == "ABCD1234". */
    public static function normalize(string $pin): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($pin));
    }

    public static function lookupHash(string $pin): string
    {
        return hash_hmac('sha256', self::normalize($pin), config('app.key'));
    }

    /** Group into blocks of four for display: ABCD1234 -> ABCD-1234. */
    public static function format(string $pin): string
    {
        return implode('-', str_split(self::normalize($pin), 4));
    }
}
