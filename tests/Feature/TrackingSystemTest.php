<?php

use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\Device;
use App\Models\FoundItem;
use App\Models\Incident;
use App\Models\IncidentCategory;
use App\Models\ItemCategory;
use App\Models\ItemClaim;
use App\Models\ItemMatch;
use App\Models\LostItem;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['student', 'staff', 'security_officer', 'administrator'] as $role) {
        Role::query()->create(['name' => $role, 'label' => str($role)->replace('_', ' ')->title()->toString()]);
    }

    $permissions = collect([
        'view-references',
        'view-lost-items',
        'create-lost-items',
        'view-found-items',
        'create-found-items',
        'claim-found-items',
        'verify-claims',
        'view-matches',
        'manage-lost-found',
        'view-devices',
        'manage-devices',
        'view-incidents',
        'create-incidents',
        'manage-incidents',
        'view-notifications',
        'manage-notifications',
        'view-analytics',
        'manage-users',
        'assign-roles',
    ])->mapWithKeys(fn (string $name) => [$name => Permission::query()->create(['name' => $name, 'label' => str($name)->replace('-', ' ')->title()->toString()])]);

    Role::query()->where('name', 'student')->first()
        ->permissions()
        ->attach($permissions->only([
            'view-references',
            'create-lost-items',
            'create-found-items',
            'claim-found-items',
            'view-devices',
            'manage-devices',
            'view-incidents',
            'create-incidents',
            'view-notifications',
            'manage-notifications',
        ])->pluck('id'));

    Role::query()->where('name', 'security_officer')->first()
        ->permissions()
        ->attach($permissions->only([
            'verify-claims',
            'view-matches',
            'manage-lost-found',
            'view-incidents',
            'manage-incidents',
            'view-analytics',
        ])->pluck('id'));

    Role::query()->where('name', 'administrator')->first()
        ->permissions()
        ->attach($permissions->pluck('id'));

    $this->campus = Campus::query()->create(['name' => 'Main Campus']);
    $this->building = $this->campus->buildings()->create(['name' => 'Library']);
    $this->category = ItemCategory::query()->create(['name' => 'Laptop', 'is_electronic' => true]);
    $this->incidentCategory = IncidentCategory::query()->create(['name' => 'Theft']);
});

function userWithRole(string $role): array
{
    $user = User::factory()->create();
    $user->roles()->sync(Role::query()->where('name', $role)->first());

    return [$user, $user->refreshApiToken()];
}

function authHeaders(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

it('registers users and blocks non administrators from role management', function (): void {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Student',
        'email' => 'jane@example.com',
        'password' => 'password123',
    ])->assertCreated();

    $token = $response->json('token');
    $user = User::query()->where('email', 'jane@example.com')->first();

    expect($token)->toBeString()
        ->and($user->roles()->where('name', 'student')->exists())->toBeTrue();

    $this->getJson('/api/admin/users', authHeaders($token))->assertForbidden();

    [$admin, $adminToken] = userWithRole('administrator');

    $this->patchJson("/api/admin/users/{$user->id}/roles", ['roles' => ['staff']], authHeaders($adminToken))
        ->assertSuccessful()
        ->assertJsonPath('roles.0.name', 'staff');

    expect(AuditLog::query()->where('action', 'roles.updated')->where('user_id', $admin->id)->exists())->toBeTrue();
});

it('stores lost and found reports with attachments, generates matches, and exposes notifications', function (): void {
    Storage::fake('public');
    [$owner, $ownerToken] = userWithRole('student');
    [$finder, $finderToken] = userWithRole('student');

    $lostPayload = [
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Dell XPS',
        'description' => 'Silver Dell XPS laptop with stickers',
        'color' => 'silver',
        'brand_model' => 'Dell XPS',
        'serial_imei' => 'ABC123',
        'lost_date' => now()->toDateString(),
        'latitude' => -6.7924,
        'longitude' => 39.2083,
        'attachments' => [UploadedFile::fake()->image('lost.png')],
    ];

    $this->postJson('/api/lost-items', $lostPayload, authHeaders($ownerToken))->assertCreated();

    $foundPayload = [
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Dell XPS',
        'description' => 'Found a Dell XPS laptop near the library',
        'color' => 'silver',
        'brand_model' => 'Dell XPS',
        'serial_imei' => 'ABC123',
        'found_date' => now()->toDateString(),
        'latitude' => -6.7925,
        'longitude' => 39.2084,
        'attachments' => [UploadedFile::fake()->image('found.png')],
    ];

    $this->postJson('/api/found-items', $foundPayload, authHeaders($finderToken))->assertCreated();

    expect(LostItem::query()->first()->attachments()->count())->toBe(1)
        ->and(FoundItem::query()->first()->attachments()->count())->toBe(1)
        ->and(ItemMatch::query()->count())->toBe(1)
        ->and(Notification::query()->where('user_id', $owner->id)->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $finder->id)->exists())->toBeTrue();

    $this->getJson('/api/notifications', authHeaders($ownerToken))
        ->assertSuccessful()
        ->assertJsonPath('data.0.type', 'item_match');
});

it('requires security or admin approval for claims', function (): void {
    [$claimant, $claimantToken] = userWithRole('student');
    [, $finderToken] = userWithRole('student');
    [$security, $securityToken] = userWithRole('security_officer');

    $foundItem = FoundItem::query()->create([
        'finder_id' => User::query()->whereNot('id', $claimant->id)->first()->id,
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Bag',
        'description' => 'Found bag',
        'found_date' => now()->toDateString(),
        'latitude' => -6.79,
        'longitude' => 39.20,
    ]);

    $claim = $this->postJson("/api/found-items/{$foundItem->id}/claims", [
        'proof_description' => 'It has my notebooks and blue key holder.',
    ], authHeaders($claimantToken))->assertCreated()->json();

    $this->patchJson("/api/claims/{$claim['id']}/verify", [
        'status' => 'approved',
    ], authHeaders($finderToken))->assertForbidden();

    $this->patchJson("/api/claims/{$claim['id']}/verify", [
        'status' => 'approved',
        'decision_notes' => 'Proof accepted.',
    ], authHeaders($securityToken))->assertSuccessful();

    expect(ItemClaim::query()->first()->status)->toBe('approved')
        ->and(FoundItem::query()->first()->status)->toBe('claimed')
        ->and(AuditLog::query()->where('action', 'claim.approved')->where('user_id', $security->id)->exists())->toBeTrue();
});

it('only accepts device locations while tracking is enabled and the device is lost', function (): void {
    [, $token] = userWithRole('student');

    $deviceId = $this->postJson('/api/devices', [
        'name' => 'Phone',
        'device_identifier' => 'phone-001',
    ], authHeaders($token))->assertCreated()->json('id');

    $this->getJson("/api/devices/{$deviceId}/status", authHeaders($token))
        ->assertSuccessful()
        ->assertJsonPath('should_send_location', false);

    $this->postJson("/api/devices/{$deviceId}/location", [
        'latitude' => -6.7924,
        'longitude' => 39.2083,
    ], authHeaders($token))->assertUnprocessable();

    $this->patchJson("/api/devices/{$deviceId}", [
        'tracking_enabled' => true,
        'is_lost' => true,
    ], authHeaders($token))->assertSuccessful();

    $this->postJson("/api/devices/{$deviceId}/location", [
        'latitude' => -6.7924,
        'longitude' => 39.2083,
        'accuracy' => 5,
    ], authHeaders($token))->assertCreated();

    $this->patchJson("/api/devices/{$deviceId}", [
        'is_lost' => false,
    ], authHeaders($token))->assertJsonPath('tracking_enabled', false);

    expect(Device::query()->first()->locations()->count())->toBe(1);
});

it('enforces incident transitions, assignment, and audit history', function (): void {
    [$reporter, $reporterToken] = userWithRole('student');
    [$security, $securityToken] = userWithRole('security_officer');

    $incident = $this->postJson('/api/incidents', [
        'incident_category_id' => $this->incidentCategory->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'description' => 'A laptop was stolen from the reading room.',
        'severity' => 'high',
        'latitude' => -6.7924,
        'longitude' => 39.2083,
    ], authHeaders($reporterToken))->assertCreated()->json();

    $this->patchJson("/api/incidents/{$incident['id']}/status", [
        'status' => 'resolved',
    ], authHeaders($securityToken))->assertUnprocessable();

    $this->patchJson("/api/incidents/{$incident['id']}/status", [
        'status' => 'under_review',
        'assigned_to' => $security->id,
        'notes' => 'Assigned to security.',
    ], authHeaders($securityToken))->assertSuccessful();

    expect(Incident::query()->first()->assigned_to)->toBe($security->id)
        ->and(Incident::query()->first()->updates()->count())->toBe(2)
        ->and(AuditLog::query()->where('action', 'incident.status_changed')->where('user_id', $security->id)->exists())->toBeTrue();
});

it('returns filtered analytics metrics', function (): void {
    [, $adminToken] = userWithRole('administrator');
    $otherCampus = Campus::query()->create(['name' => 'North Campus']);

    LostItem::query()->create([
        'user_id' => User::factory()->create()->id,
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'name' => 'Phone',
        'description' => 'Lost phone',
        'lost_date' => now()->toDateString(),
        'latitude' => -6.79,
        'longitude' => 39.20,
        'status' => 'recovered',
    ]);

    FoundItem::query()->create([
        'finder_id' => User::factory()->create()->id,
        'item_category_id' => $this->category->id,
        'campus_id' => $otherCampus->id,
        'name' => 'Card',
        'description' => 'Found card',
        'found_date' => now()->toDateString(),
        'latitude' => -6.79,
        'longitude' => 39.20,
        'status' => 'unclaimed',
    ]);

    $this->getJson("/api/analytics?campus_id={$this->campus->id}", authHeaders($adminToken))
        ->assertSuccessful()
        ->assertJsonPath('metrics.total_lost_items', 1)
        ->assertJsonPath('metrics.total_found_items', 0)
        ->assertJsonPath('metrics.recovery_rate', 100);
});
