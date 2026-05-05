<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RolePermissionController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::query()
            ->with('permissions')
            ->orderByRaw("CASE WHEN name = 'super_admin' THEN 0 ELSE 1 END")
            ->orderBy('label')
            ->get();

        $selectedRole = $roles->firstWhere('id', $request->integer('role_id')) ?? $roles->first();

        return view('admin.role-permissions.index', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissionIds' => $selectedRole?->permissions->pluck('id')->all() ?? [],
        ]);
    }

    public function update(Request $request, Role $role, AuditLogger $auditLogger): RedirectResponse
    {
        $permissionIds = $role->name === 'super_admin'
            ? Permission::query()->pluck('id')->all()
            : $request->validate([
                'permissions' => ['nullable', 'array'],
                'permissions.*' => ['integer', 'exists:permissions,id'],
            ])['permissions'] ?? [];

        $role->permissions()->sync($permissionIds);

        $auditLogger->log('role.permissions_updated', $request->user(), $role, [
            'role' => $role->name,
            'permissions' => Permission::query()->whereIn('id', $permissionIds)->pluck('name')->all(),
        ]);

        return redirect()
            ->route('admin.role-permissions.index', ['role_id' => $role->id])
            ->with('status', $role->label.' permissions updated.');
    }

    /**
     * @return array<string, array<int, Permission>>
     */
    private function permissionGroups(): array
    {
        $permissions = Permission::query()->orderBy('label')->get()->keyBy('name');

        return collect(PermissionSeeder::groupedPermissions())
            ->map(fn (array $group) => collect(array_keys($group))
                ->map(fn (string $permission) => $permissions->get($permission))
                ->filter()
                ->values()
                ->all())
            ->all();
    }
}
