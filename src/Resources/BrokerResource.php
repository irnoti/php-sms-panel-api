<?php

namespace Irnoti\Sdk\Resources;

/**
 * Telegram broker: register a bot token, get a private relay link, call the Bot API and pull updates through it.
 * Scopes: `broker.read`, `broker.write` (account owner), `broker.request`.
 */
class BrokerResource extends Resource
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return $this->client->request('get', '/broker/bots');
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string|int $id): array
    {
        return $this->client->request('get', $this->path($id));
    }

    /**
     * The token is never returned afterwards; the response carries the relay `link`.
     *
     * @return array<string, mixed>
     */
    public function create(string $label, string $token): array
    {
        return $this->client->request('post', '/broker/bots', ['label' => $label, 'token' => $token]);
    }

    public function rename(string|int $id, string $label): void
    {
        $this->client->request('patch', $this->path($id), ['label' => $label]);
    }

    /**
     * Swap in a regenerated BotFather token. Clears the return webhook.
     *
     * @return array<string, mixed>
     */
    public function replaceToken(string|int $id, string $token): array
    {
        return $this->client->request('put', $this->path($id) . '/token', ['token' => $token]);
    }

    /**
     * New relay link; the previous one stops working.
     *
     * @return array<string, mixed>
     */
    public function rotate(string|int $id): array
    {
        return $this->client->request('post', $this->path($id) . '/rotate');
    }

    public function delete(string|int $id): void
    {
        $this->client->request('delete', $this->path($id));
    }

    /**
     * Call any Telegram Bot API method. Returns `{status, response}`.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function request(string|int $id, string $method, array $params = []): array
    {
        return $this->client->request('post', $this->path($id) . '/request', ['method' => $method, 'params' => $params === [] ? new \stdClass() : $params]);
    }

    /**
     * Pull updates (long-poll `$timeout` up to 25 s).
     *
     * @param  array<int, string>|null  $allowedUpdates
     * @return array<string, mixed>
     */
    public function updates(string|int $id, ?int $offset = null, ?int $limit = null, ?int $timeout = null, ?array $allowedUpdates = null): array
    {
        return $this->client->request('post', $this->path($id) . '/updates', $this->filter([
            'offset' => $offset,
            'limit' => $limit,
            'timeout' => $timeout,
            'allowedUpdates' => $allowedUpdates,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function webhook(string|int $id): array
    {
        return $this->client->request('get', $this->path($id) . '/webhook');
    }

    /**
     * @param  array<int, string>|null  $allowedUpdates
     * @return array<string, mixed>
     */
    public function setWebhook(string|int $id, string $url, ?string $secretToken = null, ?array $allowedUpdates = null, ?bool $dropPendingUpdates = null): array
    {
        return $this->client->request('put', $this->path($id) . '/webhook', $this->filter([
            'url' => $url,
            'secretToken' => $secretToken,
            'allowedUpdates' => $allowedUpdates,
            'dropPendingUpdates' => $dropPendingUpdates,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function deleteWebhook(string|int $id, ?bool $dropPendingUpdates = null): array
    {
        return $this->client->request('delete', $this->path($id) . '/webhook', $this->filter(['dropPendingUpdates' => $dropPendingUpdates]));
    }

    private function path(string|int $id): string
    {
        return '/broker/bots/' . rawurlencode((string) $id);
    }
}
