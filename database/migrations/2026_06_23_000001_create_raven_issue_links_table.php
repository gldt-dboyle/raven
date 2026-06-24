<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raven_issue_links', function (Blueprint $table) {
            $table->id();
            // Which application/source this link belongs to. Empty string for a
            // bare (single-app) /webhooks/nightwatch install. Not nullable so the
            // composite unique index below treats every row's source as a real
            // value — SQLite and MySQL count NULLs as distinct, which would let
            // duplicate links slip through for the no-source case.
            $table->string('source')->default('');
            $table->string('nightwatch_issue_id');
            $table->unsignedInteger('nightwatch_ref')->nullable();
            $table->unsignedInteger('github_issue_number');
            $table->string('github_node_id')->nullable();
            $table->string('github_url')->nullable();
            $table->timestamps();

            // Uniqueness is per source: two applications can each have their own
            // issue with the same Nightwatch id without colliding.
            $table->unique(['source', 'nightwatch_issue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raven_issue_links');
    }
};
