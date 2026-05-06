<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function groupedPermissions(): array
    {
        return [
            'dashboard' => [
                'view-dashboard' => 'View admin dashboard',
                'view-whole-dashboard' => 'View all dashboard data',
            ],
            'users' => [
                'manage-users' => 'Create, view, update, and delete users',
                'assign-roles' => 'Assign roles to users',
                'manage-role-permissions' => 'Assign permissions to roles',
            ],
            'lost_found' => [
                'view-lost-items' => 'View lost item records',
                'create-lost-items' => 'Report lost items',
                'update-lost-items' => 'Update lost item records',
                'view-found-items' => 'View found item records',
                'create-found-items' => 'Report found items',
                'claim-found-items' => 'Submit claims for found items',
                'verify-claims' => 'Approve or reject item claims',
                'view-claims' => 'View item claims',
                'view-matches' => 'View lost and found matches',
                'manage-lost-found' => 'Manage lost and found workflows',
            ],
            'incidents' => [
                'view-incidents' => 'View incident reports',
                'create-incidents' => 'Report incidents',
                'manage-incidents' => 'Update incident status, assignment, and lifecycle',
            ],
            'devices' => [
                'view-devices' => 'View tracking devices',
                'create-devices' => 'Register eligible electronic devices',
                'send-device-location' => 'Send mobile device location updates',
                'manage-device-tracking' => 'Enable, disable, and recover device tracking',
                'manage-devices' => 'Manage all registered devices',
            ],
            'analytics' => [
                'view-analytics' => 'View analytics and reports',
            ],
            'notifications' => [
                'view-notifications' => 'View own notifications',
                'manage-notifications' => 'Update notification read status',
            ],
            'references' => [
                'view-references' => 'View reference data',
            ],
        ];
    }

    public function run(): void
    {
        $permissions = collect(self::groupedPermissions())
            ->flatMap(fn (array $group) => collect($group)->map(
                fn (string $label, string $name) => Permission::query()->firstOrCreate(
                    ['name' => $name],
                    ['label' => $label]
                )
            ));

        $superAdmin = Role::query()->firstOrCreate(
            ['name' => 'super_admin'],
            ['label' => 'Super Admin']
        );

        $superAdmin->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
    }
}
