<?php

namespace Hakanispirli\Paytr\Services;

use Exception;
use Hakanispirli\Paytr\Contracts\PaytrInterface;
use Hakanispirli\Paytr\Support\Sanitizer;

class PaytrService implements PaytrInterface
{
    protected string $merchantId;
    protected string $merchantKey;
    protected string $merchantSalt;
    protected bool $testMode;
    protected bool $debug;

    public function __construct()
    {
        $this->merchantId = config('paytr.merchant_id', '');
        $this->merchantKey = config('paytr.merchant_key', '');
        $this->merchantSalt = config('paytr.merchant_salt', '');
        $this->testMode = (bool) config('paytr.test_mode', false);
        $this->debug = (bool) config('paytr.debug', false);
    }

    /**
     * Set Merchant Credentials manually
     */
    public function setConfig(string $id, string $key, string $salt): self
    {
        $this->merchantId = $id;
        $this->merchantKey = $key;
        $this->merchantSalt = $salt;
        return $this;
    }

    /**
     * Get PayTR iframe token
     *
     * @param array $user ['email' => '', 'name' => '', 'address' => '', 'phone' => '', 'ip' => '']
     * @param array $basket [['name', 'price', quantity], ...]
     * @param string $orderId Unique Order ID / Number
     * @param float $totalAmount Total amount (e.g., 100.50)
     * @param string|null $okUrl Success callback URL
     * @param string|null $failUrl Fail callback URL
     * @param string $currency Currency (default TL)
     * @param array $installments ['no_installment' => 0, 'max_installment' => 0]
     * @param string $lang Language code
     * @return array ['success' => bool, 'token' => string|null, 'message' => string|null]
     * @throws Exception
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
    ): array {
        // Validation
        if (empty($this->merchantId) || empty($this->merchantKey) || empty($this->merchantSalt)) {
            throw new Exception('PayTR credentials are not set.');
        }

        // SANITIZE ALL INPUTS
        $merchantOid = Sanitizer::merchantOid($orderId);

        if (empty($merchantOid)) {
            throw new Exception('Order ID cannot be empty after sanitization.');
        }

        // User data sanitization
        $email = Sanitizer::email($user['email'] ?? '');
        $userName = Sanitizer::text($user['name'] ?? '', 100);
        $userAddress = Sanitizer::text($user['address'] ?? '', 300);
        $userPhone = Sanitizer::phone($user['phone'] ?? '');
        $userIp = Sanitizer::ip($user['ip'] ?? request()->ip() ?? '0.0.0.0');

        // Amount validation
        $totalAmount = Sanitizer::amount($totalAmount);
        if ($totalAmount <= 0) {
            throw new Exception('Payment amount must be greater than zero.');
        }
        $paymentAmount = (int) ($totalAmount * 100);

        // Basket sanitization
        $sanitizedBasket = Sanitizer::basket($basket);
        if (empty($sanitizedBasket)) {
            throw new Exception('Basket cannot be empty.');
        }
        $userBasket = base64_encode(json_encode($sanitizedBasket));

        // Currency and language
        $currency = Sanitizer::currency($currency);
        $lang = Sanitizer::language($lang);

        // Other parameters
        $timeoutLimit = max(1, min(1440, (int) config('paytr.timeout_limit', 30)));
        $debugOn = $this->debug ? 1 : 0;
        $testMode = $this->testMode ? 1 : 0;

        $noInstallment = in_array((int) ($installments['no_installment'] ?? 0), [0, 1])
            ? (int) ($installments['no_installment'] ?? 0)
            : 0;
        $maxInstallment = max(0, min(12, (int) ($installments['max_installment'] ?? 0)));

        // URLs
        $okUrl = $okUrl ?? route('paytr.success');
        $failUrl = $failUrl ?? route('paytr.fail');

        // Generate Hash
        $hashStr = $this->merchantId . $userIp . $merchantOid . $email . $paymentAmount . $userBasket . $noInstallment . $maxInstallment . $currency . $testMode;
        $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $this->merchantSalt, $this->merchantKey, true));

        $postVals = [
            'merchant_id' => $this->merchantId,
            'user_ip' => $userIp,
            'merchant_oid' => $merchantOid,
            'email' => $email,
            'payment_amount' => $paymentAmount,
            'paytr_token' => $paytrToken,
            'user_basket' => $userBasket,
            'debug_on' => $debugOn,
            'no_installment' => $noInstallment,
            'max_installment' => $maxInstallment,
            'user_name' => $userName,
            'user_address' => $userAddress,
            'user_phone' => $userPhone,
            'merchant_ok_url' => $okUrl,
            'merchant_fail_url' => $failUrl,
            'timeout_limit' => $timeoutLimit,
            'currency' => $currency,
            'test_mode' => $testMode,
            'lang' => $lang
        ];

        return $this->callPaytrApi($postVals);
    }

    /**
     * Curl Helper - sends request to PayTR API
     */
    protected function callPaytrApi(array $postVals): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.paytr.com/odeme/api/get-token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postVals);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $result = @curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'message' => 'Connection error: ' . $error];
        }

        curl_close($ch);

        $result = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid response from PayTR'];
        }

        if (isset($result['status']) && $result['status'] === 'success') {
            return ['success' => true, 'token' => $result['token']];
        }

        return ['success' => false, 'message' => $result['reason'] ?? 'Unknown PayTR Error'];
    }

    /**
     * Verify Callback Hash from PayTR
     * 
     * Uses timing-safe comparison to prevent timing attacks.
     */
    public function verifyCallback(array $post): bool
    {
        $sanitized = Sanitizer::callbackData($post);

        $expectedHash = base64_encode(
            hash_hmac(
                'sha256',
                $sanitized['merchant_oid'] . $this->merchantSalt . $sanitized['status'] . $sanitized['total_amount'],
                $this->merchantKey,
                true
            )
        );

        return hash_equals($expectedHash, $post['hash'] ?? '');
    }
}
