<?php

namespace Hakanispirli\Paytr\Support;

/**
 * Input Sanitizer for PayTR data
 * 
 * Handles security sanitization for all user inputs
 * to prevent XSS, SQL injection, and other attacks.
 */
class Sanitizer
{
    /**
     * Sanitize merchant_oid - ONLY alphanumeric allowed by PayTR
     * 
     * PayTR strictly requires merchant_oid to contain ONLY:
     * - Letters (A-Z, a-z)
     * - Numbers (0-9)
     * 
     * Characters like -, _, ., #, etc. are NOT allowed.
     */
    public static function merchantOid(string $orderId): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $orderId);
    }

    /**
     * Sanitize email address
     */
    public static function email(string $email): string
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    /**
     * Sanitize string for XSS prevention
     * Used for name, address, and other text fields
     */
    public static function text(string $value, int $maxLength = 255): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);

        // Strip HTML and PHP tags
        $value = strip_tags($value);

        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Trim and limit length
        return mb_substr(trim($value), 0, $maxLength);
    }

    /**
     * Sanitize phone number - only digits and + allowed
     */
    public static function phone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    /**
     * Sanitize IP address
     */
    public static function ip(string $ip): string
    {
        $ip = filter_var($ip, FILTER_VALIDATE_IP);

        return $ip ?: '0.0.0.0';
    }

    /**
     * Sanitize amount - ensure it's a valid positive number
     */
    public static function amount(mixed $amount): float
    {
        $amount = (float) $amount;

        return max(0, $amount);
    }

    /**
     * Sanitize basket items
     * Each item should be [name, price, quantity]
     */
    public static function basket(array $basket): array
    {
        $sanitizedBasket = [];

        foreach ($basket as $item) {
            if (!is_array($item) || count($item) < 3) {
                continue;
            }

            $sanitizedBasket[] = [
                self::text($item[0], 100),                    // name
                number_format((float) $item[1], 2, '.', ''),  // price
                max(1, (int) $item[2]),                       // quantity
            ];
        }

        return $sanitizedBasket;
    }

    /**
     * Sanitize currency code
     */
    public static function currency(string $currency): string
    {
        $allowed = ['TL', 'EUR', 'USD', 'GBP', 'RUB'];
        $currency = strtoupper(trim($currency));

        return in_array($currency, $allowed) ? $currency : 'TL';
    }

    /**
     * Sanitize language code
     */
    public static function language(string $lang): string
    {
        $allowed = ['tr', 'en', 'de', 'ru', 'ar'];
        $lang = strtolower(trim($lang));

        return in_array($lang, $allowed) ? $lang : 'tr';
    }

    /**
     * Sanitize callback data from PayTR
     * Used when processing POST data from PayTR callback
     */
    public static function callbackData(array $data): array
    {
        return [
            'merchant_oid' => self::merchantOid($data['merchant_oid'] ?? ''),
            'status' => in_array($data['status'] ?? '', ['success', 'failed']) ? $data['status'] : 'failed',
            'total_amount' => (int) ($data['total_amount'] ?? 0),
            'hash' => $data['hash'] ?? '',
            'failed_reason_code' => (int) ($data['failed_reason_code'] ?? 0),
            'failed_reason_msg' => self::text($data['failed_reason_msg'] ?? '', 500),
            'test_mode' => (int) ($data['test_mode'] ?? 0),
            'payment_type' => self::text($data['payment_type'] ?? '', 50),
        ];
    }
}
