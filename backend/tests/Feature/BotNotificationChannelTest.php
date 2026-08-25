<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BotNotificationChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.slack.bot_user_oauth_token' => 'xoxb-test-token',
            'services.slack.channel_name_prefix' => 'clinic-',
        ]);
    }

    public function test_it_creates_a_channel_and_invites_members(): void
    {
        Http::fakeSequence()
            ->push([
                'ok' => true,
                'channel' => [
                    'id' => 'C123456789',
                    'name' => 'clinic-visit-123',
                    'is_private' => false,
                ],
            ])
            ->push(['ok' => true]);

        $response = $this->postJson('/api/bot-notifications/channels', [
            'workspace_id' => 'T123456789',
            'channel_name' => 'visit-123',
            'member_ids' => ['U123456789', 'W987654321'],
            'is_private' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.workspace_id', 'T123456789')
            ->assertJsonPath('data.channel.name', 'clinic-visit-123')
            ->assertJsonPath('data.invited_member_ids.0', 'U123456789');

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://slack.com/api/conversations.create'
                && $request->hasHeader('Authorization', 'Bearer xoxb-test-token')
                && $request['team_id'] === 'T123456789'
                && $request['name'] === 'clinic-visit-123'
                && $request['is_private'] === false;
        });

        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://slack.com/api/conversations.invite'
                && $request['channel'] === 'C123456789'
                && $request['users'] === 'U123456789,W987654321';
        });
    }

    public function test_it_validates_the_request_before_calling_slack(): void
    {
        Http::fake();

        $response = $this->postJson('/api/bot-notifications/channels', [
            'workspace_id' => 'invalid',
            'channel_name' => 'Has Spaces',
            'member_ids' => ['invalid-user'],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'workspace_id',
                'channel_name',
                'member_ids.0',
            ]);

        Http::assertNothingSent();
    }

    public function test_it_returns_a_useful_error_when_channel_name_is_taken(): void
    {
        Http::fake([
            'slack.com/api/conversations.create' => Http::response([
                'ok' => false,
                'error' => 'name_taken',
            ]),
        ]);

        $response = $this->postJson('/api/bot-notifications/channels', [
            'workspace_id' => 'T123456789',
            'channel_name' => 'visit-123',
            'member_ids' => [],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('error', 'name_taken')
            ->assertJsonPath('slack_method', 'conversations.create');
    }

    public function test_it_reports_when_channel_was_created_but_inviting_failed(): void
    {
        Http::fakeSequence()
            ->push([
                'ok' => true,
                'channel' => [
                    'id' => 'C123456789',
                    'name' => 'clinic-visit-123',
                ],
            ])
            ->push([
                'ok' => false,
                'error' => 'missing_scope',
            ]);

        $response = $this->postJson('/api/bot-notifications/channels', [
            'workspace_id' => 'T123456789',
            'channel_name' => 'visit-123',
            'member_ids' => ['U123456789'],
        ]);

        $response
            ->assertStatus(502)
            ->assertJsonPath('error', 'missing_scope')
            ->assertJsonPath('channel_created', true)
            ->assertJsonPath('channel.id', 'C123456789');
    }
}
