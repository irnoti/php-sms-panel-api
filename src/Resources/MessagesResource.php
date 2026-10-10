<?php

namespace Irnoti\Sdk\Resources;

/**
 * Delivery archive. Statuses: `queued`, `sent`, `delivered`, `failed`. Scope: `sms.read`.
 */
class MessagesResource extends Resource
{
    /**
     * Newest first. Page backwards with `before` = id of the last message of the previous page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(?int $take = null, string|int|null $before = null): array
    {
        return $this->client->request('get', '/sms/messages', $this->filter([
            'take' => $take,
            'before' => $before === null ? null : (string) $before,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string|int $id): array
    {
        return $this->client->request('get', '/sms/messages/' . rawurlencode((string) $id));
    }

    /**
     * Ask the operator for the final delivery state right now (it is also polled in the background). 30/min.
     *
     * @return array<string, mixed>
     */
    public function check(string|int $id): array
    {
        return $this->client->request('post', '/sms/messages/' . rawurlencode((string) $id) . '/check');
    }
}
