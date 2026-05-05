<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);

    Role::query()->firstOrCreate(['name' => 'staff'], ['label' => 'Staff']);
    Role::query()->firstOrCreate(['name' => 'student'], ['label' => 'Student']);
});

function rolePermissionUserWithPermissions(array $permissions): User
{
    static $roleNumber = 0;

    $roleNumber++;
    $role = Role::query()->create(['name' => 'permission_manager_'.$roleNumber, 'label' => 'Permission Manager '.$roleNumber]);
    $role->permissions()->sync(Permission::query()->whereIn('name', $permissions)->pluck('id'));

    $user = User::factory()->create();
    $user->roles()->sync($role);

    return $user;
}

it('requires manage role permissions permission to access the page', function (): void {
    $user = rolePermissionUserWithPermissions(['view-dashboard']);
    $manager = rolePermissionUserWithPermissions(['manage-role-permissions']);

    $this->actingAs($user)->get(route('admin.role-permissions.index'))
        ->assertForbidden();

    $this->actingAs($manager)->get(route('admin.role-permissions.index'))
        ->assertSuccessful()
        ->assertSee('Role Permissions')
        ->assertSee('Lost Found')
        ->assertSee('View lost item records');
});

it('adds and removes permissions from a role', function (): void {
    $manager = rolePermissionUserWithPermissions(['manage-role-permissions']);
    $staff = Role::query()->where('name', 'staff')->firstOrFail();
    $viewLost = Permission::query()->where('name', 'view-lost-items')->firstOrFail();
    $viewFound = Permission::query()->where('name', 'view-found-items')->firstOrFail();
    $manageIncidents = Permission::query()->where('name', 'manage-incidents')->firstOrFail();

    $staff->permissions()->sync([$viewLost->id, $manageIncidents->id]);

    $this->actingAs($manager)->put(route('admin.role-permissions.update', $staff), [
        'permissions' => [$viewFound->id],
    ])->assertRedirect(route('admin.role-permissions.index', ['role_id' => $staff->id]));

    $staff->refresh();

    expect($staff->permissions()->where('name', 'view-found-items')->exists())->toBeTrue()
        ->and($staff->permissions()->where('name', 'view-lost-items')->exists())->toBeFalse()
        ->and($staff->permissions()->where('name', 'manage-incidents')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'role.permissions_updated')->where('user_id', $manager->id)->exists())->toBeTrue();
});

it('keeps super admin assigned to every permission even when update submits none', function (): void {
    $manager = rolePermissionUserWithPermissions(['manage-role-permissions']);
    $superAdmin = Role::query()->where('name', 'super_admin')->firstOrFail();

    $superAdmin->permissions()->detach();

    $this->actingAs($manager)->put(route('admin.role-permissions.update', $superAdmin), [
        'permissions' => [],
    ])->assertRedirect(route('admin.role-permissions.index', ['role_id' => $superAdmin->id]));

    expect($superAdmin->fresh()->permissions()->count())->toBe(Permission::query()->count());
});
