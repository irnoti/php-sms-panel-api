<?php

namespace Irnoti\Sdk\Support;

use InvalidArgumentException;

/**
 * Verifies delivery webhooks (`message.sent`, `message.delivered`, `message.failed`).
 *
 * irnoti signs `"{t}.{raw body}"` with HMAC-SHA256 using your `whsec_…` signing key and sends it as
 * `X-Irnoti-Signature: t=<unix>,v1=<hex>`. Always verify the RAW request body (before JSON-decoding).
 */
final class Webhook
{
    /**
     * @param  int  $tolerance  Max accepted clock skew in seconds, to reject replays.
     */
    public static function verify(string $rawBody, string $signatureHeader, string $secret, int $tolerance = 300): bool
    {
        if (! preg_match('/^t=(\d+),v1=([0-9a-f]{64})$/', $signatureHeader, $m)) {
            return false;
        }

        if (abs(time() - (int) $m[1]) > $tolerance) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $m[1] . '.' . $rawBody, $secret), $m[2]);
    }

    /**
     * Verify and decode in one step.
     *
     * @return array{id: string, event: string, createdAt: string, data: array<string, mixed>}
     *
     * @throws InvalidArgumentException when the signature is invalid or the body is not JSON.
     */
    public static function parse(string $rawBody, string $signatureHeader, string $secret, int $tolerance = 300): array
    {
        if (! self::verify($rawBody, $signatureHeader, $secret, $tolerance)) {
            throw new InvalidArgumentException('Invalid irnoti webhook signature.');
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            throw new InvalidArgumentException('Webhook body is not valid JSON.');
        }

        return $payload;
    }
}
