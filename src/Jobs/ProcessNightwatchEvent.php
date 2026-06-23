<?php

declare(strict_types=1);

namespace Gldt\Raven\Jobs;

use Gldt\Raven\Contracts\GitHubClient;
use Gldt\Raven\Data\NightwatchWebhookEvent;
use Gldt\Raven\Enums\NightwatchEventType;
use Gldt\Raven\Models\RavenIssueLink;
use Gldt\Raven\Support\IssuePresenter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessNightwatchEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly NightwatchWebhookEvent $event) {}

    public function handle(GitHubClient $github, IssuePresenter $presenter): void
    {
        $issue = $this->event->issue;
        $type = $this->event->event;

        $link = RavenIssueLink::query()->firstWhere('nightwatch_issue_id', $issue->id);

        if ($link === null) {
            if ($type->opensIssue()) {
                $this->createGitHubIssue($github, $presenter);
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

    private function createGitHubIssue(GitHubClient $github, IssuePresenter $presenter): void
    {
        $issue = $this->event->issue;

        $created = $github->createIssue(
            $presenter->title($issue),
            $presenter->body($this->event),
            $presenter->labels($issue),
        );

        RavenIssueLink::query()->create([
            'nightwatch_issue_id' => $issue->id,
            'nightwatch_ref' => $issue->ref,
            'github_issue_number' => $created['number'],
            'github_node_id' => $created['node_id'],
            'github_url' => $created['html_url'],
        ]);
    }
}
