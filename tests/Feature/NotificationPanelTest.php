<?php

use App\Models\Campus;
use App\Models\FoundItem;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\ItemCategory;
use App\Models\LostItem;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['student', 'staff', 'security_officer', 'administrator', 'lost_auditor'] as $role) {
        Role::query()->create(['name' => $role, 'label' => str($role)->replace('_', ' ')->title()->toString()]);
    }

    $this->permissions = collect([
        'view-lost-items',
        'view-found-items',
        'manage-lost-found',
        'view-incidents',
        'manage-incidents',
    ])->mapWithKeys(fn (string $name) => [
        $name => Permission::query()->create(['name' => $name, 'label' => str($name)->replace('-', ' ')->title()->toString()]),
    ]);

    Role::query()->where('name', 'administrator')->first()
        ->permissions()
        ->attach($this->permissions->pluck('id'));

    Role::query()->where('name', 'security_officer')->first()
        ->permissions()
        ->attach($this->permissions->only(['manage-lost-found', 'manage-incidents'])->pluck('id'));

    Role::query()->where('name', 'staff')->first()
        ->permissions()
        ->attach($this->permissions->only(['view-lost-items', 'view-found-items', 'view-incidents'])->pluck('id'));

    Role::query()->where('name', 'lost_auditor')->first()
        ->permissions()
        ->attach($this->permissions->only(['view-lost-items'])->pluck('id'));

    $this->campus = Campus::query()->create(['name' => 'Main Campus']);
    $this->building = $this->campus->buildings()->create(['name' => 'Library']);
    $this->category = ItemCategory::query()->create(['name' => 'Phone', 'is_electronic' => true]);
    $this->incidentCategory = IncidentCategory::query()->create(['name' => 'Theft']);
});

function notificationPanelUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->sync(Role::query()->where('name', $role)->first());

    return $user;
}

it('creates lost item notifications for users with matching permissions', function (): void {
    Mail::fake();
    $reporter = notificationPanelUserWithRole('student');
    $administrator = notificationPanelUserWithRole('administrator');
    $security = notificationPanelUserWithRole('security_officer');
    $staff = notificationPanelUserWithRole('staff');
    $auditor = notificationPanelUserWithRole('lost_auditor');

    $this->actingAs($reporter)->post(route('admin.lost-items.store'), [
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Samsung Phone',
        'description' => 'Black Samsung phone lost near the library.',
        'lost_date' => now()->toDateString(),
    ])->assertRedirect();

    foreach ([$administrator, $security, $staff, $auditor] as $recipient) {
        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipient->id,
            'category' => 'lost_item',
            'entity_type' => LostItem::class,
            'created_by' => $reporter->id,
        ]);
    }

    $this->assertDatabaseMissing('notifications', [
        'user_id' => $reporter->id,
        'category' => 'lost_item',
    ]);
});

it('creates found item notifications for authorized users only', function (): void {
    Mail::fake();
    $reporter = notificationPanelUserWithRole('student');
    $administrator = notificationPanelUserWithRole('administrator');
    $staff = notificationPanelUserWithRole('staff');
    $auditor = notificationPanelUserWithRole('lost_auditor');

    $this->actingAs($reporter)->post(route('admin.found-items.store'), [
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Student ID Card',
        'description' => 'Found a student ID card at the library desk.',
        'found_date' => now()->toDateString(),
    ])->assertRedirect();

    foreach ([$administrator, $staff] as $recipient) {
        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipient->id,
            'category' => 'found_item',
            'entity_type' => FoundItem::class,
            'created_by' => $reporter->id,
        ]);
    }

    $this->assertDatabaseMissing('notifications', [
        'user_id' => $auditor->id,
        'category' => 'found_item',
    ]);
});

it('creates incident notifications for users with incident permissions', function (): void {
    $reporter = notificationPanelUserWithRole('student');
    $administrator = notificationPanelUserWithRole('administrator');
    $security = notificationPanelUserWithRole('security_officer');
    $staff = notificationPanelUserWithRole('staff');
    $token = $reporter->refreshApiToken();

    $this->postJson('/api/incidents', [
        'incident_category_id' => $this->incidentCategory->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'description' => 'Theft near Library reported by a student.',
        'severity' => 'high',
        'latitude' => -6.7924,
        'longitude' => 39.2083,
    ], ['Authorization' => "Bearer {$token}"])->assertCreated();

    foreach ([$administrator, $security, $staff] as $recipient) {
        $this->assertDatabaseHas('notifications', [
            'user_id' => $recipient->id,
            'category' => 'incident',
            'entity_type' => Incident::class,
            'created_by' => $reporter->id,
        ]);
    }
});

it('returns only the authenticated users notifications in the panel feed', function (): void {
    $administrator = notificationPanelUserWithRole('administrator');
    $otherUser = notificationPanelUserWithRole('staff');

    $readNotification = Notification::query()->create([
        'user_id' => $administrator->id,
        'type' => 'info',
        'category' => 'lost_item',
        'level' => 'info',
        'title' => 'Older item',
        'message' => 'Older notification',
        'read_at' => now(),
    ]);

    $unreadNotification = Notification::query()->create([
        'user_id' => $administrator->id,
        'type' => 'warning',
        'category' => 'incident',
        'level' => 'warning',
        'title' => 'New incident',
        'message' => 'New notification',
    ]);

    Notification::query()->create([
        'user_id' => $otherUser->id,
        'type' => 'danger',
        'category' => 'incident',
        'level' => 'danger',
        'title' => 'Other notification',
        'message' => 'Should not be exposed',
    ]);

    $this->actingAs($administrator)->getJson(route('admin.notifications.feed'))
        ->assertSuccessful()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('new.0.id', $unreadNotification->id)
        ->assertJsonPath('older.0.id', $readNotification->id)
        ->assertJsonMissing(['title' => 'Other notification']);
});

it('marks notifications as read and blocks access to other users notifications', function (): void {
    $administrator = notificationPanelUserWithRole('administrator');
    $staff = notificationPanelUserWithRole('staff');

    $ownNotification = Notification::query()->create([
        'user_id' => $administrator->id,
        'type' => 'info',
        'category' => 'lost_item',
        'level' => 'info',
        'title' => 'Own notification',
        'message' => 'Can be read',
    ]);

    $otherNotification = Notification::query()->create([
        'user_id' => $staff->id,
        'type' => 'info',
        'category' => 'lost_item',
        'level' => 'info',
        'title' => 'Other notification',
        'message' => 'Cannot be read',
    ]);

    $this->actingAs($administrator)->patchJson(route('admin.notifications.read', $otherNotification))
        ->assertForbidden();

    $this->actingAs($administrator)->patchJson(route('admin.notifications.read', $ownNotification))
        ->assertSuccessful()
        ->assertJsonPath('id', $ownNotification->id);

    expect($ownNotification->fresh()->read_at)->not->toBeNull();
});

it('marks all authenticated user notifications as read', function (): void {
    $administrator = notificationPanelUserWithRole('administrator');
    $staff = notificationPanelUserWithRole('staff');

    Notification::query()->create([
        'user_id' => $administrator->id,
        'type' => 'info',
        'category' => 'lost_item',
        'level' => 'info',
        'title' => 'First',
        'message' => 'First notification',
    ]);

    Notification::query()->create([
        'user_id' => $administrator->id,
        'type' => 'info',
        'category' => 'found_item',
        'level' => 'info',
        'title' => 'Second',
        'message' => 'Second notification',
    ]);

    Notification::query()->create([
        'user_id' => $staff->id,
        'type' => 'info',
        'category' => 'incident',
        'level' => 'info',
        'title' => 'Staff',
        'message' => 'Staff notification',
    ]);

    $this->actingAs($administrator)->patchJson(route('admin.notifications.read-all'))
        ->assertSuccessful()
        ->assertJsonPath('unread_count', 0);

    expect(Notification::query()->where('user_id', $administrator->id)->whereNull('read_at')->count())->toBe(0)
        ->and(Notification::query()->where('user_id', $staff->id)->whereNull('read_at')->count())->toBe(1);
});
