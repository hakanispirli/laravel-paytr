<?php

namespace Hakanispirli\Paytr\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getIframeToken(array $user, array $basket, string $orderId, float $totalAmount, ?string $okUrl = null, ?string $failUrl = null, string $currency = 'TL', array $installments = [], string $lang = 'tr')
 * @method static bool verifyCallback(array $post)
 * @method static self setConfig(string $id, string $key, string $salt)
 * 
 * @see \Hakanispirli\Paytr\Services\PaytrService
 */
class Paytr extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'paytr';
    }
}
