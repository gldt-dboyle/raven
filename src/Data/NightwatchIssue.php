<?php

declare(strict_types=1);

namespace Gldt\Raven\Data;

class NightwatchIssue
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?int $ref,
        public readonly string $type,
        public readonly string $status,
        public readonly string $priority,
        public readonly ?string $url,
        public readonly array $details = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            ref: isset($data['ref']) ? (int) $data['ref'] : null,
            type: (string) ($data['type'] ?? 'exception'),
            title: (string) ($data['title'] ?? 'Untitled issue'),
            status: (string) ($data['status'] ?? 'open'),
            priority: (string) ($data['priority'] ?? 'none'),
            url: isset($data['url']) ? (string) $data['url'] : null,
            details: (array) ($data['details'] ?? []),
        );
    }
}
