<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\AssignUserRoleRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserManagementController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'created_at', 'updated_at'])
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'message' => 'Users fetched successfully.',
            'data' => $users,
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['role'] === 'Admin' && ! $request->user()->hasRole('Admin')) {
            return response()->json([
                'message' => 'Forbidden. Only Admin can create Admin users.',
            ], 403);
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'User created successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        $user->update($payload);

        return response()->json([
            'message' => 'User updated successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    public function assignRole(AssignUserRoleRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['role'] === 'Admin' && ! $request->user()->hasRole('Admin')) {
            return response()->json([
                'message' => 'Forbidden. Only Admin can assign Admin role.',
            ], 403);
        }

        $user->syncRoles([$validated['role']]);

        return response()->json([
            'message' => 'User role updated successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }
}
