<?php

namespace App\Support;

class WhatsAppNumberNormalizer
{
    /**
     * Normalize phone number to standard WhatsApp international format without leading plus.
     * Example: 081234567890 -> 6281234567890
     *          +6281234567890 -> 6281234567890
     *          6281234567890 -> 6281234567890
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        // Clean allowed formatting characters (spaces, dashes, dots, parentheses)
        $cleaned = preg_replace('/[\s\-\.\(\)]+/', '', $phone);

        // Check if string contains unexpected non-digit characters (other than an optional leading +)
        if (! preg_match('/^\+?[0-9]+$/', $cleaned)) {
            return null;
        }

        // Strip leading plus
        if (str_starts_with($cleaned, '+')) {
            $cleaned = substr($cleaned, 1);
        }

        // Replace leading 0 with 62 (Indonesian standard)
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62'.substr($cleaned, 1);
        }

        // Validate length and prefix (E.164 max 15 digits, min 10 digits for Indonesian numbers)
        if (! self::isValid($cleaned)) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Check whether the normalized phone string represents a valid WhatsApp number.
     */
    public static function isValid(?string $phone): bool
    {
        if ($phone === null) {
            return false;
        }

        // Must be digits only, between 10 and 15 digits
        if (! preg_match('/^[0-9]{10,15}$/', $phone)) {
            return false;
        }

        // For Indonesian numbers (starting with 62), must start with 628 (standard mobile)
        if (str_starts_with($phone, '62')) {
            return str_starts_with($phone, '628');
        }

        return true;
    }
}
