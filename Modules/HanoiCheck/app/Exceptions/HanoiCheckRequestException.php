<?php

namespace Modules\HanoiCheck\Exceptions;

use RuntimeException;

class HanoiCheckRequestException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $responseBody = null,
        public readonly bool $retryable = true,
    ) {
        parent::__construct($message);
    }

    public static function fromStatus(int $status, ?array $json, string $body): self
    {
        $code = (string) ($json['error_code'] ?? 'HTTP_' . $status);
        $message = (string) ($json['message'] ?? 'HanoiCheck trả lỗi HTTP ' . $status);
        if (! empty($json['errors']) && is_array($json['errors'])) {
            $message .= ' | ' . collect($json['errors'])->map(fn ($v, $k) => $k . ': ' . implode('; ', (array) $v))->implode(' | ');
        }

        $retryable = $status >= 500 || $status === 429
            || in_array($code, ['EXPIRED_TIMESTAMP', 'DUPLICATE_NONCE'], true);

        return new self($code, $message, $status, $body, $retryable);
    }
}
