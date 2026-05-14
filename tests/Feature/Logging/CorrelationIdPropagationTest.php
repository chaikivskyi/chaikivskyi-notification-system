<?php

namespace Tests\Feature\Logging;

use App\Enums\UserNotificationStatus;
use App\Http\Middleware\CorrelationId;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\Middleware\PushCorrelationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Tests\TestCase;

class CorrelationIdPropagationTest extends TestCase
{
    use RefreshDatabase;

    public function test_correlation_id_from_api_request_persists_on_notification(): void
    {
        $user = User::factory()->create();
        $uuid = Str::uuid()->toString();
        $this->withHeaders(['X-Correlation-ID' => $uuid])
            ->postJson('/api/user-notifications', [
                'user_id' => $user->id,
                'channel' => 'email',
                'subject' => 'hi',
                'body' => 'yo',
            ])
            ->assertSuccessful();

        $notification = UserNotification::query()->latest('id')->first();
        $this->assertNotNull($notification);
        $this->assertSame($uuid, $notification->correlation_id);
    }

    public function test_correlation_id_is_shared_across_bulk_rows(): void
    {
        $user = User::factory()->create();
        $uuid = Str::uuid()->toString();
        $this->withHeaders(['X-Correlation-ID' => $uuid])
            ->postJson('/api/user-notifications/bulk', [
                'notifications' => [
                    ['user_id' => $user->id, 'channel' => 'email', 'body' => 'a'],
                    ['user_id' => $user->id, 'channel' => 'sms', 'body' => 'b'],
                ],
            ])
            ->assertSuccessful();

        $ids = UserNotification::query()->pluck('correlation_id')->unique();
        $this->assertCount(1, $ids);
        $this->assertSame($uuid, $ids->first());
    }

    public function test_job_middleware_pushes_correlation_id_into_context(): void
    {
        Context::forget(CorrelationId::CONTEXT_KEY);

        (new PushCorrelationContext('trace-job-007'))->handle(
            new \stdClass,
            fn () => null,
        );

        $this->assertSame('trace-job-007', Context::get(CorrelationId::CONTEXT_KEY));
    }

    public function test_job_middleware_with_null_id_leaves_context_untouched(): void
    {
        Context::forget(CorrelationId::CONTEXT_KEY);

        (new PushCorrelationContext(null))->handle(
            new \stdClass,
            fn () => null,
        );

        $this->assertNull(Context::get(CorrelationId::CONTEXT_KEY));
    }

    public function test_mailpit_webhook_rehydrates_correlation_from_notification(): void
    {
        $notification = UserNotification::factory()->pending()->create([
            'correlation_id' => 'trace-webhook-042',
        ]);

        Context::forget(CorrelationId::CONTEXT_KEY);

        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('webhook:secret')])
            ->postJson('/webhooks/mailpit', [
                'ID' => 'mp-1',
                'Tags' => ["notification-{$notification->id}"],
            ])
            ->assertOk();

        $this->assertSame(UserNotificationStatus::Delivered, $notification->refresh()->status);
    }
}
