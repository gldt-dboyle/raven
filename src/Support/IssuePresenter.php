<?php

declare(strict_types=1);

namespace Gldt\Raven\Support;

use Gldt\Raven\Data\NightwatchIssue;
use Gldt\Raven\Data\NightwatchWebhookEvent;
use Gldt\Raven\Enums\NightwatchEventType;

class IssuePresenter
{
    public function title(NightwatchIssue $issue): string
    {
        return $issue->title;
    }

    /**
     * @return array<int, string>
     */
    public function labels(NightwatchIssue $issue): array
    {
        $default = (array) config('raven.labels.default', []);
        $typed = (array) config('raven.labels.'.$issue->type, []);

        return array_values(array_unique([...$default, ...$typed]));
    }

    public function body(NightwatchWebhookEvent $event): string
    {
        $issue = $event->issue;

        $lines = [
            '| Field | Value |',
            '| --- | --- |',
            '| Type | '.$issue->type.' |',
            '| Priority | '.$issue->priority.' |',
        ];

        if ($issue->ref !== null) {
            $lines[] = '| Nightwatch ref | #'.$issue->ref.' |';
        }

        if ($name = $event->environment['name'] ?? null) {
            $lines[] = '| Environment | '.$name.' |';
        }

        foreach ($issue->details as $key => $value) {
            if ($key === 'type') {
                continue;
            }

            $lines[] = '| '.ucfirst(str_replace('_', ' ', (string) $key)).' | '.$this->stringify($value).' |';
        }

        if ($issue->url !== null) {
            $lines[] = '';
            $lines[] = '[View in Nightwatch]('.$issue->url.')';
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = '<sub>🐦‍⬛ Opened automatically by Raven from a Laravel Nightwatch webhook.</sub>';

        return implode("\n", $lines);
    }

    public function recurrenceComment(NightwatchIssue $issue): string
    {
        $link = $issue->url !== null ? ' ([view in Nightwatch]('.$issue->url.'))' : '';

        return '🐦‍⬛ **This issue has recurred.** Nightwatch reopened it after it was previously resolved.'.$link;
    }

    public function statusComment(NightwatchWebhookEvent $event): string
    {
        $status = $event->event === NightwatchEventType::Resolved ? 'resolved' : 'ignored';
        $actor = $event->actor['name'] ?? null;
        $by = $actor !== null ? ' by '.$actor : '';

        return '🐦‍⬛ Marked **'.$status.'** in Nightwatch'.$by.'. This GitHub issue is left open — close it when the work is done.';
    }

    private function stringify(mixed $value): string
    {
        return is_array($value) ? implode(', ', $value) : (string) $value;
    }
}
