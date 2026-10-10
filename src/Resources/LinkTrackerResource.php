<?php

namespace Irnoti\Sdk\Resources;

/**
 * Click-tracked SMS: one unique short link per recipient, with optional A/B text variants and a holdout group.
 */
class LinkTrackerResource extends Resource
{
    /**
     * Scope: `sms.send` (KYC + `sms.link_tracker` feature required).
     *
     * @param  array<int, string>  $variants  Up to 2 alternative texts for A/B testing.
     * @param  array<int, string>  $to
     * @param  array<int, int|string>  $groupIds
     * @return array<string, mixed>
     */
    public function send(
        string|int $lineId,
        string $text,
        string $url,
        array $to = [],
        array $groupIds = [],
        array $variants = [],
        ?int $holdoutPercent = null,
        ?int $decideAfterMinutes = null,
    ): array {
        return $this->client->request('post', '/link-tracker/send', $this->filter([
            'lineId' => (string) $lineId,
            'text' => $text,
            'url' => $url,
            'to' => $to ?: null,
            'groupIds' => $groupIds ? array_map('strval', $groupIds) : null,
            'variants' => $variants ?: null,
            'holdoutPercent' => $holdoutPercent,
            'decideAfterMinutes' => $decideAfterMinutes,
        ]));
    }

    /**
     * Campaigns whose holdout group is still waiting for the winning variant. Scope: `sms.read`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function holdouts(): array
    {
        return $this->client->request('get', '/link-tracker/holdouts');
    }

    /**
     * Release the holdout group now. Scope: `sms.send`.
     *
     * @return array<string, mixed>
     */
    public function sendNow(string|int $holdoutId): array
    {
        return $this->client->request('post', '/link-tracker/holdouts/' . rawurlencode((string) $holdoutId) . '/send-now');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function campaigns(): array
    {
        return $this->client->request('get', '/link-tracker/campaigns');
    }

    /**
     * Per-campaign click analytics.
     *
     * @return array<string, mixed>
     */
    public function campaign(string|int $batchId): array
    {
        return $this->client->request('get', '/link-tracker/campaigns/' . rawurlencode((string) $batchId));
    }
}
