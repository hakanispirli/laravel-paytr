<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayTR Merchant Credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are provided by PayTR when you sign up for a merchant account.
    |
    */

    'merchant_id' => env('PAYTR_MERCHANT_ID', ''),
    'merchant_key' => env('PAYTR_MERCHANT_KEY', ''),
    'merchant_salt' => env('PAYTR_MERCHANT_SALT', ''),

    /*
    |--------------------------------------------------------------------------
    | Test Mode
    |--------------------------------------------------------------------------
    |
    | Set this to true to enable PayTR test mode.
    |
    */

    'test_mode' => env('PAYTR_TEST_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Customize the route paths for PayTR callbacks and redirects.
    |
    */
    'routes' => [
        'prefix' => 'payment/paytr',
        'callback' => 'callback',
        'success' => 'success',
        'fail' => 'fail',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redirect URLs
    |--------------------------------------------------------------------------
    |
    | After payment is completed (success or fail), where should the user be redirected?
    | You can use route names or URLs.
    |
    | Examples:
    |   - URL: 'https://example.com/orders'
    |   - Route name: 'orders.index'
    |   - With flash message: The package will add 'success' or 'error' flash message
    |
    */
    'redirect' => [
        // Where to redirect after successful payment
        // Example: 'orders.show' or '/my-orders'
        'success' => '/',

        // Where to redirect after failed payment
        // Example: 'checkout.index' or '/checkout'
        'fail' => '/',

        // Flash message keys (customize if needed)
        'success_message' => 'Ödeme başarılı!',
        'fail_message' => 'Ödeme başarısız!',
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout Limit
    |--------------------------------------------------------------------------
    |
    | Timeout limit in minutes for the payment page.
    |
    */
    'timeout_limit' => 30,

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Log detailed PayTR requests and responses.
    |
    */
    'debug' => env('PAYTR_DEBUG', false),
];
