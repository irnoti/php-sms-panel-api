<?php

namespace Irnoti\Sdk;

use Irnoti\Sdk\Exceptions\IrnotiApiException;
use Irnoti\Sdk\Resources\BatchesResource;
use Irnoti\Sdk\Resources\BotsResource;
use Irnoti\Sdk\Resources\LinesResource;
use Irnoti\Sdk\Resources\LinkTrackerResource;
use Irnoti\Sdk\Resources\MessagesResource;
use Irnoti\Sdk\Resources\NumberBanksResource;
use Irnoti\Sdk\Resources\PhonebookResource;
use Irnoti\Sdk\Resources\ServicesResource;
use Irnoti\Sdk\Resources\SmsResource;
use Irnoti\Sdk\Resources\VerifyResource;
use Irnoti\Sdk\Resources\VoipResource;
use Irnoti\Sdk\Resources\WalletResource;

/**
 * Framework-agnostic client (ext-curl + ext-json). For Laravel, prefer irnoti/laravel-sms-panel-api.
 *
 * The transport can be replaced (tests, Guzzle, PSR-18 adapters) by passing a callable
 * `fn (string $method, string $url, array $headers, ?string $body): array{0:int,1:string}`
 * returning [status, rawBody].
 */
class IrnotiClient
{
    private const API_PREFIX = '/v1';

    /** @var callable */
    private $transport;

    public function __construct(
        protected string $apiKey,
        protected string $baseUrl = 'https://api.irnoti.com',
        protected int $timeout = 15,
        protected int $retries = 1,
        ?callable $transport = null,
    ) {
        if ($apiKey === '') {
            throw new \InvalidArgumentException('irnoti: apiKey is required');
        }
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->transport = $transport ?? $this->curlTransport(...);
    }

    public function withApiKey(string $apiKey): self
    {
        return new self($apiKey, $this->baseUrl, $this->timeout, $this->retries, $this->transport);
    }

    public function sms(): SmsResource { return new SmsResource($this); }
    public function messages(): MessagesResource { return new MessagesResource($this); }
    public function batches(): BatchesResource { return new BatchesResource($this); }
    public function lines(): LinesResource { return new LinesResource($this); }
    public function wallet(): WalletResource { return new WalletResource($this); }
    public function phonebook(): PhonebookResource { return new PhonebookResource($this); }
    public function services(): ServicesResource { return new ServicesResource($this); }
    public function linkTracker(): LinkTrackerResource { return new LinkTrackerResource($this); }
    public function numberBanks(): NumberBanksResource { return new NumberBanksResource($this); }
    public function verify(): VerifyResource { return new VerifyResource($this); }
    public function bots(): BotsResource { return new BotsResource($this); }
    public function voip(): VoipResource { return new VoipResource($this); }

    /**
     * @param  array<string, mixed>  $payload  Query string for GET, JSON body otherwise.
     * @param  array<string, string>  $headers
     * @return array<int|string, mixed>
     *
     * @throws IrnotiApiException on any non-2xx response.
     */
    public function request(string $method, string $path, array $payload = [], array $headers = []): array
    {
        $method = strtoupper($method);
        $url = $this->baseUrl . self::API_PREFIX . $path;
        $body = null;
        if ($method === 'GET') {
            $url .= $payload ? '?' . http_build_query($payload) : '';
        } else {
            $body = json_encode($payload === [] ? new \stdClass() : $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->send($method, $url, $body === null ? [] : ['Content-Type' => 'application/json'], $body, $headers);
    }

    /**
     * Multipart file upload (e.g. phonebook CSV import).
     *
     * @return array<int|string, mixed>
     */
    public function upload(string $path, string $field, string $contents, string $filename): array
    {
        $boundary = bin2hex(random_bytes(16));
        $body = "--{$boundary}\r\nContent-Disposition: form-data; name=\"{$field}\"; filename=\"{$filename}\"\r\nContent-Type: text/csv\r\n\r\n{$contents}\r\n--{$boundary}--\r\n";

        return $this->send('POST', $this->baseUrl . self::API_PREFIX . $path, ['Content-Type' => "multipart/form-data; boundary={$boundary}"], $body, []);
    }

    /**
     * @param  array<string, string>  $headers  Content headers.
     * @param  array<string, string>  $extra    Caller headers (Idempotency-Key).
     * @return array<int|string, mixed>
     */
    private function send(string $method, string $url, array $headers, ?string $body, array $extra): array
    {
        $headers = $extra + $headers + ['Authorization' => 'Bearer ' . $this->apiKey, 'Accept' => 'application/json'];
        // Only calls safe to repeat are retried: GETs and sends carrying an Idempotency-Key.
        $attempts = 1 + ($method === 'GET' || isset($headers['Idempotency-Key']) ? $this->retries : 0);

        for ($i = 0; $i < $attempts; $i++) {
            try {
                [$status, $raw] = ($this->transport)($method, $url, $headers, $body);
            } catch (\RuntimeException $e) {
                if ($i === $attempts - 1) {
                    throw $e;
                }
                continue;
            }
            if ($status >= 500 && $i < $attempts - 1) {
                continue;
            }
            if ($status < 200 || $status >= 300) {
                throw IrnotiApiException::fromResponse($status, $raw);
            }
            $decoded = $raw === '' ? [] : json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        throw new \LogicException('unreachable');
    }

    /**
     * @param  array<string, string>  $headers
     * @return array{0: int, 1: string}
     */
    private function curlTransport(string $method, string $url, array $headers, ?string $body): array
    {
        $ch = curl_init($url);
        $lines = [];
        foreach ($headers as $k => $v) {
            $lines[] = "{$k}: {$v}";
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('irnoti: ' . $err);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [$status, (string) $raw];
    }
}
