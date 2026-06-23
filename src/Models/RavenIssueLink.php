<?php

declare(strict_types=1);

namespace Gldt\Raven\Models;

use Illuminate\Database\Eloquent\Model;

class RavenIssueLink extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nightwatch_ref' => 'integer',
            'github_issue_number' => 'integer',
        ];
    }
}
