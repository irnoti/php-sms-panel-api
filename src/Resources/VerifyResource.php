<?php

namespace Irnoti\Sdk\Resources;

/**
 * Link/OTP verification sessions. Create/cancel: `verify.send`; read: `verify.read`.
 */
class VerifyResource extends Resource
{
    /**
     * Price per session (Rial string).
     *
     * @return array{price: string}
     */
    public function info(): array
    {
        return $this->client->request('get', '/verify/info');
    }

    /**
     * Start a verification for a mobile (`to`) or a bot user (`bot` = ['id' => …, 'ref' => …]) — exactly one of them.
     *
     * Options: lineId, mode, length (4–8), ttlSec (60–1800), maxAttempts (3–10), redirectUrl, callbackUrl,
     * clientRef, brandName, brandColor (#RRGGBB).
     *
     * @param  array<string, mixed>  $options
     * @param  array{id: string|int, ref: string}|null  $bot
     * @return array<string, mixed>
     */
    public function create(?string $to = null, ?array $bot = null, array $options = []): array
    {
        return $this->client->request('post', '/verify/sessions', $this->filter([...$options, 'to' => $to, 'bot' => $bot]));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?int $take = null, string|int|null $before = null): array
    {
        return $this->client->request('get', '/verify/sessions', $this->filter([
            'take' => $take,
            'before' => $before === null ? null : (string) $before,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string|int $id): array
    {
        return $this->client->request('get', '/verify/sessions/' . rawurlencode((string) $id));
    }

    public function cancel(string|int $id): void
    {
        $this->client->request('post', '/verify/sessions/' . rawurlencode((string) $id) . '/cancel');
    }

    /**
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        return $this->client->request('get', '/verify/stats');
    }
}
