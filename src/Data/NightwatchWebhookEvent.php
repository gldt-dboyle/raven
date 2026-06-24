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
        $payload = (array) ($data['payload'] ?? []);

        return new self(
            event: NightwatchEventType::from((string) ($data['event'] ?? '')),
            timestamp: (string) ($data['timestamp'] ?? ''),
            issue: NightwatchIssue::fromArray((array) ($payload['issue'] ?? [])),
            environment: (array) ($payload['environment'] ?? []),
            actor: isset($payload['actor']) ? (array) $payload['actor'] : null,
        );
    }
}
