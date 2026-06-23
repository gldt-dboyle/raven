<?php

declare(strict_types=1);

namespace Gldt\Raven\Contracts;

interface GitHubClient
{
    /**
     * @param  array<int, string>  $labels
     * @return array{number: int, html_url: string, node_id: string}
     */
    public function createIssue(string $title, string $body, array $labels = []): array;

    public function reopenIssue(int $number): void;

    public function commentOnIssue(int $number, string $body): void;
}
