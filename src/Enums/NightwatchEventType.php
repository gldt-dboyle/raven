<?php

declare(strict_types=1);

namespace Gldt\Raven\Enums;

enum NightwatchEventType: string
{
    case Opened = 'issue.opened';
    case Reopened = 'issue.reopened';
    case Resolved = 'issue.resolved';
    case Ignored = 'issue.ignored';

    public function opensIssue(): bool
    {
        return in_array($this, [self::Opened, self::Reopened], true);
    }

    public function isStatusNote(): bool
    {
        return in_array($this, [self::Resolved, self::Ignored], true);
    }
}
