<?php

namespace Irnoti\Sdk\Resources;

use Irnoti\Sdk\IrnotiClient;

abstract class Resource
{
    public function __construct(protected IrnotiClient $client)
    {
    }

    /**
     * Drop null values so optional fields are simply omitted from the
     * request instead of being sent as explicit nulls.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function filter(array $payload): array
    {
        return array_filter($payload, static fn ($value) => $value !== null);
    }

    /**
     * @return array<string, string>
     */
    protected function idempotency(?string $key): array
    {
        return $key === null ? [] : ['Idempotency-Key' => $key];
    }
}
