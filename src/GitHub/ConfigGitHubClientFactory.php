<?php

declare(strict_types=1);

namespace Gldt\Raven\GitHub;

use Gldt\Raven\Contracts\GitHubClient;
use Gldt\Raven\Contracts\GitHubClientFactory;

class ConfigGitHubClientFactory implements GitHubClientFactory
{
    public function for(?string $source): GitHubClient
    {
        $github = (array) config('raven.github');

        // A configured source files into its own repository/token; anything it
        // omits (or the bare no-source install) falls back to github.*.
        $config = $source !== null
            ? (array) config('raven.webhook.sources.'.$source)
            : [];

        return new TokenGitHubClient(
            token: $config['token'] ?? $github['token'] ?? null,
            repository: $config['repository'] ?? $github['repository'] ?? null,
            apiUrl: $github['api_url'] ?? 'https://api.github.com',
        );
    }
}
