<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('raven_issue_links', function (Blueprint $table) {
            // Which application/source this link belongs to. Empty string for a
            // bare (single-app) /webhooks/nightwatch install. Not nullable so the
            // composite unique index below treats every row's source as a real
            // value — SQLite and MySQL count NULLs as distinct, which would let
            // duplicate links slip through for the no-source case.
            $table->string('source')->default('');
        });

        Schema::table('raven_issue_links', function (Blueprint $table) {
            // Swap the single-column unique for a per-source composite so two
            // applications can share a Nightwatch issue id without colliding.
            $table->dropUnique(['nightwatch_issue_id']);
            $table->unique(['source', 'nightwatch_issue_id']);
        });
    }

    public function down(): void
    {
        Schema::table('raven_issue_links', function (Blueprint $table) {
            $table->dropUnique(['source', 'nightwatch_issue_id']);
            $table->unique(['nightwatch_issue_id']);
        });

        Schema::table('raven_issue_links', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
