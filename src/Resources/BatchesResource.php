<?php

namespace Irnoti\Sdk\Resources;

/**
 * Scheduled and gradual batches created by `sms()->send()`.
 */
class BatchesResource extends Resource
{
    /**
     * Pending scheduled sends. Scope: `sms.read`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function scheduled(): array
    {
        return $this->client->request('get', '/sms/scheduled');
    }

    /**
     * Cancel before it fires; for a gradual batch only the not-yet-released remainder is refunded. Scope: `sms.send`.
     *
     * @return array<string, mixed>
     */
    public function cancel(string|int $batchId): array
    {
        return $this->client->request('post', '/sms/batches/' . rawurlencode((string) $batchId) . '/cancel');
    }

    /** Pause a gradual batch. Scope: `sms.send`. */
    public function pause(string|int $batchId): void
    {
        $this->client->request('post', '/sms/batches/' . rawurlencode((string) $batchId) . '/pause');
    }

    /** Resume a paused gradual batch. Scope: `sms.send`. */
    public function resume(string|int $batchId): void
    {
        $this->client->request('post', '/sms/batches/' . rawurlencode((string) $batchId) . '/resume');
    }
}
