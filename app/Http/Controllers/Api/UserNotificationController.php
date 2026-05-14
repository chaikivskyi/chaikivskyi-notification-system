<?php

namespace App\Http\Controllers\Api;

use App\DTOs\UserNotificationData;
use App\DTOs\UserNotificationFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserNotification\IndexRequest;
use App\Http\Requests\UserNotification\StoreBulkRequest;
use App\Http\Requests\UserNotification\StoreRequest;
use App\Http\Resources\UserNotification\DetailResource;
use App\Http\Resources\UserNotification\ListResource;
use App\Models\UserNotification;
use App\Repositories\UserNotificationRepository;
use App\Support\Pagination;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('User Notifications')]
class UserNotificationController extends Controller
{
    /**
     * List user notifications
     */
    public function index(IndexRequest $request, UserNotificationRepository $notificationRepository): JsonResponse
    {
        $filter = UserNotificationFilter::fromArray($request->validated('filters', []));
        $notifications = $notificationRepository->list($filter, (int) $request->validated('per_page', Pagination::DEFAULT_PER_PAGE));

        return ListResource::collection($notifications)->response();
    }

    /**
     * Store user notification
     */
    public function store(StoreRequest $request, UserNotificationRepository $notificationRepository): JsonResponse
    {
        $data = UserNotificationData::fromArray($request->validated());
        $notification = $notificationRepository->store($data);

        return DetailResource::make($notification)->response();
    }

    /**
     * Store bulk user notification
     */
    public function storeBulk(StoreBulkRequest $request, UserNotificationRepository $notificationRepository): JsonResponse
    {
        $items = array_map(
            fn (array $item) => UserNotificationData::fromArray($item),
            $request->validated('notifications'),
        );
        $notifications = $notificationRepository->storeBulk($items);

        return ListResource::collection($notifications)->response();
    }

    /**
     * Get status of notification/batch
     */
    public function status(Request $request, UserNotificationRepository $notificationRepository): JsonResponse
    {
        $request->validate([
            'id' => ['required_without:batch_id', 'prohibits:batch_id', 'integer', 'exists:user_notifications,id'],
            'batch_id' => ['required_without:id', 'uuid', 'exists:user_notifications,batch_id'],
        ]);

        $status = $notificationRepository->status(
            $request->filled('id') ? $request->integer('id') : null,
            $request->input('batch_id'),
        );

        return response()->json(['status' => $status]);
    }

    /**
     * Cancel notification
     */
    public function cancel(
        UserNotification $userNotification,
        UserNotificationRepository $notificationRepository
    ): JsonResponse {
        $notification = $notificationRepository->cancel($userNotification);

        return DetailResource::make($notification)->response();
    }
}
