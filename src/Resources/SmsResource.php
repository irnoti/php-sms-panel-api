<?php

namespace Irnoti\Sdk\Resources;

/**
 * Sending. Money values in responses are integer Rial amounts encoded as strings.
 *
 * Every send accepts an `$idempotencyKey` (max 100 chars): retrying with the same key
 * never creates a duplicate and returns the original result with `replayed: true`.
 */
class SmsResource extends Resource
{
    /**
     * Send one text to one or many recipients and/or phonebook groups. Scope: `sms.send`.
     *
     * Pass `lineId: 'sandbox'` with an `irn_test_…` key to try the API for free.
     * Give `scheduledAt` (1 minute – 60 days ahead) to send later, and/or `gradual`
     * (`['chunkSize' => 500, 'intervalMinutes' => 10]`) to drip the batch out.
     *
     * @param  string|array<int, string>  $to  Number(s) — `0912…`, `+98912…` and Persian digits are accepted (max 10,000).
     * @param  array<int, int|string>  $groupIds  Phonebook group ids (opted-out contacts are skipped).
     * @param  \DateTimeInterface|string|null  $scheduledAt
     * @param  array{chunkSize: int, intervalMinutes: int}|null  $gradual
     * @return array{batchId: string, count: int, totalPrice: string, replayed: bool}
     */
    public function send(
        string|int $lineId,
        string $text,
        string|array|null $to = null,
        array $groupIds = [],
        \DateTimeInterface|string|null $scheduledAt = null,
        ?array $gradual = null,
        ?string $idempotencyKey = null,
    ): array {
        return $this->client->request('post', '/sms/send', $this->filter([
            'lineId' => (string) $lineId,
            'to' => $to,
            'groupIds' => $groupIds ? array_map('strval', $groupIds) : null,
            'text' => $text,
            'scheduledAt' => $scheduledAt instanceof \DateTimeInterface ? $scheduledAt->format(DATE_ATOM) : $scheduledAt,
            'gradual' => $gradual,
        ]), $this->idempotency($idempotencyKey));
    }

    /**
     * Exact cost of a send, without sending or charging. Scope: `sms.read`.
     *
     * @param  string|array<int, string>|null  $to
     * @param  array<int, int|string>  $groupIds
     * @return array{count: int, parts: int, totalPrice: string}
     */
    public function quote(string|int $lineId, string $text, string|array|null $to = null, array $groupIds = []): array
    {
        return $this->client->request('post', '/sms/quote', $this->filter([
            'lineId' => (string) $lineId,
            'to' => $to,
            'groupIds' => $groupIds ? array_map('strval', $groupIds) : null,
            'text' => $text,
        ]));
    }

    /**
     * Send an approved template («پترن») to one number — OTPs and transactional messages. Scope: `sms.send`.
     *
     * @param  array<string, scalar>  $params  Values for every `{variable}` in the template.
     * @return array{batchId: string, count: int, totalPrice: string, replayed: bool}
     */
    public function pattern(string $code, string $to, array $params = [], ?string $idempotencyKey = null): array
    {
        return $this->client->request('post', '/sms/pattern', [
            'code' => $code,
            'to' => $to,
            'params' => (object) $params,
        ], $this->idempotency($idempotencyKey));
    }

    /**
     * Guaranteed delivery for critical alerts: sent over the platform's own routes with
     * automatic provider failover (up to 200 recipients, separate `sms.sos` tariff). Scope: `sms.send`.
     *
     * @param  string|array<int, string>  $to
     * @return array{batchId: string, count: int, totalPrice: string, replayed: bool}
     */
    public function sos(string|array $to, string $text, ?string $idempotencyKey = null): array
    {
        return $this->client->request('post', '/sms/sos', ['to' => $to, 'text' => $text], $this->idempotency($idempotencyKey));
    }
}
