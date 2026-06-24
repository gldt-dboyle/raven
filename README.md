# 🐦‍⬛ Raven

Carry [Laravel Nightwatch](https://nightwatch.laravel.com) issues into **GitHub issues**, automatically.

Like the ravens of the Night's Watch, this package delivers word from your monitoring to where your team actually works. When Nightwatch detects a new exception or performance problem, Raven opens a GitHub issue. When the problem recurs, Raven reopens it. Your team closes issues in GitHub when the work is done — Raven never closes them for you.

## How it works

Nightwatch pushes events to Raven via a signed webhook. Raven verifies the signature, then maps each event to a GitHub action:

| Nightwatch event | What Raven does in GitHub |
| --- | --- |
| `issue.opened` | Creates a new issue, stores a Nightwatch ↔ GitHub mapping |
| `issue.reopened` | Reopens the mapped issue and adds a "this recurred" comment (or creates a fresh issue if no mapping exists yet — e.g. the original predates Raven) |
| `issue.resolved` | Adds a comment noting it was resolved — **leaves the issue open** |
| `issue.ignored` | Adds a comment noting it was ignored — **leaves the issue open** |

GitHub is the source of truth for "done." Nightwatch drives *creation* and *recurrence*; humans close issues in GitHub.

```
Nightwatch ──signed POST──▶ /webhooks/nightwatch
                                │  VerifyNightwatchSignature (HMAC-SHA256)
                                ▼
                         WebhookController ──dispatch──▶ ProcessNightwatchEvent (queued)
                                                                │
                                                  create / reopen / comment on GitHub
                                                                │
                                                  store mapping in raven_issue_links
```

## Requirements

- PHP 8.4+
- Laravel 11, 12, or 13
- A GitHub repository to file issues in
- A Nightwatch account with webhooks

## Installation

```bash
composer require gldt/raven
```

Publish the config (optional — it works with sensible defaults):

```bash
php artisan vendor:publish --tag=raven-config
```

Run the migration to create the mapping table:

```bash
php artisan migrate
```

## Configuration

Set these in your `.env`:

```dotenv
# A GitHub fine-grained PAT with "Issues: Read and write" on the target repo
RAVEN_GITHUB_TOKEN=github_pat_xxxxxxxx
RAVEN_GITHUB_REPOSITORY=your-org/your-repo

# The signing secret you configured on the Nightwatch webhook
RAVEN_WEBHOOK_SECRET=a-long-random-string

# Optional
RAVEN_WEBHOOK_PATH=webhooks/nightwatch          # default
RAVEN_GITHUB_API_URL=https://api.github.com     # change for GitHub Enterprise
RAVEN_QUEUE_CONNECTION=                          # falls back to your default queue
RAVEN_QUEUE=
```

Labels applied to created issues are configurable in `config/raven.php`:

```php
'labels' => [
    'default' => ['nightwatch'],
    'exception' => ['bug'],
    'performance' => ['performance'],
],
```

### GitHub token

Create a **fine-grained personal access token** scoped to the target repository with **Issues: Read and write**. That's the only permission Raven needs.

> Raven authenticates via a token by default. The GitHub calls live behind a `Gldt\Raven\Contracts\GitHubClient` interface, so you can bind your own implementation (e.g. a GitHub App) in a service provider without touching the rest of the package.

### Nightwatch webhook

In your Nightwatch application settings, add a webhook pointing at:

```
https://your-app.com/webhooks/nightwatch
```

Set a signing secret and use the **same** value for `RAVEN_WEBHOOK_SECRET`. Raven verifies every request with an HMAC-SHA256 signature, so requests without a valid signature are rejected with a `403`.

### Multiple environments (one install)

You don't need a separate Raven deployment per environment. A single install can receive webhooks from several Nightwatch environments by adding a source segment to the path — `/webhooks/nightwatch/{source}` — with a distinct signing secret per source:

```dotenv
RAVEN_WEBHOOK_SOURCES=dev,staging,prod
RAVEN_WEBHOOK_SECRET_DEV=secret-for-dev
RAVEN_WEBHOOK_SECRET_STAGING=secret-for-staging
RAVEN_WEBHOOK_SECRET_PROD=secret-for-prod
```

Then point each Nightwatch environment at its own URL:

```
https://your-app.com/webhooks/nightwatch/dev
https://your-app.com/webhooks/nightwatch/staging
https://your-app.com/webhooks/nightwatch/prod
```

Each request is verified against that source's secret; an unknown or unconfigured source is rejected with a `404`. All sources file into the same `RAVEN_GITHUB_REPOSITORY`, and issues are tagged with an `env:<source>` label so they stay distinguishable. The bare `/webhooks/nightwatch` path keeps working and uses `RAVEN_WEBHOOK_SECRET`.

Adding an environment is just two `.env` lines (a name in `RAVEN_WEBHOOK_SOURCES` and its `RAVEN_WEBHOOK_SECRET_<NAME>`) — no config file to publish, and it survives `php artisan config:cache`.

## Processing

Webhook handling is dispatched to a **queued job** so the endpoint responds instantly and the GitHub calls (and their retries) happen in the background. Make sure a queue worker is running:

```bash
php artisan queue:work
```

(With the default `sync` queue connection it runs inline, but a real queue is recommended in production.)

### Duplicate protection

Duplicate webhook deliveries and job retries won't open a second GitHub issue for the same Nightwatch issue. Issue creation is serialized per Nightwatch issue id using an atomic [cache lock](https://laravel.com/docs/cache#atomic-locks), with the `raven_issue_links` unique index as the final backstop.

The one residual gap is a process crash *after* GitHub accepts the new issue but *before* Raven records the link: a later retry can't see the orphaned issue and will create a fresh one. The unique index prevents a duplicate link row, not a duplicate issue on GitHub. This window is inherent to pairing a non-transactional external API call with a local write, and is the only path to a duplicate.

This relies on your cache store supporting atomic locks — `redis`, `memcached`, `database`, `dynamodb`, `file`, and `array` all do. The only configuration that won't work is sharing the in-memory `array` store across separate worker processes (not a real-world setup). If you've pointed `CACHE_STORE` at something custom, make sure it implements `Illuminate\Contracts\Cache\LockProvider`.

## Testing

```bash
composer install
vendor/bin/pest
```

The suite uses [Orchestra Testbench](https://github.com/orchestral/testbench) to boot a minimal Laravel app and `Http::fake()` to assert GitHub calls without hitting the network.

## License

MIT
