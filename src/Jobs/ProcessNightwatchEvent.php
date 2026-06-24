<?php

declare(strict_types=1);

namespace Gldt\Raven\Jobs;

use Gldt\Raven\Contracts\GitHubClient;
use Gldt\Raven\Contracts\GitHubClientFactory;
use Gldt\Raven\Data\NightwatchWebhookEvent;
use Gldt\Raven\Enums\NightwatchEventType;
use Gldt\Raven\Models\RavenIssueLink;
use Gldt\Raven\Support\IssuePresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ProcessNightwatchEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public readonly NightwatchWebhookEvent $event,
        public readonly ?string $source = null,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(GitHubClientFactory $factory, IssuePresenter $presenter): void
    {
        $issue = $this->event->issue;
        $type = $this->event->event;
        $source = (string) $this->source;

        $github = $factory->for($this->source);

        $link = RavenIssueLink::query()
            ->where('source', $source)
            ->firstWhere('nightwatch_issue_id', $issue->id);

        if ($link === null) {
            if ($type->opensIssue()) {
                $this->createGitHubIssue($github, $presenter, $source);
            }

            return;
        }

        if ($type === NightwatchEventType::Reopened) {
            $github->reopenIssue($link->github_issue_number);
            $github->commentOnIssue($link->github_issue_number, $presenter->recurrenceComment($issue));

            return;
        }

        if ($type->isStatusNote()) {
            $github->commentOnIssue($link->github_issue_number, $presenter->statusComment($this->event));
        }
    }

    private function createGitHubIssue(GitHubClient $github, IssuePresenter $presenter, string $source): void
    {
        $issue = $this->event->issue;

        // Serialize creation per source + Nightwatch issue so duplicate webhook
        // deliveries or overlapping retries can't each open a separate GitHub
        // issue. The re-check inside the lock makes a second delivery a no-op,
        // and the DB unique index on (source, nightwatch_issue_id) is the final
        // backstop. The source is part of the key so two applications creating
        // the same Nightwatch issue id don't block each other.
        //
        // TTL (120s) must comfortably exceed the GitHub call's own timeout (see
        // TokenGitHubClient::request) so the lock can't lapse mid-callback and
        // let a concurrent duplicate slip past the exists() check below.
        //
        // The block wait (25s) must in turn exceed that same GitHub timeout, so
        // a duplicate arriving mid-create waits for the holder to finish and
        // then no-ops on the re-check, rather than timing out and burning a retry.
        Cache::lock('raven:create:'.$source.':'.$issue->id, 120)->block(25, function () use ($github, $presenter, $source, $issue) {
            if (RavenIssueLink::query()->where('source', $source)->where('nightwatch_issue_id', $issue->id)->exists()) {
                return;
            }

            $created = $github->createIssue(
                $presenter->title($issue),
                $presenter->body($this->event),
                $presenter->labels($issue, $this->source),
            );

            RavenIssueLink::query()->create([
                'source' => $source,
                'nightwatch_issue_id' => $issue->id,
                'nightwatch_ref' => $issue->ref,
                'github_issue_number' => $created['number'],
                'github_node_id' => $created['node_id'],
                'github_url' => $created['html_url'],
            ]);
        });
    }
}
