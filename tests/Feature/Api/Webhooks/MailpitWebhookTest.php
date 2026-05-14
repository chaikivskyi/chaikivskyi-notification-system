<?php

namespace Tests\Feature\Api\Webhooks;

use App\Enums\UserNotificationStatus;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailpitWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(array $payload, bool $authenticated = true): \Illuminate\Testing\TestResponse
    {
        $headers = $authenticated
            ? ['Authorization' => 'Basic '.base64_encode('webhook:secret')]
            : [];

        return $this->postJson('/webhooks/mailpit', $payload, $headers);
    }

    public function test_marks_pending_notification_as_delivered(): void
    {
        $notification = UserNotification::factory()->pending()->create();

        $this->postWebhook([
            'ID' => 'mailpit-abc',
            'Tags' => ["notification-{$notification->id}"],
        ])->assertOk()->assertExactJson(['status' => 'ok']);

        $this->assertSame(UserNotificationStatus::Delivered, $notification->refresh()->status);
    }

    public function test_ignores_payload_with_no_matching_tag(): void
    {
        $this->postWebhook([
            'ID' => 'mailpit-abc',
            'Tags' => ['something-else'],
        ])->assertOk()->assertExactJson(['status' => 'ignored']);
    }

    public function test_ignores_payload_without_tags(): void
    {
        $this->postWebhook([
            'ID' => 'mailpit-abc',
        ])->assertOk()->assertExactJson(['status' => 'ignored']);
    }

    public function test_acks_unknown_notification_id_without_changing_state(): void
    {
        $this->postWebhook([
            'ID' => 'mailpit-abc',
            'Tags' => ['notification-999999'],
        ])->assertOk()->assertExactJson(['status' => 'ok']);
    }

    public function test_does_not_overwrite_non_pending_status(): void
    {
        $notification = UserNotification::factory()->canceled()->create();

        $this->postWebhook([
            'ID' => 'mailpit-abc',
            'Tags' => ["notification-{$notification->id}"],
        ])->assertOk();

        $this->assertSame(UserNotificationStatus::Canceled, $notification->refresh()->status);
    }

    public function test_rejects_request_without_credentials(): void
    {
        $notification = UserNotification::factory()->pending()->create();

        $this->postWebhook([
            'ID' => 'mailpit-abc',
            'Tags' => ["notification-{$notification->id}"],
        ], authenticated: false)->assertStatus(401);

        $this->assertSame(UserNotificationStatus::Pending, $notification->refresh()->status);
    }

    public function test_rejects_request_with_wrong_credentials(): void
    {
        $notification = UserNotification::factory()->pending()->create();

        $this->postJson('/webhooks/mailpit', [
            'ID' => 'mailpit-abc',
            'Tags' => ["notification-{$notification->id}"],
        ], ['Authorization' => 'Basic '.base64_encode('webhook:wrong')])
            ->assertStatus(401);

        $this->assertSame(UserNotificationStatus::Pending, $notification->refresh()->status);
    }
}
