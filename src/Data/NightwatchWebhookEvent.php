<?php

declare(strict_types=1);

namespace Gldt\Raven\Data;

use Gldt\Raven\Enums\NightwatchEventType;

class NightwatchWebhookEvent
{
    /**
     * @param  array<string, mixed>  $environment
     * @param  array<string, mixed>|null  $actor
     */
    public function __construct(
        public readonly NightwatchEventType $event,
        public readonly string $timestamp,
        public readonly NightwatchIssue $issue,
        public readonly array $environment = [],
        public readonly ?array $actor = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payload = $data['payload'] ?? [];

        return new self(
            event: NightwatchEventType::from($data['event']),
            timestamp: (string) ($data['timestamp'] ?? ''),
            issue: NightwatchIssue::fromArray($payload['issue'] ?? []),
            environment: $payload['environment'] ?? [],
            actor: $payload['actor'] ?? null,
        );
    }
}
