<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\IncidentCategory;
use App\Models\ItemCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            'student' => 'Student',
            'staff' => 'Staff',
            'security_officer' => 'Security Officer',
            'administrator' => 'Administrator',
        ])->each(fn (string $label, string $name) => Role::query()->firstOrCreate(['name' => $name], ['label' => $label]));

        collect([
            ['name' => 'Laptop', 'is_electronic' => true],
            ['name' => 'Phone', 'is_electronic' => true],
            ['name' => 'Identity Card', 'is_electronic' => false],
            ['name' => 'Bag', 'is_electronic' => false],
        ])->each(fn (array $category) => ItemCategory::query()->firstOrCreate(['name' => $category['name']], $category));

        collect(['Theft', 'Accident', 'Harassment', 'Safety Hazard'])
            ->each(fn (string $name) => IncidentCategory::query()->firstOrCreate(['name' => $name]));

        $campus = Campus::query()->firstOrCreate(['name' => 'Main Campus']);
        $campus->buildings()->firstOrCreate(['name' => 'Administration Block']);

        $admin = User::factory()->create([
            'name' => 'System Administrator',
            'email' => 'admin@example.com',
        ]);
        $admin->roles()->syncWithoutDetaching(Role::query()->where('name', 'administrator')->first());
    }
}
