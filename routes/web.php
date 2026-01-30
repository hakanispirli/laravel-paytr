<?php

use Illuminate\Support\Facades\Route;
use Hakanispirli\Paytr\Http\Controllers\PaytrController;

$prefix = config('paytr.routes.prefix', 'payment/paytr');

/*
|--------------------------------------------------------------------------
| PayTR Callback Route (NO web middleware - PayTR sends this from their server)
|--------------------------------------------------------------------------
|
| This route receives POST notifications from PayTR's server.
| It should NOT have CSRF protection or session middleware.
|
*/
Route::post($prefix . '/' . config('paytr.routes.callback', 'callback'), [PaytrController::class, 'callback'])
    ->name('paytr.callback')
    ->withoutMiddleware(['web']);

/*
|--------------------------------------------------------------------------
| Success/Fail Pages (with web middleware for user redirects)
|--------------------------------------------------------------------------
|
| These routes handle user redirects after payment.
| They need web middleware for session flash messages.
|
*/
Route::group(['prefix' => $prefix, 'middleware' => ['web']], function () {
    // Success page - PayTR may redirect with GET or POST
    Route::match(['get', 'post'], config('paytr.routes.success', 'success'), [PaytrController::class, 'success'])
        ->name('paytr.success');

    // Fail page - PayTR may redirect with GET or POST
    Route::match(['get', 'post'], config('paytr.routes.fail', 'fail'), [PaytrController::class, 'fail'])
        ->name('paytr.fail');
});
