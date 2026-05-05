<?php

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds all permissions idempotently and grants every permission to super admin', function (): void {
    $this->seed(PermissionSeeder::class);
    $this->seed(PermissionSeeder::class);

    $expectedPermissionCount = collect(PermissionSeeder::groupedPermissions())->flatten(1)->count();
    $superAdmin = Role::query()->where('name', 'super_admin')->firstOrFail();

    expect(Permission::query()->count())->toBe($expectedPermissionCount)
        ->and($superAdmin->permissions()->count())->toBe($expectedPermissionCount);
});

it('does not assign permissions to non super admin roles', function (): void {
    Role::query()->create(['name' => 'staff', 'label' => 'Staff']);

    $this->seed(PermissionSeeder::class);

    expect(Role::query()->where('name', 'staff')->firstOrFail()->permissions()->count())->toBe(0);
});
