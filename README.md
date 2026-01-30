# Laravel PayTR Paketi

Laravel 12+ projeleri için geliştirilmiş, kullanımı kolay ve güvenli PayTR **iFrame API** entegrasyon paketi.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/hakanispirli/laravel-paytr.svg?style=flat-square)](https://packagist.org/packages/hakanispirli/laravel-paytr)
[![License](https://img.shields.io/packagist/l/hakanispirli/laravel-paytr.svg?style=flat-square)](https://packagist.org/packages/hakanispirli/laravel-paytr)

## Kurulum

1. Paketi composer ile projenize dahil edin:
   ```bash
   composer require hakanispirli/laravel-paytr
   ```

2. Ayar dosyasını yayınlayın:
   ```bash
   php artisan vendor:publish --tag=paytr-config
   ```

3. `.env` dosyanıza PayTR mağaza bilgilerinizi ekleyin:
   ```env
   PAYTR_MERCHANT_ID=magaza_no
   PAYTR_MERCHANT_KEY=magaza_anahtari
   PAYTR_MERCHANT_SALT=magaza_salt
   PAYTR_TEST_MODE=true
   ```

## ÖNEMLİ: CSRF Ayarı (Zorunlu!)

PayTR, ödeme sonucunu bildirmek için sitenize dışarıdan bir `POST` isteği gönderir. Laravel'in bu isteği engellememesi için PayTR callback rotasını CSRF korumasından hariç tutmalısınız.

### Laravel 12 (`bootstrap/app.php`):

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // PayTR callback için CSRF korumasını devre dışı bırak
        $middleware->validateCsrfTokens(except: [
            'payment/paytr/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

## ÖNEMLİ: Callback URL Ayarı

PayTR Mağaza Paneline giriş yapın ve **Bildirim URL (Callback URL)** kısmını şu şekilde ayarlayın:

```
https://siteniz.com/payment/paytr/callback
```

> **Not:** Sitenizde SSL/HTTPS aktif olmalıdır.

## Konfigürasyon

`config/paytr.php` dosyasından özelleştirebileceğiniz ayarlar:

```php
'redirect' => [
    'success' => 'orders.show',  // route adı veya '/my-orders' (URL)
    'fail' => 'checkout.index',
    'success_message' => 'Ödeme başarılı!',
    'fail_message' => 'Ödeme başarısız!',
],
```

## Kullanım

### 1. Ödeme Formu (Token) Oluşturma

```php
use Hakanispirli\Paytr\Facades\Paytr;

public function payment()
{
    $user = [
        'email' => auth()->user()->email,
        'name' => auth()->user()->name,
        'address' => 'Adres Bilgisi',
        'phone' => '05555555555',
        'ip' => request()->ip()
    ];

    $basket = [
        ['Ürün 1', '10.00', 1],
        ['Ürün 2', '20.50', 2],
    ];
    
    // ⚠️ ÖNEMLİ: PayTR sadece alfanümerik karakterler kabul eder!
    // Tire (-), alt çizgi (_), nokta (.) KABUL EDİLMEZ.
    $orderId = 'SIP' . time(); // Doğru: SIP1706612345
    
    $result = Paytr::getIframeToken(
        $user, 
        $basket, 
        $orderId, 
        150.00
    );

    if ($result['success']) {
        return view('payment', ['token' => $result['token']]);
    }

    return back()->with('error', $result['message']);
}
```

### 2. Blade Template (iFrame)

```html
<iframe src="https://www.paytr.com/odeme/guvenli/{{ $token }}" 
        id="paytriframe" 
        frameborder="0" 
        scrolling="no" 
        style="width: 100%; height: 600px;">
</iframe>

<script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
<script>iFrameResize({}, '#paytriframe');</script>
```

### 3. Ödeme Sonucunu Yakalama (Events)

**`app/Providers/AppServiceProvider.php` içinde:**

```php
use Hakanispirli\Paytr\Events\PaytrPaymentSuccess;
use Hakanispirli\Paytr\Events\PaytrPaymentFailed;
use Illuminate\Support\Facades\Event;

public function boot(): void
{
    Event::listen(PaytrPaymentSuccess::class, function ($event) {
        $order = Order::find($event->merchantOid);
        if ($order) {
            $order->update(['status' => 'paid']);
        }
    });

    Event::listen(PaytrPaymentFailed::class, function ($event) {
        logger()->error('PayTR Ödeme Başarısız', [
            'order_id' => $event->merchantOid,
            'reason' => $event->reason,
        ]);
    });
}
```

## Güvenlik Özellikleri

| Özellik | Açıklama |
|---------|----------|
| **XSS Koruması** | Tüm girdiler `htmlspecialchars()` ile sanitize edilir |
| **Input Validation** | Email, telefon, IP adresi, tutar doğrulaması |
| **Timing-Safe Hash** | `hash_equals()` ile timing attack koruması |
| **SSL Verification** | HTTPS zorunluluğu |

## Gereksinimler

- PHP 8.2+
- Laravel 11.x veya 12.x
- ext-curl

## Lisans

MIT License

## Geliştirici

- [Hakan İspirli](https://github.com/hakanispirli)
- [Webmarka](https://webmarka.com)
