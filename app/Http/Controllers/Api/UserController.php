<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\ListResource;
use App\Models\User;
use App\Support\Pagination;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Users')]
class UserController extends Controller
{
    /**
     * List users
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.Pagination::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $perPage = (int) ($data['per_page'] ?? Pagination::DEFAULT_PER_PAGE);

        $users = User::query()
            ->orderBy('id')
            ->paginate($perPage);

        return ListResource::collection($users)->response();
    }
}
