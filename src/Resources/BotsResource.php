<?php

namespace Irnoti\Sdk\Resources;

/**
 * Bot builder (Bale / Telegram bots you created in the panel). Send: `bot.send`; read: `bot.read`.
 * Creating and configuring bots is done in the panel, not through API keys.
 */
class BotsResource extends Resource
{
    /**
     * Pricing sheet: per-message price, bot limit, per-kind fees and character tiers.
     *
     * @return array<string, mixed>
     */
    public function info(): array
    {
        return $this->client->request('get', '/botbuilder/info');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return $this->client->request('get', '/botbuilder/bots');
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string|int $botId): array
    {
        return $this->client->request('get', '/botbuilder/bots/' . rawurlencode((string) $botId));
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(string|int $botId): array
    {
        return $this->client->request('get', '/botbuilder/bots/' . rawurlencode((string) $botId) . '/stats');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function subscribers(string|int $botId, ?int $take = null, string|int|null $before = null): array
    {
        return $this->client->request('get', '/botbuilder/bots/' . rawurlencode((string) $botId) . '/subscribers', $this->page($take, $before));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function messages(string|int $botId, ?int $take = null, string|int|null $before = null): array
    {
        return $this->client->request('get', '/botbuilder/bots/' . rawurlencode((string) $botId) . '/messages', $this->page($take, $before));
    }

    /**
     * Queue a message (HTTP 202). `$to` and `$message` follow the target/message shapes of the panel's bot sender.
     *
     * @param  array<string, mixed>  $to
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    public function send(string|int $botId, array $to, array $message, ?string $idempotencyKey = null): array
    {
        return $this->client->request('post', '/botbuilder/bots/' . rawurlencode((string) $botId) . '/send', [
            'to' => $to,
            'message' => $message,
        ], $this->idempotency($idempotencyKey));
    }

    /**
     * Send a one-time code through the bot (HTTP 202). `length` 4–8; `template` 3–500 chars.
     *
     * @param  array<string, mixed>  $to
     * @return array<string, mixed>
     */
    public function otp(string|int $botId, array $to, ?int $length = null, ?string $template = null, ?string $idempotencyKey = null): array
    {
        return $this->client->request('post', '/botbuilder/bots/' . rawurlencode((string) $botId) . '/otp', $this->filter([
            'to' => $to,
            'length' => $length,
            'template' => $template,
        ]), $this->idempotency($idempotencyKey));
    }

    /**
     * Shop-bot orders, optionally filtered by status (new, confirmed, shipped, done, canceled).
     *
     * @return array<int, array<string, mixed>>
     */
    public function shopOrders(string|int $botId, ?string $status = null): array
    {
        return $this->client->request('get', '/botbuilder/bots/' . rawurlencode((string) $botId) . '/shop/orders', $this->filter(['status' => $status]));
    }

    /**
     * @return array<string, mixed>
     */
    public function shopStats(string|int $botId): array
    {
        return $this->client->request('get', '/botbuilder/bots/' . rawurlencode((string) $botId) . '/shop/stats');
    }

    /**
     * @return array<string, mixed>
     */
    private function page(?int $take, string|int|null $before): array
    {
        return $this->filter(['take' => $take, 'before' => $before === null ? null : (string) $before]);
    }
}
