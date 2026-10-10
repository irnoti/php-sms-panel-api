<?php

namespace Irnoti\Sdk\Resources;

/**
 * Provider-side send modes, messenger and voice campaigns — all share one order flow:
 * quote → create → poll/refresh → (cancel). Read scope `sms.read`, write scope `sms.send`;
 * each product also needs its feature enabled for your account.
 *
 * Products: regional, targeted, peyamap, smart, messenger, voice, international.
 */
class ServicesResource extends Resource
{
    public const REGIONAL = 'regional';
    public const TARGETED = 'targeted';
    public const PEYAMAP = 'peyamap';
    public const SMART = 'smart';
    public const MESSENGER = 'messenger';
    public const VOICE = 'voice';
    public const INTERNATIONAL = 'international';

    /**
     * Unit price per product for your account (Rial, strings).
     *
     * @return array<int, array<string, mixed>>
     */
    public function prices(): array
    {
        return $this->client->request('get', '/services/prices');
    }

    /**
     * Destinations on offer for `international` and their price per SMS part.
     *
     * @return array<int|string, mixed>
     */
    public function internationalRates(): array
    {
        return $this->client->request('get', '/services/international/rates');
    }

    /**
     * Your orders of one product.
     *
     * @return array<int, array<string, mixed>>
     */
    public function list(string $product): array
    {
        return $this->client->request('get', '/services/' . $product);
    }

    /**
     * Price an order without creating it.
     *
     * Body per product:
     *  - regional:      province, city?, count (100–1,000,000), text, scheduledAt?
     *  - targeted:      gender?, ageFrom?, ageTo?, province?, city?, operator?, job? (≥1 filter), count, text, scheduledAt?
     *  - peyamap:       operator (mci|irancell), areas [{name, count}], text, scheduledAt?
     *  - smart:         recipients[] (≤10,000), text
     *  - messenger:     channel (bale|eitaa|whatsapp), recipients[], text
     *  - voice:         recipients[], text (3–300 chars)
     *  - international: recipients[], text
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function quote(string $product, array $body): array
    {
        return $this->client->request('post', '/services/' . $product . '/quote', $body);
    }

    /**
     * Place an order; the cost is reserved from the wallet immediately.
     *
     * @param  array<string, mixed>  $body  Same shape as {@see quote()}.
     * @return array<string, mixed>
     */
    public function create(string $product, array $body): array
    {
        return $this->client->request('post', '/services/' . $product, $body);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $product, string|int $id): array
    {
        return $this->client->request('get', '/services/' . $product . '/' . rawurlencode((string) $id));
    }

    /**
     * Pull the latest progress from the provider.
     *
     * @return array<string, mixed>
     */
    public function refresh(string $product, string|int $id): array
    {
        return $this->client->request('post', '/services/' . $product . '/' . rawurlencode((string) $id) . '/refresh');
    }

    /**
     * Recall an order the provider has not started yet (refunded).
     *
     * @return array<string, mixed>
     */
    public function cancel(string $product, string|int $id): array
    {
        return $this->client->request('post', '/services/' . $product . '/' . rawurlencode((string) $id) . '/cancel');
    }

    /** @param array<string, mixed> $body @return array<string, mixed> */
    public function regional(array $body): array
    {
        return $this->create(self::REGIONAL, $body);
    }

    /** @param array<string, mixed> $body @return array<string, mixed> */
    public function targeted(array $body): array
    {
        return $this->create(self::TARGETED, $body);
    }

    /** @param array<string, mixed> $body @return array<string, mixed> */
    public function peyamap(array $body): array
    {
        return $this->create(self::PEYAMAP, $body);
    }

    /**
     * @param  array<int, string>  $recipients
     * @return array<string, mixed>
     */
    public function smart(array $recipients, string $text): array
    {
        return $this->create(self::SMART, ['recipients' => $recipients, 'text' => $text]);
    }

    /**
     * @param  array<int, string>  $recipients
     * @return array<string, mixed>
     */
    public function messenger(string $channel, array $recipients, string $text): array
    {
        return $this->create(self::MESSENGER, ['channel' => $channel, 'recipients' => $recipients, 'text' => $text]);
    }

    /**
     * @param  array<int, string>  $recipients
     * @return array<string, mixed>
     */
    public function voice(array $recipients, string $text): array
    {
        return $this->create(self::VOICE, ['recipients' => $recipients, 'text' => $text]);
    }

    /**
     * @param  array<int, string>  $recipients
     * @return array<string, mixed>
     */
    public function international(array $recipients, string $text): array
    {
        return $this->create(self::INTERNATIONAL, ['recipients' => $recipients, 'text' => $text]);
    }
}
