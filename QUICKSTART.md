# Quickstart (PHP)

Goal: send your first SMS with the irnoti PHP SDK in about 5 minutes, without spending money.

Full function list: [README → function reference](README.md#what-can-this-sdk-do-function-reference). Full API reference (the docs panel): [irnoti.com/docs](https://irnoti.com/docs).

## 1. Install

```bash
composer require irnoti/php-sms-panel-api
```

Requires PHP 8.1+ and `ext-curl`. Works in any framework; on Laravel use [irnoti/laravel-sms-panel-api](https://github.com/irnoti/laravel-sms-panel-api).

## 2. Get an access key

In the irnoti panel create an **access key** (docs panel → *Authentication / Access keys*).

- `irn_test_…` — sandbox key: nothing is sent, nothing is charged. **Start with this.**
- `irn_live_…` — real key: sends real SMS and charges your wallet.

Keep it in an environment variable, never in source control or client-side code:

```bash
export IRNOTI_API_KEY=irn_test_xxxxxxxx
```

## 3. Send a test SMS (sandbox, free)

```php
<?php
require 'vendor/autoload.php';

use Irnoti\Sdk\IrnotiClient;
use Irnoti\Sdk\Exceptions\IrnotiApiException;

$irnoti = new IrnotiClient(getenv('IRNOTI_API_KEY'));

try {
    // How much would it cost? (no send, no charge)
    print_r($irnoti->sms()->quote('sandbox', 'Hello', '09121234567'));

    // Send. idempotencyKey makes retries safe: the same key never sends twice.
    $result = $irnoti->sms()->send(lineId: 'sandbox', to: '09121234567', text: 'Hello', idempotencyKey: 'my-first-send');
    print_r($result); // ['batchId' => …, 'count' => 1, 'totalPrice' => '…', 'replayed' => false]
} catch (IrnotiApiException $e) {
    echo $e->statusCode(), ' ', $e->errorCode(), ' ', $e->getMessage();
}
```

`totalPrice` is an integer amount in **Rial, returned as a string** — don't convert it to a float.

## 4. Go live

1. Switch to an `irn_live_…` key.
2. Find your sender number: list your lines and use its `id` instead of `"sandbox"`.
3. Check money: the wallet `balance` function.
4. Always `quote` first for big sends, and always pass an idempotency key to `send`.

```php
$line = $irnoti->lines()->list()[0]['id'];
$irnoti->sms()->send(lineId: $line, to: ['09121234567'], text: 'Hello', idempotencyKey: 'order-1001');
```

## 5. Common tasks — which function, which docs section

| I want to… | Call | Docs section |
|---|---|---|
| Send one/many SMS | `$irnoti->sms()->send(lineId: $line, text: '…', to: [...])` | SMS |
| Send to a contact group | `$irnoti->sms()->send(lineId: $line, text: '…', groupIds: [12])` | SMS + Phonebook |
| Schedule / drip-send | `$irnoti->sms()->send(..., scheduledAt: …, gradual: ['chunkSize' => 500, 'intervalMinutes' => 10])` | SMS |
| Send an OTP with an approved template | `$irnoti->sms()->pattern(code: 'template_code', to: '0912…', params: ['code' => '4821'])` | SMS → Pattern |
| Check delivery | `$irnoti->messages()->get($id)` or a webhook | SMS → Messages / Webhooks |
| Manage contacts | `$irnoti->phonebook()->createGroup(...)`, `addContacts(...)`, `importCsv(...)` | Phonebook |
| Check balance | `$irnoti->wallet()->balance()` | Wallet |
| Phone verification flow | `$irnoti->verify()->create(to: '0912…', options: ['ttlSec' => 300])` | Verify |
| Telegram bot relay | `$irnoti->broker()->create($label, $token)` | Telegram broker |

(Every function: [README reference table](README.md#what-can-this-sdk-do-function-reference).)

## 6. Handle errors

```php
try {
    $irnoti->sms()->send(lineId: $line, to: '0912…', text: 'Hi');
} catch (IrnotiApiException $e) {
    if ($e->isInsufficientBalance()) { /* top up the wallet */ }
    elseif ($e->isRateLimited()) { /* slow down */ }
    elseif ($e->isInvalidRecipients()) { var_dump($e->details()); }
    else { throw $e; }
}
```

Status → helper: `401` unauthenticated, `402` insufficient balance, `403` forbidden (missing scope / KYC), `404` not found, `422` `no_price`, `429` rate limited (send limit 600 req/min).

## 7. Receive delivery webhooks

Verify the **raw** request body (not re-serialised JSON) against the `X-Irnoti-Signature` header with your `whsec_…` secret. Signatures older than 5 minutes are rejected; events can repeat, so de-duplicate on `X-Irnoti-Delivery`.

```php
use Irnoti\Sdk\Support\Webhook;

try {
    $event = Webhook::parse(file_get_contents('php://input'), $_SERVER['HTTP_X_IRNOTI_SIGNATURE'] ?? '', getenv('IRNOTI_WEBHOOK_SECRET'));
} catch (InvalidArgumentException) {
    http_response_code(400);
    exit;
}
error_log($event['event']);
http_response_code(204);
```

## Next

- Browse everything in the [function reference](README.md#what-can-this-sdk-do-function-reference).
- Each method has a PHPDoc block (arguments and return shape) in `src/Resources/`.
- Multi-tenant app? `$irnoti->withApiKey($customerKey)`.
- Testing: pass a `transport` callable to fake HTTP.
