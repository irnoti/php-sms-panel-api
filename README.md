# irnoti/php-sms-panel-api

[![Packagist](https://img.shields.io/packagist/v/irnoti/php-sms-panel-api.svg)](https://packagist.org/packages/irnoti/php-sms-panel-api) [![tests](https://github.com/irnoti/php-sms-panel-api/actions/workflows/tests.yml/badge.svg)](https://github.com/irnoti/php-sms-panel-api/actions/workflows/tests.yml) [![license](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)

Framework-agnostic PHP SDK (ext-curl). On Laravel use [irnoti/laravel-sms-panel-api](https://github.com/irnoti/laravel-sms-panel-api), which adds the service provider, facade and config.

```bash
composer require irnoti/php-sms-panel-api
```

```php
$irnoti = new Irnoti\Sdk\IrnotiClient(getenv('IRNOTI_API_KEY'));   // baseUrl, timeout, retries, transport are optional

try {
    $irnoti->sms()->send(lineId: 1, to: '09121234567', text: 'Hi', idempotencyKey: 'k1');
    $irnoti->sms()->pattern(code: 'k3f9a2', to: '09121234567', params: ['code' => '4821']);
    $irnoti->services()->smart(['09121234567'], 'Hello');
} catch (Irnoti\Sdk\Exceptions\IrnotiApiException $e) {
    $e->isInsufficientBalance();
}

$event = Irnoti\Sdk\Support\Webhook::parse(file_get_contents('php://input'), $_SERVER['HTTP_X_IRNOTI_SIGNATURE'] ?? '', $secret);
```

Pass a `transport` callable to plug in Guzzle or a PSR-18 client or to fake HTTP in tests.

## Behaviour shared by all irnoti SDKs

- **Auth:** `Authorization: Bearer irn_live_…`. Use an `irn_test_…` key with `lineId: "sandbox"` to try sends for free. Keys carry scopes (`sms.send`, `sms.read`, `wallet.read`, `phonebook.read|write`, `bot.send|read`, `verify.send|read`); a missing scope is a `403`.
- **Money** is integer **Rial** returned as a **string** (`"1960"`) — never parse it into a float.
- **Idempotency:** every send takes an idempotency key; retrying with the same key never sends twice (`replayed: true`). Automatic retries apply only to `GET`s and sends that carry a key.
- **Errors:** one error type carrying `status`, the API's machine `code` (`insufficient_balance`, `invalid_recipients`, `no_price`, … — empty for plain 401/403/404) and `details`, with helpers such as `isInsufficientBalance` / `isRateLimited` / `isUnauthenticated`.
- **Limits:** send 600 req/min, pattern 1200 req/min per IP (`429` when exceeded).
- **Webhooks:** verify the **raw** body against `X-Irnoti-Signature: t=<unix>,v1=<hex>` (HMAC-SHA256 of `"<t>.<body>"` with your `whsec_…` key); signatures older than 5 minutes are rejected.
- Bot creation/configuration and verify settings are panel-only and not exposed.

Sibling SDKs: [Laravel](https://github.com/irnoti/laravel-sms-panel-api) · [Node.js](https://github.com/irnoti/node-sms-panel-api) · [Python](https://github.com/irnoti/python-sms-panel-api) · [Go](https://github.com/irnoti/go-sms-panel-api) · [PHP](https://github.com/irnoti/php-sms-panel-api). API reference: [irnoti.com/docs](https://irnoti.com/docs).

MIT licensed.
