<?php

namespace Irnoti\Sdk\Resources;

/**
 * Buy-and-send to a targeted slice of a number bank. Read: `sms.read`; send: `sms.send` (KYC required).
 */
class NumberBanksResource extends Resource
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return $this->client->request('get', '/number-banks');
    }

    /**
     * Available filter values (operators, provinces, cities, tags) for one bank.
     *
     * @return array<string, mixed>
     */
    public function facets(string|int $bankId): array
    {
        return $this->client->request('get', '/number-banks/' . rawurlencode((string) $bankId) . '/facets');
    }

    /**
     * @param  array{operator?: string, province?: string, city?: string, tag?: string}  $filters
     * @return array<string, mixed>
     */
    public function quote(string|int $bankId, int $count, string|int|null $lineId, string $text, array $filters = []): array
    {
        return $this->client->request('post', '/number-banks/quote', $this->body($bankId, $count, $lineId, $text, $filters));
    }

    /**
     * @param  array{operator?: string, province?: string, city?: string, tag?: string}  $filters
     * @return array<string, mixed>
     */
    public function send(string|int $bankId, int $count, string|int|null $lineId, string $text, array $filters = [], ?string $idempotencyKey = null): array
    {
        return $this->client->request('post', '/number-banks/send', $this->body($bankId, $count, $lineId, $text, $filters), $this->idempotency($idempotencyKey));
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    private function body(string|int $bankId, int $count, string|int|null $lineId, string $text, array $filters): array
    {
        return array_filter([
            'bankId' => (string) $bankId,
            'count' => $count,
            'lineId' => $lineId === null || $lineId === '' ? null : (string) $lineId,
            'text' => $text,
            'filters' => (object) $filters,
        ], static fn ($v) => $v !== null);
    }
}
