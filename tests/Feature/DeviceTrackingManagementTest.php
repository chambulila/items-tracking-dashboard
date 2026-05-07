<?php

use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\Device;
use App\Models\ItemCategory;
use App\Models\LostItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['student', 'security_officer'] as $role) {
        Role::query()->create(['name' => $role, 'label' => str($role)->replace('_', ' ')->title()->toString()]);
    }

    $permissions = collect([
        'view-devices',
        'create-devices',
        'send-device-location',
        'manage-device-tracking',
        'manage-devices',
    ])->mapWithKeys(fn (string $name) => [
        $name => Permission::query()->create(['name' => $name, 'label' => str($name)->replace('-', ' ')->title()->toString()]),
    ]);

    Role::query()->where('name', 'student')->first()
        ->permissions()
        ->attach($permissions->only(['view-devices', 'create-devices', 'send-device-location'])->pluck('id'));

    Role::query()->where('name', 'security_officer')->first()
        ->permissions()
        ->attach($permissions->only(['view-devices', 'manage-device-tracking', 'manage-devices'])->pluck('id'));
});

function deviceTrackingUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->sync(Role::query()->where('name', $role)->first());

    return $user;
}

function deviceTrackingAuthHeaders(User $user): array
{
    return ['Authorization' => 'Bearer '.$user->refreshApiToken()];
}

it('registers only eligible electronic devices and validates linked lost item ownership', function (): void {
    $owner = deviceTrackingUserWithRole('student');
    $otherUser = deviceTrackingUserWithRole('student');
    $campus = Campus::query()->create(['name' => 'Main Campus']);
    $electronic = ItemCategory::query()->create(['name' => 'Laptop', 'is_electronic' => true]);
    $nonElectronic = ItemCategory::query()->create(['name' => 'Bag', 'is_electronic' => false]);

    $electronicLostItem = LostItem::query()->create([
        'user_id' => $owner->id,
        'item_category_id' => $electronic->id,
        'campus_id' => $campus->id,
        'name' => 'Dell Laptop',
        'description' => 'Electronic lost item.',
        'lost_date' => now()->toDateString(),
        'status' => 'open',
    ]);

    $nonElectronicLostItem = LostItem::query()->create([
        'user_id' => $owner->id,
        'item_category_id' => $nonElectronic->id,
        'campus_id' => $campus->id,
        'name' => 'Backpack',
        'description' => 'Non electronic lost item.',
        'lost_date' => now()->toDateString(),
        'status' => 'open',
    ]);

    $otherLostItem = LostItem::query()->create([
        'user_id' => $otherUser->id,
        'item_category_id' => $electronic->id,
        'campus_id' => $campus->id,
        'name' => 'Other Laptop',
        'description' => 'Another user lost this.',
        'lost_date' => now()->toDateString(),
        'status' => 'open',
    ]);

    $this->postJson('/api/devices', [
        'name' => 'Invalid Device',
        'device_type' => 'wallet',
        'device_identifier' => 'bad-001',
    ], deviceTrackingAuthHeaders($owner))->assertUnprocessable();

    $this->postJson('/api/devices', [
        'name' => 'Backpack Tracker',
        'device_type' => 'laptop',
        'device_identifier' => 'bad-002',
        'lost_item_id' => $nonElectronicLostItem->id,
    ], deviceTrackingAuthHeaders($owner))->assertUnprocessable();

    $this->postJson('/api/devices', [
        'name' => 'Other Tracker',
        'device_type' => 'laptop',
        'device_identifier' => 'bad-003',
        'lost_item_id' => $otherLostItem->id,
    ], deviceTrackingAuthHeaders($owner))->assertForbidden();

    $this->postJson('/api/devices', [
        'name' => 'Dell Laptop',
        'device_type' => 'laptop',
        'device_identifier' => 'device-001',
        'brand_model' => 'Dell XPS',
        'serial_imei' => 'ABC123',
        'lost_item_id' => $electronicLostItem->id,
    ], deviceTrackingAuthHeaders($owner))->assertCreated()
        ->assertJsonPath('device_type', 'laptop')
        ->assertJsonPath('lost_item_id', $electronicLostItem->id);
});

it('lets tracking managers enable disable and recover devices from the web dashboard', function (): void {
    $owner = deviceTrackingUserWithRole('student');
    $security = deviceTrackingUserWithRole('security_officer');

    $device = Device::query()->create([
        'user_id' => $owner->id,
        'name' => 'Phone',
        'device_type' => 'phone',
        'device_identifier' => 'phone-001',
    ]);

    $this->actingAs($owner)->patch(route('admin.devices.enable-active-search', $device))
        ->assertForbidden();

    $this->actingAs($security)->get(route('admin.devices', ['lost' => 1]))
        ->assertSuccessful()
        ->assertDontSee('phone-001');

    $this->actingAs($security)->patch(route('admin.devices.enable-active-search', $device))
        ->assertRedirect();

    expect($device->fresh()->tracking_enabled)->toBeTrue()
        ->and($device->fresh()->is_lost)->toBeTrue()
        ->and($device->fresh()->tracking_mode)->toBe('live')
        ->and(AuditLog::query()->where('action', 'device.active_search_enabled')->where('user_id', $security->id)->exists())->toBeTrue();

    $this->actingAs($security)->get(route('admin.devices', ['lost' => 1]))
        ->assertSuccessful()
        ->assertSee('phone-001');

    $this->actingAs($security)->patch(route('admin.devices.mark-recovered', $device))
        ->assertRedirect();

    expect($device->fresh()->tracking_enabled)->toBeFalse()
        ->and($device->fresh()->is_lost)->toBeFalse()
        ->and($device->fresh()->tracking_mode)->toBe('idle')
        ->and($device->fresh()->recovered_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'device.recovered')->where('user_id', $security->id)->exists())->toBeTrue();
});

it('syncs a mobile device by uuid and rejects location updates while tracking is disabled', function (): void {
    $owner = deviceTrackingUserWithRole('student');
    $security = deviceTrackingUserWithRole('security_officer');

    $this->postJson('/api/mobile/devices/sync', [
        'device_uuid' => 'uuid-001',
        'device_name' => 'Jane Phone',
        'device_type' => 'phone',
        'brand' => 'Google',
        'model' => 'Pixel',
        'os_name' => 'android',
        'os_version' => '15',
        'app_version' => '1.0.0',
        'location_permission_status' => 'granted',
    ], deviceTrackingAuthHeaders($owner))->assertCreated()
        ->assertJsonPath('device_uuid', 'uuid-001')
        ->assertJsonPath('user_id', $owner->id);

    $this->getJson('/api/mobile/devices/uuid-001/status', deviceTrackingAuthHeaders($owner))
        ->assertSuccessful()
        ->assertJsonPath('tracking_mode', 'idle')
        ->assertJsonPath('heartbeat_interval_minutes', 15);

    $this->postJson('/api/mobile/devices/uuid-001/location', [
        'latitude' => -6.7924,
        'longitude' => 39.2083,
        'accuracy' => 8,
        'battery_level' => 80,
        'tracking_mode' => 'heartbeat',
        'recorded_at' => now()->toIso8601String(),
    ], deviceTrackingAuthHeaders($owner))->assertUnprocessable()
        ->assertJsonPath('message', 'Tracking is disabled for this device.');

    $this->postJson('/api/mobile/devices/uuid-001/location', [
        'latitude' => -6.7925,
        'longitude' => 39.2084,
        'tracking_mode' => 'live',
    ], deviceTrackingAuthHeaders($owner))->assertUnprocessable();

    $device = Device::query()->where('device_uuid', 'uuid-001')->firstOrFail();
    $this->actingAs($security)->patch(route('admin.devices.enable-active-search', $device))->assertRedirect();

    $this->postJson('/api/mobile/devices/uuid-001/location', [
        'latitude' => -6.7925,
        'longitude' => 39.2084,
        'speed' => 2.5,
        'tracking_mode' => 'live',
    ], deviceTrackingAuthHeaders($owner))->assertCreated()
        ->assertJsonPath('tracking_mode', 'live');

    expect($device->fresh()->last_latitude)->not->toBeNull()
        ->and($device->fresh()->last_longitude)->not->toBeNull()
        ->and($device->fresh()->last_seen_at)->not->toBeNull()
        ->and($device->locations()->count())->toBe(1);

    $this->actingAs($security)->patch(route('admin.devices.disable-tracking', $device))->assertRedirect();

    $this->postJson('/api/mobile/devices/uuid-001/location', [
        'latitude' => -6.7926,
        'longitude' => 39.2085,
        'tracking_mode' => 'live',
    ], deviceTrackingAuthHeaders($owner))->assertUnprocessable()
        ->assertJsonPath('message', 'Tracking is disabled for this device.');
});
