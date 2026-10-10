<?php

namespace Irnoti\Sdk\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Thrown for every non-2xx response the irnoti API returns.
 *
 * The HTTP status decides the class of failure; the body carries
 * `message` and, for domain errors, a stable machine-readable `code`
 * (e.g. "insufficient_balance", "invalid_recipients", "no_price") plus
 * optional `details` (invalid numbers, missing template params, per-field
 * validation errors).
 *
 * @see https://irnoti.com/docs
 */
class IrnotiApiException extends RuntimeException
{
    /**
     * @param  array<int|string, mixed>  $details
     */
    public function __construct(
        string $message,
        protected string $errorCode,
        protected int $statusCode,
        protected array $details = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(int $status, string $rawBody): self
    {
        $body = json_decode($rawBody, true);
        $body = is_array($body) ? $body : [];

        $message = $body['message'] ?? null;
        if (is_array($message)) {
            $message = implode(' ', array_map('strval', $message));
        }

        $details = $body['details'] ?? [];

        return new self(
            message: is_string($message) && $message !== '' ? $message : 'The irnoti API returned an error (HTTP ' . $status . ').',
            errorCode: (string) ($body['code'] ?? ''),
            statusCode: $status,
            details: is_array($details) ? $details : [],
        );
    }

    /**
     * The stable machine-readable code, or '' when the API sent only an HTTP status
     * (plain 401/403/404 answers carry no code).
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Extra error context: the invalid numbers for `invalid_recipients`, the missing
     * variables for `missing_params`, or the per-field bag for `validation_error`.
     *
     * @return array<int|string, mixed>
     */
    public function details(): array
    {
        return $this->details;
    }

    public function isValidationError(): bool
    {
        return $this->errorCode === 'validation_error' || $this->errorCode === 'invalid_recipients' || $this->errorCode === 'missing_params';
    }

    public function isInvalidRecipients(): bool
    {
        return $this->errorCode === 'invalid_recipients';
    }

    /** 402 — wallet balance cannot cover the send. */
    public function isInsufficientBalance(): bool
    {
        return $this->statusCode === 402 || $this->errorCode === 'insufficient_balance';
    }

    /** 422 `no_price` — no tariff is defined for this service in your user group. */
    public function isNoPrice(): bool
    {
        return $this->errorCode === 'no_price';
    }

    /** 429 — too many requests; back off and retry. */
    public function isRateLimited(): bool
    {
        return $this->statusCode === 429;
    }

    /** 401 — key invalid, revoked, expired, or the caller IP is not whitelisted. */
    public function isUnauthenticated(): bool
    {
        return $this->statusCode === 401;
    }

    /** 403 — key lacks the scope, KYC is incomplete, or the feature is disabled for the account. */
    public function isForbidden(): bool
    {
        return $this->statusCode === 403;
    }

    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    /** 409 — conflict (duplicate group name, no SOS route available, …). */
    public function isConflict(): bool
    {
        return $this->statusCode === 409;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }
}
