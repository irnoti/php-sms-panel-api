<?php

namespace Irnoti\Sdk\Resources;

/**
 * Scope: `wallet.read`. Amounts are integer Rial values encoded as strings.
 */
class WalletResource extends Resource
{
    /**
     * @return array{balance: string, currency: string}
     */
    public function balance(): array
    {
        return $this->client->request('get', '/wallet');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function transactions(): array
    {
        return $this->client->request('get', '/wallet/transactions');
    }
}
