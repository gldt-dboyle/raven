<?php

declare(strict_types=1);

use Gldt\Raven\Models\RavenIssueLink;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        '*/issues/*/comments' => Http::response([], 201),
        '*/issues/*' => Http::response([], 200),
        '*/issues' => Http::response([
            'number' => 42,
            'html_url' => 'https://github.com/acme/widgets/issues/42',
            'node_id' => 'I_node42',
        ], 201),
    ]);
});

it('rejects a webhook with an invalid signature', function () {
    $body = json_encode(nightwatchPayload('issue.opened'));

    $this->call('POST', '/webhooks/nightwatch', [], [], [], [
        'HTTP_NIGHTWATCH_SIGNATURE' => 'not-the-right-signature',
        'CONTENT_TYPE' => 'application/json',
    ], $body)->assertForbidden();

    Http::assertNothingSent();
});

it('creates a GitHub issue and stores a link on issue.opened', function () {
    postNightwatchWebhook(nightwatchPayload('issue.opened', ['id' => 'uuid-open']))
        ->assertOk();

    expect(RavenIssueLink::query()->where('nightwatch_issue_id', 'uuid-open')->first())
        ->not->toBeNull()
        ->github_issue_number->toBe(42)
        ->github_node_id->toBe('I_node42');

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/repos/acme/widgets/issues'));
});

it('reopens and comments on issue.reopened when a link already exists', function () {
    RavenIssueLink::query()->create([
        'nightwatch_issue_id' => 'uuid-reopen',
        'github_issue_number' => 99,
    ]);

    postNightwatchWebhook(nightwatchPayload('issue.reopened', ['id' => 'uuid-reopen']))
        ->assertOk();

    Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
        && str_ends_with($request->url(), '/issues/99'));

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/issues/99/comments'));
});

it('only comments on issue.resolved and never creates or closes', function () {
    RavenIssueLink::query()->create([
        'nightwatch_issue_id' => 'uuid-resolved',
        'github_issue_number' => 5,
    ]);

    postNightwatchWebhook(nightwatchPayload('issue.resolved', ['id' => 'uuid-resolved'], [
        'payload' => ['actor' => ['name' => 'Dana', 'type' => 'user']],
    ]))->assertOk();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/issues/5/comments'));

    // No create (POST .../issues) and no reopen (PATCH) should happen.
    Http::assertNotSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/issues'));
    Http::assertNotSent(fn (Request $request) => $request->method() === 'PATCH');
});

it('creates a fresh GitHub issue on issue.reopened when no link exists yet', function () {
    postNightwatchWebhook(nightwatchPayload('issue.reopened', ['id' => 'uuid-reopen-new']))
        ->assertOk();

    expect(RavenIssueLink::query()->where('nightwatch_issue_id', 'uuid-reopen-new')->first())
        ->not->toBeNull()
        ->github_issue_number->toBe(42);

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/repos/acme/widgets/issues'));
});

it('does nothing on issue.resolved when no link exists', function () {
    postNightwatchWebhook(nightwatchPayload('issue.resolved', ['id' => 'uuid-orphan']))
        ->assertOk();

    Http::assertNothingSent();
    expect(RavenIssueLink::query()->count())->toBe(0);
});

it('acknowledges but ignores an unmapped event type', function () {
    postNightwatchWebhook(nightwatchPayload('issue.snoozed'))
        ->assertOk()
        ->assertJson(['received' => true, 'ignored' => true]);

    Http::assertNothingSent();
    expect(RavenIssueLink::query()->count())->toBe(0);
});

it('does not create a duplicate when issue.opened arrives twice', function () {
    $payload = nightwatchPayload('issue.opened', ['id' => 'uuid-dupe']);

    postNightwatchWebhook($payload)->assertOk();
    postNightwatchWebhook($payload)->assertOk();

    expect(RavenIssueLink::query()->where('nightwatch_issue_id', 'uuid-dupe')->count())->toBe(1);
});

it('verifies a named source against its own secret and tags the issue', function () {
    config()->set('raven.webhook.sources', ['prod' => 'prod-secret']);

    postNightwatchWebhook(nightwatchPayload('issue.opened', ['id' => 'uuid-prod']), 'prod-secret', 'prod')
        ->assertOk();

    expect(RavenIssueLink::query()->where('nightwatch_issue_id', 'uuid-prod')->first())
        ->not->toBeNull();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/repos/acme/widgets/issues')
        && in_array('env:prod', $request->data()['labels'] ?? [], true));
});

it('rejects a named source signed with the wrong secret', function () {
    config()->set('raven.webhook.sources', ['prod' => 'prod-secret']);

    // Signed with the default secret, not prod's.
    postNightwatchWebhook(nightwatchPayload('issue.opened'), 'test-secret', 'prod')
        ->assertForbidden();

    Http::assertNothingSent();
});

it('returns 404 for an unconfigured source', function () {
    config()->set('raven.webhook.sources', []);

    postNightwatchWebhook(nightwatchPayload('issue.opened'), 'whatever', 'ghost')
        ->assertNotFound();

    Http::assertNothingSent();
});
