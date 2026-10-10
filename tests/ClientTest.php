<?php

namespace Irnoti\Sdk\Tests;

use Irnoti\Sdk\Exceptions\IrnotiApiException;
use Irnoti\Sdk\IrnotiClient;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /** @var array<int, array{string, string, array<string, string>, ?string}> */
    private array $calls = [];

    private function client(int $status = 200, string $body = '{}'): IrnotiClient
    {
        return new IrnotiClient('irn_test_x', 'https://irnoti.test/', retries: 0, transport: function ($m, $u, $h, $b) use ($status, $body) {
            $this->calls[] = [$m, $u, $h, $b];

            return [$status, $body];
        });
    }

    public function test_send_request_shape(): void
    {
        $r = $this->client(201, '{"batchId":"1","count":2,"totalPrice":"1960","replayed":false}')
            ->sms()->send(lineId: 1, text: 'hi', to: ['0912'], groupIds: [12], idempotencyKey: 'k1');

        $this->assertSame('1960', $r['totalPrice']);
        [$m, $u, $h, $b] = $this->calls[0];
        $this->assertSame('POST', $m);
        $this->assertSame('https://irnoti.test/v1/sms/send', $u);
        $this->assertSame('Bearer irn_test_x', $h['Authorization']);
        $this->assertSame('k1', $h['Idempotency-Key']);
        $this->assertSame('application/json', $h['Content-Type']);
        $this->assertSame(['lineId' => '1', 'to' => ['0912'], 'groupIds' => ['12'], 'text' => 'hi'], json_decode($b, true));
    }

    public function test_get_query_and_empty_params_object(): void
    {
        $c = $this->client(200, '[]');
        $c->messages()->list(take: 2, before: 90);
        $c->sms()->pattern('k3f9a2', '0912');
        $this->assertSame('https://irnoti.test/v1/sms/messages?take=2&before=90', $this->calls[0][1]);
        $this->assertNull($this->calls[0][3]);
        $this->assertStringContainsString('"params":{}', $this->calls[1][3]);
    }

    public function test_errors(): void
    {
        try {
            $this->client(400, '{"statusCode":400,"code":"invalid_recipients","details":["0912"]}')->sms()->send(1, 'x', '0912');
            $this->fail('expected exception');
        } catch (IrnotiApiException $e) {
            $this->assertTrue($e->isInvalidRecipients());
            $this->assertSame(['0912'], $e->details());
        }
        try {
            $this->client(401, '{"statusCode":401,"message":"Invalid API key"}')->wallet()->balance();
            $this->fail('expected exception');
        } catch (IrnotiApiException $e) {
            $this->assertTrue($e->isUnauthenticated());
            $this->assertSame('', $e->errorCode());
            $this->assertSame('Invalid API key', $e->getMessage());
        }
    }

    public function test_no_content_csv_and_with_api_key(): void
    {
        $this->client(204, '')->batches()->pause(7);
        $this->assertSame('https://irnoti.test/v1/sms/batches/7/pause', $this->calls[0][1]);

        $r = $this->client(200, '{"added":1,"duplicates":0,"invalid":0}')->phonebook()->importCsv(12, "0912,Ali\n");
        $this->assertSame(1, $r['added']);
        $this->assertStringStartsWith('multipart/form-data; boundary=', $this->calls[1][2]['Content-Type']);
        $this->assertStringContainsString('0912,Ali', $this->calls[1][3]);

        $c = $this->client();
        $this->assertNotSame($c, $c->withApiKey('irn_live_y'));
    }
}
