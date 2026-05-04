<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users(): JsonResponse
    {
        return response()->json(User::query()->with('roles')->latest()->paginate(25));
    }

    public function updateUserRoles(Request $request, User $user, AuditLogger $auditLogger): JsonResponse
    {
        $data = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'exists:roles,name'],
        ]);

        $roles = Role::query()->whereIn('name', $data['roles'])->pluck('id');
        $user->roles()->sync($roles);

        $auditLogger->log('roles.updated', $request->user(), $user, ['roles' => $data['roles']]);

        return response()->json($user->fresh('roles'));
    }
}
