<?php

declare(strict_types=1);

namespace Gldt\Raven\Contracts;

interface GitHubClientFactory
{
    /**
     * Resolve a GitHub client for the given webhook source. A null source (a
     * bare /webhooks/nightwatch install) uses the top-level github.* config.
     */
    public function for(?string $source): GitHubClient;
}
