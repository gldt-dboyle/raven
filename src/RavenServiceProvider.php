<?php

declare(strict_types=1);

namespace Gldt\Raven;

use Gldt\Raven\Contracts\GitHubClient;
use Gldt\Raven\Contracts\GitHubClientFactory;
use Gldt\Raven\GitHub\ConfigGitHubClientFactory;
use Illuminate\Support\ServiceProvider;

class RavenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/raven.php', 'raven');

        $this->app->singleton(GitHubClientFactory::class, ConfigGitHubClientFactory::class);

        // The bare GitHubClient resolves to the no-source (top-level config)
        // client, so anything resolving the contract directly still works.
        $this->app->bind(
            GitHubClient::class,
            fn ($app): GitHubClient => $app->make(GitHubClientFactory::class)->for(null),
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadRoutesFrom(__DIR__.'/../routes/raven.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/raven.php' => config_path('raven.php'),
            ], 'raven-config');
        }
    }
}
