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

## Quickstart

New here? Follow **[QUICKSTART.md](QUICKSTART.md)**: install, get a key, send your first (free, sandbox) SMS in 5 minutes.

## What can this SDK do? (function reference)

Everything hangs off one client. Each row below is a *resource*, and links to its section in the API docs at [irnoti.com/docs](https://irnoti.com/docs) (open the docs panel and look for the section named in the "Docs section" column).

| Resource | Docs section | What it is for | Functions |
|---|---|---|---|
| `$irnoti->sms()` | SMS | Send text messages | `send` (single / bulk / group / scheduled / gradual), `quote` (exact price, no send), `pattern` (approved OTP/transactional template), `sos` (guaranteed delivery with failover) |
| `$irnoti->messages()` | SMS → Messages | Look up sent messages | `list`, `get`, `check` |
| `$irnoti->batches()` | SMS → Batches | Control scheduled/gradual sends | `scheduled`, `cancel`, `pause`, `resume` |
| `$irnoti->lines()` | SMS → Lines | Your sender numbers (you need a line id to send) | `list` |
| `$irnoti->wallet()` | Wallet | Balance and history | `balance`, `transactions` |
| `$irnoti->phonebook()` | Phonebook | Contact groups and contacts | `groups`, `createGroup`, `deleteGroup`, `contacts`, `addContacts`, `importCsv`, `updateContact`, `deleteContact` |
| `$irnoti->services()` | Services | Provider products: `regional`, `targeted`, `peyamap`, `smart`, `messenger`, `voice`, `international` | `prices`, `internationalRates`, `list`, `quote`, `create`, `get`, `refresh`, `cancel`, `regional`, `targeted`, `peyamap`, `smart`, `messenger`, `voice`, `international` |
| `$irnoti->linkTracker()` | Link tracker | SMS with tracked links, A/B variants, holdout | `send`, `holdouts`, `sendNow`, `campaigns`, `campaign` |
| `$irnoti->numberBanks()` | Number banks | Send to purchased/filtered number banks | `list`, `facets`, `quote`, `send` |
| `$irnoti->verify()` | Verify | OTP verification sessions (SMS or bot) | `info`, `create`, `list`, `get`, `cancel`, `stats` |
| `$irnoti->bots()` | Bot builder | Read bots, send messages/OTP through them (creating bots is panel-only) | `info`, `list`, `get`, `stats`, `subscribers`, `messages`, `send`, `otp`, `shopOrders`, `shopStats` |
| `$irnoti->broker()` | Telegram broker | Run Telegram bots through a private relay | `list`, `get`, `create`, `rename`, `replaceToken`, `rotate`, `delete`, `request`, `updates`, `webhook`, `setWebhook`, `deleteWebhook` |
| `$irnoti->voip()` | VoIP | Click-to-call | `calls`, `price`, `call` |

Top-level helpers:

| Function | What it does |
|---|---|
| `new IrnotiClient($apiKey, ...)` | Create the client |
| `$irnoti->withApiKey($key)` | New independent client for another customer key (multi-tenant) |
| `Webhook::verify($rawBody, $signature, $secret)` | `true`/`false`: is this webhook really from irnoti? |
| `Webhook::parse($rawBody, $signature, $secret)` | Verify and return the decoded event; fails if the signature is bad |
| `IrnotiApiException` | Raised/returned on any non-2xx answer; has `statusCode()`, `errorCode()`, `details()` and helpers like `isInsufficientBalance()` |

Every function is a thin wrapper over one HTTP endpoint and returns the API's JSON. Each method has a PHPDoc block with arguments and return shape; see `src/Resources/`.

> Typical flow: `lines()->list()` → `sms()->quote()` → `sms()->send()` (with `idempotencyKey`) → `messages()->get()` / webhook. Use an `irn_test_…` key and line id `sandbox` while experimenting: nothing is sent or charged.

### Telegram broker

```php
$bot = $irnoti->broker()->create('Shop bot', $botFatherToken);   // scope broker.write (account owner); returns the relay `link`
$irnoti->broker()->request($bot['id'], 'sendMessage', ['chat_id' => 123, 'text' => 'Hi']); // scope broker.request
$irnoti->broker()->updates($bot['id'], limit: 20);               // pull mode
$irnoti->broker()->setWebhook($bot['id'], 'https://example.com/tg', secretToken: 's3cret'); // push mode
```

## Behaviour shared by all irnoti SDKs

- **Auth:** `Authorization: Bearer irn_live_…`. Use an `irn_test_…` key with `lineId: "sandbox"` to try sends for free. Keys carry scopes (`sms.send`, `sms.read`, `wallet.read`, `phonebook.read|write`, `bot.send|read`, `broker.read|write|request`, `verify.send|read`); a missing scope is a `403`.
- **Money** is integer **Rial** returned as a **string** (`"1960"`) — never parse it into a float.
- **Idempotency:** every send takes an idempotency key; retrying with the same key never sends twice (`replayed: true`). Automatic retries apply only to `GET`s and sends that carry a key.
- **Errors:** one error type carrying `status`, the API's machine `code` (`insufficient_balance`, `invalid_recipients`, `no_price`, … — empty for plain 401/403/404) and `details`, with helpers such as `isInsufficientBalance` / `isRateLimited` / `isUnauthenticated`.
- **Limits:** send 600 req/min, pattern 1200 req/min per IP (`429` when exceeded).
- **Webhooks:** verify the **raw** body against `X-Irnoti-Signature: t=<unix>,v1=<hex>` (HMAC-SHA256 of `"<t>.<body>"` with your `whsec_…` key); signatures older than 5 minutes are rejected.
- Bot-builder creation/configuration and verify settings are panel-only; Telegram broker bots can be managed through the API.

Sibling SDKs: [Laravel](https://github.com/irnoti/laravel-sms-panel-api) · [Node.js](https://github.com/irnoti/node-sms-panel-api) · [Python](https://github.com/irnoti/python-sms-panel-api) · [Go](https://github.com/irnoti/go-sms-panel-api) · [PHP](https://github.com/irnoti/php-sms-panel-api). API reference: [irnoti.com/docs](https://irnoti.com/docs).

MIT licensed.
