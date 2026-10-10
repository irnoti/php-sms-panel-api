<?php

namespace Irnoti\Sdk\Resources;

/**
 * Click-to-call. Read: `sms.read`; place a call: `sms.send` (needs the `voip` feature).
 */
class VoipResource extends Resource
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function calls(): array
    {
        return $this->client->request('get', '/voip/calls');
    }

    /**
     * Per-minute price (Rial).
     *
     * @return array<string, mixed>
     */
    public function price(): array
    {
        return $this->client->request('get', '/voip/price');
    }

    /**
     * Ring `$from` first, then bridge to `$to`.
     *
     * @return array<string, mixed>
     */
    public function call(string $from, string $to): array
    {
        return $this->client->request('post', '/voip/calls', ['from' => $from, 'to' => $to]);
    }
}
