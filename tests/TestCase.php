<?php

declare(strict_types=1);

namespace Gldt\Raven\Tests;

use Gldt\Raven\RavenServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            RavenServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('queue.default', 'sync');

        $app['config']->set('raven.github.token', 'test-token');
        $app['config']->set('raven.github.repository', 'acme/widgets');
        $app['config']->set('raven.webhook.signing_secret', 'test-secret');

        // Keep the route focused on our signature gate (skip the api throttle group in tests).
        $app['config']->set('raven.webhook.middleware', []);
    }
}
