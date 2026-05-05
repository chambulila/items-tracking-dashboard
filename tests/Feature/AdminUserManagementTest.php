<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['student' => 'Student', 'staff' => 'Staff', 'security_officer' => 'Security Officer', 'administrator' => 'Administrator'] as $name => $label) {
        Role::query()->create(['name' => $name, 'label' => $label]);
    }
});

function webUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->sync(Role::query()->where('name', $role)->first());

    return $user;
}

it('uses laravel web authentication for the admin dashboard', function (): void {
    $admin = webUserWithRole('administrator');

    $this->get('/admin')->assertRedirect(route('login'));

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);

    $this->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();
});

it('only administrators can access user management screens', function (): void {
    $student = webUserWithRole('student');
    $admin = webUserWithRole('administrator');

    $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();

    $this->actingAs($admin)->get(route('admin.users.index'))->assertSuccessful();
});

it('allows administrators to create users and assign roles', function (): void {
    $admin = webUserWithRole('administrator');
    $staffRole = Role::query()->where('name', 'staff')->first();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Staff Member',
        'email' => 'staff@example.com',
        'phone' => '+255700000000',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => [$staffRole->id],
    ])->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', 'staff@example.com')->firstOrFail();

    expect(Hash::check('password123', $user->password))->toBeTrue()
        ->and($user->roles()->where('name', 'staff')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'user.created')->where('user_id', $admin->id)->exists())->toBeTrue();
});

it('allows administrators to update users and replace assigned roles', function (): void {
    $admin = webUserWithRole('administrator');
    $user = webUserWithRole('student');
    $securityRole = Role::query()->where('name', 'security_officer')->first();

    $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => 'Security User',
        'email' => $user->email,
        'phone' => '+255711111111',
        'password' => '',
        'password_confirmation' => '',
        'roles' => [$securityRole->id],
    ])->assertRedirect(route('admin.users.index'));

    $user->refresh();

    expect($user->name)->toBe('Security User')
        ->and($user->roles()->where('name', 'security_officer')->exists())->toBeTrue()
        ->and($user->roles()->where('name', 'student')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'user.updated')->where('user_id', $admin->id)->exists())->toBeTrue();
});

it('allows administrators to delete other users but not themselves', function (): void {
    $admin = webUserWithRole('administrator');
    $user = webUserWithRole('student');

    $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))
        ->assertSessionHasErrors('user');

    $this->actingAs($admin)->delete(route('admin.users.destroy', $user))
        ->assertRedirect(route('admin.users.index'));

    expect(User::query()->whereKey($admin)->exists())->toBeTrue()
        ->and(User::query()->whereKey($user)->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'user.deleted')->where('user_id', $admin->id)->exists())->toBeTrue();
});
