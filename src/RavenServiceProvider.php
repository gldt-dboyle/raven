<?php

declare(strict_types=1);

namespace Gldt\Raven;

use Gldt\Raven\Contracts\GitHubClient;
use Gldt\Raven\GitHub\TokenGitHubClient;
use Illuminate\Support\ServiceProvider;

class RavenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/raven.php', 'raven');

        $this->app->singleton(GitHubClient::class, function ($app): TokenGitHubClient {
            $config = $app['config']->get('raven.github');

            return new TokenGitHubClient(
                token: $config['token'] ?? null,
                repository: $config['repository'] ?? null,
                apiUrl: $config['api_url'] ?? 'https://api.github.com',
            );
        });
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
