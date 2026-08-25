<?php

namespace App\Exceptions;

use RuntimeException;

class SlackApiException extends RuntimeException
{
    public function __construct(
        public readonly string $method,
        public readonly string $slackError,
        public readonly int $httpStatus = 502,
        public readonly array $details = [],
        public readonly ?array $createdChannel = null,
        public readonly ?int $retryAfter = null,
    ) {
        parent::__construct("Slack API {$method} failed: {$slackError}");
    }
}
