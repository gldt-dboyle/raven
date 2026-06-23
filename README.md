# 🐦‍⬛ Raven

Carry [Laravel Nightwatch](https://nightwatch.laravel.com) issues into **GitHub issues**, automatically.

Like the ravens of the Night's Watch, this package delivers word from your monitoring to where your team actually works. When Nightwatch detects a new exception or performance problem, Raven opens a GitHub issue. When the problem recurs, Raven reopens it. Your team closes issues in GitHub when the work is done — Raven never closes them for you.

## How it works

Nightwatch pushes events to Raven via a signed webhook. Raven verifies the signature, then maps each event to a GitHub action:

| Nightwatch event | What Raven does in GitHub |
| --- | --- |
| `issue.opened` | Creates a new issue, stores a Nightwatch ↔ GitHub mapping |
| `issue.reopened` | Reopens the mapped issue and adds a "this recurred" comment |
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

## Processing

Webhook handling is dispatched to a **queued job** so the endpoint responds instantly and the GitHub calls (and their retries) happen in the background. Make sure a queue worker is running:

```bash
php artisan queue:work
```

(With the default `sync` queue connection it runs inline, but a real queue is recommended in production.)

## Testing

```bash
composer install
vendor/bin/pest
```

The suite uses [Orchestra Testbench](https://github.com/orchestral/testbench) to boot a minimal Laravel app and `Http::fake()` to assert GitHub calls without hitting the network.

## License

MIT
