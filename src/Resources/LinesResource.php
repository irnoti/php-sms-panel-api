<?php

namespace Irnoti\Sdk\Resources;

class LinesResource extends Resource
{
    /**
     * Sender lines you may use as `lineId` (your dedicated lines + shared lines). Scope: `sms.read`.
     *
     * @return array<int, array{id: string, number: string, kind: string}>
     */
    public function list(): array
    {
        return $this->client->request('get', '/sms/lines');
    }
}
