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
            $table->string('nightwatch_issue_id')->unique();
            $table->unsignedInteger('nightwatch_ref')->nullable();
            $table->unsignedInteger('github_issue_number');
            $table->string('github_node_id')->nullable();
            $table->string('github_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raven_issue_links');
    }
};
