<?php

namespace Irnoti\Sdk\Tests;

use InvalidArgumentException;
use Irnoti\Sdk\Support\Webhook;
use PHPUnit\Framework\TestCase;

class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test';

    private function sign(string $body, ?int $t = null): string
    {
        $t ??= time();

        return "t={$t},v1=" . hash_hmac('sha256', "{$t}.{$body}", self::SECRET);
    }

    public function test_valid_signature(): void
    {
        $body = '{"id":"1","event":"message.delivered","createdAt":"x","data":{}}';

        $this->assertTrue(Webhook::verify($body, $this->sign($body), self::SECRET));
        $this->assertSame('message.delivered', Webhook::parse($body, $this->sign($body), self::SECRET)['event']);
    }

    public function test_tampered_body_stale_timestamp_and_garbage_are_rejected(): void
    {
        $body = '{"a":1}';

        $this->assertFalse(Webhook::verify('{"a":2}', $this->sign($body), self::SECRET));
        $this->assertFalse(Webhook::verify($body, $this->sign($body, time() - 3600), self::SECRET));
        $this->assertFalse(Webhook::verify($body, 'nonsense', self::SECRET));
        $this->assertFalse(Webhook::verify($body, $this->sign($body), 'whsec_other'));
    }

    public function test_parse_throws_on_bad_signature(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Webhook::parse('{}', 'bad', self::SECRET);
    }
}
