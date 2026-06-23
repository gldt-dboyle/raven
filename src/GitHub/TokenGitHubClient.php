<?php

declare(strict_types=1);

namespace Gldt\Raven\GitHub;

use Gldt\Raven\Contracts\GitHubClient;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TokenGitHubClient implements GitHubClient
{
    public function __construct(
        protected ?string $token,
        protected ?string $repository,
        protected string $apiUrl = 'https://api.github.com',
    ) {}

    protected function request(): PendingRequest
    {
        return Http::baseUrl($this->apiUrl)
            ->withToken($this->token)
            ->acceptJson()
            ->withHeaders([
                'X-GitHub-Api-Version' => '2022-11-28',
                'User-Agent' => 'gldt-raven',
            ]);
    }

    protected function endpoint(string $path): string
    {
        return "/repos/{$this->repository}/{$path}";
    }

    /**
     * @param  array<int, string>  $labels
     * @return array{number: int, html_url: string, node_id: string}
     */
    public function createIssue(string $title, string $body, array $labels = []): array
    {
        $response = $this->request()
            ->post($this->endpoint('issues'), [
                'title' => $title,
                'body' => $body,
                'labels' => array_values($labels),
            ])
            ->throw();

        return [
            'number' => (int) $response->json('number'),
            'html_url' => (string) $response->json('html_url'),
            'node_id' => (string) $response->json('node_id'),
        ];
    }

    public function reopenIssue(int $number): void
    {
        $this->request()
            ->patch($this->endpoint("issues/{$number}"), ['state' => 'open'])
            ->throw();
    }

    public function commentOnIssue(int $number, string $body): void
    {
        $this->request()
            ->post($this->endpoint("issues/{$number}/comments"), ['body' => $body])
            ->throw();
    }
}
