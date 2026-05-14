<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Webhooks\MailpitWebhookRequest;
use App\Models\UserNotification;
use App\Repositories\UserNotificationRepository;
use App\Services\NotificationTagParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MailpitController extends Controller
{
    public function __invoke(
        MailpitWebhookRequest $request,
        UserNotificationRepository $repository,
        NotificationTagParser $tagParser,
    ): JsonResponse {
        $notificationId = $tagParser->extractId($request->validated('Tags', []));

        if ($notificationId === null) {
            Log::warning('Notification tags not found.');

            return response()->json(['status' => 'ignored']);
        }

        $notification = UserNotification::query()->find($notificationId);

        if (! $notification) {
            Log::warning('Invalid notification id.', ['notificationId' => $notificationId]);

            return response()->json(['status' => 'ok']);
        }

        $repository->markDelivered($notification);

        return response()->json(['status' => 'ok']);
    }
}
