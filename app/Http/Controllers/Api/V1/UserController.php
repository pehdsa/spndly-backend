<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Users\BlockUserAction;
use App\Actions\Users\DeleteUserAction;
use App\Actions\Users\ListUsersAction;
use App\Actions\Users\UnblockUserAction;
use App\Actions\Users\UpdateUserRoleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request, ListUsersAction $action): AnonymousResourceCollection
    {
        $users = $action->handle(search: $request->string('search')->value() ?: null);

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function destroy(User $user, DeleteUserAction $action): MessageResource
    {
        Gate::authorize('delete', $user);

        $action->handle($user);

        return new MessageResource('User deleted successfully.');
    }

    public function block(User $user, BlockUserAction $action): MessageResource
    {
        Gate::authorize('block', $user);

        $action->handle($user);

        return new MessageResource('User blocked successfully.');
    }

    public function unblock(User $user, UnblockUserAction $action): MessageResource
    {
        Gate::authorize('unblock', $user);

        $action->handle($user);

        return new MessageResource('User unblocked successfully.');
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user, UpdateUserRoleAction $action): UserResource
    {
        Gate::authorize('updateRole', $user);

        $action->handle($user, $request->enum('role', \App\Enums\UserRole::class));

        return new UserResource($user->fresh());
    }
}
