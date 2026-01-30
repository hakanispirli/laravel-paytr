<?php

namespace Hakanispirli\Paytr\Contracts;

/**
 * PayTR Service Interface
 * 
 * Defines the contract for PayTR payment operations.
 * This can be used to create mock implementations for testing.
 */
interface PaytrInterface
{
    /**
     * Set merchant credentials manually
     */
    public function setConfig(string $id, string $key, string $salt): self;

    /**
     * Get PayTR iframe token for payment
     *
     * @param array $user User information ['email', 'name', 'address', 'phone', 'ip']
     * @param array $basket Cart items [['name', 'price', 'quantity'], ...]
     * @param string $orderId Unique order identifier
     * @param float $totalAmount Total payment amount
     * @param string|null $okUrl Success redirect URL
     * @param string|null $failUrl Fail redirect URL
     * @param string $currency Currency code (TL, EUR, USD, etc.)
     * @param array $installments Installment options
     * @param string $lang Language code
     * @return array ['success' => bool, 'token' => string|null, 'message' => string|null]
     */
    public function getIframeToken(
        array $user,
        array $basket,
        string $orderId,
        float $totalAmount,
        ?string $okUrl = null,
        ?string $failUrl = null,
        string $currency = 'TL',
        array $installments = [],
        string $lang = 'tr'
    ): array;

    /**
     * Verify callback hash from PayTR
     */
    public function verifyCallback(array $post): bool;
}
