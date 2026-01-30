<?php

namespace Hakanispirli\Paytr\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Hakanispirli\Paytr\Facades\Paytr;
use Hakanispirli\Paytr\Events\PaytrPaymentSuccess;
use Hakanispirli\Paytr\Events\PaytrPaymentFailed;
use Hakanispirli\Paytr\Support\Sanitizer;

class PaytrController extends Controller
{
    /**
     * Handle PayTR Callback
     * 
     * This is called by PayTR's server, NOT by the user's browser.
     * Hash verification ensures the request is authentic.
     */
    public function callback(Request $request)
    {
        $post = $request->all();

        // 1. Verify Hash (timing-safe comparison)
        if (!Paytr::verifyCallback($post)) {
            Log::warning('PayTR Callback: Hash verification failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return response('PAYTR notification failed: bad hash', 400);
        }

        // 2. Sanitize callback data
        $sanitized = Sanitizer::callbackData($post);

        // 3. Dispatch Events
        if ($sanitized['status'] === 'success') {
            event(new PaytrPaymentSuccess(
                $sanitized['merchant_oid'],
                $sanitized['total_amount'],
                $sanitized
            ));
        } else {
            event(new PaytrPaymentFailed(
                $sanitized['merchant_oid'],
                $sanitized['failed_reason_msg'],
                $sanitized
            ));
        }

        // 4. Return OK to PayTR
        return response('OK');
    }

    /**
     * Success Redirect
     * 
     * User is redirected here after successful payment.
     */
    public function success(Request $request)
    {
        $redirectUrl = config('paytr.redirect.success', '/');
        $message = config('paytr.redirect.success_message', 'Ödeme başarılı!');

        return $this->redirectTo($redirectUrl, 'success', $message);
    }

    /**
     * Fail Redirect
     * 
     * User is redirected here after failed payment.
     */
    public function fail(Request $request)
    {
        $redirectUrl = config('paytr.redirect.fail', '/');
        $message = config('paytr.redirect.fail_message', 'Ödeme başarısız!');

        return $this->redirectTo($redirectUrl, 'error', $message);
    }

    /**
     * Helper: Redirect to URL or route
     */
    protected function redirectTo(string $url, string $flashKey, string $message)
    {
        // If starts with / or http, it's a URL
        if (str_starts_with($url, '/') || str_starts_with($url, 'http')) {
            return redirect($url)->with($flashKey, $message);
        }

        // Check if route exists
        if (app('router')->has($url)) {
            return redirect()->route($url)->with($flashKey, $message);
        }

        // Fallback to home
        return redirect('/')->with($flashKey, $message);
    }
}
