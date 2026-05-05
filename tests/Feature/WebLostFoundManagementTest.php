<?php

use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\FoundItem;
use App\Models\ItemCategory;
use App\Models\ItemClaim;
use App\Models\ItemMatch;
use App\Models\LostItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    foreach (['student' => 'Student', 'staff' => 'Staff', 'security_officer' => 'Security Officer', 'administrator' => 'Administrator'] as $name => $label) {
        Role::query()->create(['name' => $name, 'label' => $label]);
    }

    $this->campus = Campus::query()->create(['name' => 'Main Campus']);
    $this->otherCampus = Campus::query()->create(['name' => 'North Campus']);
    $this->building = $this->campus->buildings()->create(['name' => 'Library']);
    $this->category = ItemCategory::query()->create(['name' => 'Laptop', 'is_electronic' => true]);
    $this->otherCategory = ItemCategory::query()->create(['name' => 'Bag', 'is_electronic' => false]);
});

function lostFoundWebUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->roles()->sync(Role::query()->where('name', $role)->first());

    return $user;
}

it('allows authenticated users to report lost items with images and track status', function (): void {
    Storage::fake('public');
    Mail::fake();
    $student = lostFoundWebUserWithRole('student');

    $response = $this->actingAs($student)->post(route('admin.lost-items.store'), [
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Dell XPS',
        'description' => 'Silver Dell laptop lost in the library.',
        'color' => 'silver',
        'brand_model' => 'Dell XPS',
        'serial_imei' => 'DX-123',
        'lost_date' => now()->toDateString(),
        'latitude' => -6.7924,
        'longitude' => 39.2083,
        'attachments' => [UploadedFile::fake()->image('lost-laptop.png')],
    ]);

    $item = LostItem::query()->firstOrFail();

    $response->assertRedirect(route('admin.lost-items.show', $item));

    expect($item->status)->toBe('open')
        ->and($item->attachments()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'lost_item.created')->exists())->toBeTrue();

    Storage::disk('public')->assertExists($item->attachments()->first()->path);

    $this->actingAs($student)->get(route('admin.lost-items.show', $item))
        ->assertSuccessful()
        ->assertSee('Dell XPS')
        ->assertSee('Open');

    $this->actingAs($student)->patch(route('admin.lost-items.recovered', $item))
        ->assertRedirect();

    expect($item->fresh()->status)->toBe('recovered');
});

it('allows authenticated users to report found items with images', function (): void {
    Storage::fake('public');
    Mail::fake();
    $staff = lostFoundWebUserWithRole('staff');

    $response = $this->actingAs($staff)->post(route('admin.found-items.store'), [
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Dell XPS',
        'description' => 'Found silver Dell laptop near the library desk.',
        'color' => 'silver',
        'brand_model' => 'Dell XPS',
        'serial_imei' => 'DX-123',
        'found_date' => now()->toDateString(),
        'latitude' => -6.7925,
        'longitude' => 39.2084,
        'attachments' => [UploadedFile::fake()->image('found-laptop.png')],
    ]);

    $item = FoundItem::query()->firstOrFail();

    $response->assertRedirect(route('admin.found-items.show', $item));

    expect($item->status)->toBe('unclaimed')
        ->and($item->attachments()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'found_item.created')->exists())->toBeTrue();

    Storage::disk('public')->assertExists($item->attachments()->first()->path);
});

it('shows validation errors when required report fields are missing', function (): void {
    $student = lostFoundWebUserWithRole('student');

    $this->actingAs($student)->from(route('admin.lost-items.create'))->post(route('admin.lost-items.store'), [])
        ->assertRedirect(route('admin.lost-items.create'))
        ->assertSessionHasErrors(['item_category_id', 'campus_id', 'name', 'description', 'lost_date'])
        ->assertSessionDoesntHaveErrors(['latitude', 'longitude']);
});

it('allows reports to be submitted without manually entering coordinates', function (): void {
    Mail::fake();
    $student = lostFoundWebUserWithRole('student');

    $this->actingAs($student)->post(route('admin.lost-items.store'), [
        'item_category_id' => $this->otherCategory->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Notebook',
        'description' => 'A handwritten course notebook.',
        'lost_date' => now()->toDateString(),
    ])->assertRedirect();

    $item = LostItem::query()->where('name', 'Notebook')->firstOrFail();

    expect($item->latitude)->toBeNull()
        ->and($item->longitude)->toBeNull();

    $this->actingAs($student)->get(route('admin.lost-items.show', $item))
        ->assertSuccessful()
        ->assertSee('Not captured');
});

it('filters lost and found items by keyword category date campus and status', function (): void {
    $student = lostFoundWebUserWithRole('student');

    LostItem::query()->create([
        'user_id' => $student->id,
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Silver Laptop',
        'description' => 'Dell laptop',
        'color' => 'silver',
        'lost_date' => '2026-05-01',
        'latitude' => -6.79,
        'longitude' => 39.20,
        'status' => 'open',
    ]);

    LostItem::query()->create([
        'user_id' => $student->id,
        'item_category_id' => $this->otherCategory->id,
        'campus_id' => $this->otherCampus->id,
        'name' => 'Blue Bag',
        'description' => 'Backpack',
        'color' => 'blue',
        'lost_date' => '2026-04-01',
        'latitude' => -6.70,
        'longitude' => 39.10,
        'status' => 'recovered',
    ]);

    $this->actingAs($student)->get(route('admin.lost-items', [
        'keyword' => 'Silver',
        'category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'status' => 'open',
        'from' => '2026-05-01',
        'to' => '2026-05-31',
    ]))
        ->assertSuccessful()
        ->assertSee('Silver Laptop')
        ->assertDontSee('Blue Bag');
});

it('allows users to submit claims and tracks rejected claims without claiming the item', function (): void {
    $finder = lostFoundWebUserWithRole('staff');
    $claimant = lostFoundWebUserWithRole('student');
    $security = lostFoundWebUserWithRole('security_officer');

    $item = FoundItem::query()->create([
        'finder_id' => $finder->id,
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Laptop Charger',
        'description' => 'Found charger',
        'found_date' => now()->toDateString(),
        'latitude' => -6.79,
        'longitude' => 39.20,
        'status' => 'unclaimed',
    ]);

    $this->actingAs($claimant)->post(route('admin.found-items.claims.store', $item), [
        'proof_description' => 'It has my initials on the cable tag.',
    ])->assertRedirect(route('admin.found-items.show', $item));

    $claim = ItemClaim::query()->firstOrFail();

    expect($claim->status)->toBe('pending');

    $this->actingAs($security)->patch(route('admin.claims.verify', $claim), [
        'status' => 'rejected',
        'decision_notes' => 'Proof did not match.',
    ])->assertRedirect();

    expect($claim->fresh()->status)->toBe('rejected')
        ->and($item->fresh()->status)->toBe('unclaimed');

    $this->actingAs($claimant)->get(route('admin.claims'))
        ->assertSuccessful()
        ->assertSee('Rejected');
});

it('requires security or administrators to approve claims and marks found item claimed', function (): void {
    $finder = lostFoundWebUserWithRole('staff');
    $claimant = lostFoundWebUserWithRole('student');
    $security = lostFoundWebUserWithRole('security_officer');

    $item = FoundItem::query()->create([
        'finder_id' => $finder->id,
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'name' => 'Student ID',
        'description' => 'Found student ID',
        'found_date' => now()->toDateString(),
        'latitude' => -6.79,
        'longitude' => 39.20,
        'status' => 'unclaimed',
    ]);

    $claim = ItemClaim::query()->create([
        'found_item_id' => $item->id,
        'claimant_id' => $claimant->id,
        'proof_description' => 'My name and registration number are on it.',
        'status' => 'pending',
    ]);

    $this->actingAs($claimant)->patch(route('admin.claims.verify', $claim), [
        'status' => 'approved',
    ])->assertForbidden();

    $this->actingAs($security)->patch(route('admin.claims.verify', $claim), [
        'status' => 'approved',
        'decision_notes' => 'Identity confirmed.',
    ])->assertRedirect();

    expect($claim->fresh()->status)->toBe('approved')
        ->and($item->fresh()->status)->toBe('claimed')
        ->and(AuditLog::query()->where('action', 'claim.approved')->where('user_id', $security->id)->exists())->toBeTrue();
});

it('shows possible matches when lost and found items share category and imei even if names differ', function (): void {
    Mail::fake();
    $owner = lostFoundWebUserWithRole('student');
    $finder = lostFoundWebUserWithRole('staff');

    $lostItem = LostItem::query()->create([
        'user_id' => $owner->id,
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'My personal computer',
        'description' => 'Laptop missing from the library.',
        'serial_imei' => 'IMEI-9988-XYZ',
        'lost_date' => now()->toDateString(),
        'latitude' => -6.7924,
        'longitude' => 39.2083,
        'status' => 'open',
    ]);

    $foundItem = FoundItem::query()->create([
        'finder_id' => $finder->id,
        'item_category_id' => $this->category->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Unknown laptop',
        'description' => 'Found a laptop with serial label.',
        'serial_imei' => 'imei9988xyz',
        'found_date' => now()->toDateString(),
        'latitude' => -6.7926,
        'longitude' => 39.2085,
        'status' => 'unclaimed',
    ]);

    $this->actingAs($owner)->get(route('admin.lost-items.show', $lostItem))
        ->assertSuccessful()
        ->assertSee('Possible Matches')
        ->assertSee('Unknown laptop')
        ->assertSee('serial or IMEI matches');

    $this->actingAs($finder)->get(route('admin.found-items.show', $foundItem))
        ->assertSuccessful()
        ->assertSee('Possible Matches')
        ->assertSee('My personal computer')
        ->assertSee('serial or IMEI matches');

    expect(ItemMatch::query()->whereBelongsTo($lostItem)->whereBelongsTo($foundItem)->exists())->toBeTrue();
});

it('shows possible matches from same category close date and nearby location', function (): void {
    Mail::fake();
    $owner = lostFoundWebUserWithRole('student');
    $finder = lostFoundWebUserWithRole('staff');

    $lostItem = LostItem::query()->create([
        'user_id' => $owner->id,
        'item_category_id' => $this->otherCategory->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Black backpack',
        'description' => 'Bag lost near library entrance.',
        'lost_date' => '2026-05-01',
        'latitude' => -6.7924,
        'longitude' => 39.2083,
        'status' => 'open',
    ]);

    $foundItem = FoundItem::query()->create([
        'finder_id' => $finder->id,
        'item_category_id' => $this->otherCategory->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Unmarked bag',
        'description' => 'Found near the same entrance.',
        'found_date' => '2026-05-03',
        'latitude' => -6.7928,
        'longitude' => 39.2087,
        'status' => 'unclaimed',
    ]);

    $this->actingAs($owner)->get(route('admin.lost-items.show', $lostItem))
        ->assertSuccessful()
        ->assertSee('Unmarked bag')
        ->assertSee('dates are close')
        ->assertSee('locations are close');

    $this->actingAs($finder)->get(route('admin.found-items.show', $foundItem))
        ->assertSuccessful()
        ->assertSee('Black backpack')
        ->assertSee('same campus')
        ->assertSee('same building');

    expect(ItemMatch::query()->whereBelongsTo($lostItem)->whereBelongsTo($foundItem)->first()?->score)->toBeGreaterThanOrEqual(55);
});

it('dynamically matches non electronic items without imei or exact gps coordinates', function (): void {
    Mail::fake();
    $owner = lostFoundWebUserWithRole('student');
    $finder = lostFoundWebUserWithRole('staff');

    $lostItem = LostItem::query()->create([
        'user_id' => $owner->id,
        'item_category_id' => $this->otherCategory->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Blue backpack',
        'description' => 'Blue school backpack with notebooks inside.',
        'color' => 'blue',
        'lost_date' => '2026-05-02',
        'status' => 'open',
    ]);

    $foundItem = FoundItem::query()->create([
        'finder_id' => $finder->id,
        'item_category_id' => $this->otherCategory->id,
        'campus_id' => $this->campus->id,
        'building_id' => $this->building->id,
        'name' => 'Backpack',
        'description' => 'Found blue backpack with books.',
        'color' => 'blue',
        'found_date' => '2026-05-04',
        'status' => 'unclaimed',
    ]);

    $this->actingAs($owner)->get(route('admin.lost-items.show', $lostItem))
        ->assertSuccessful()
        ->assertSee('Backpack')
        ->assertSee('name is similar')
        ->assertSee('color matches')
        ->assertDontSee('locations are close');

    $this->actingAs($finder)->get(route('admin.found-items.show', $foundItem))
        ->assertSuccessful()
        ->assertSee('Blue backpack');

    expect(ItemMatch::query()->whereBelongsTo($lostItem)->whereBelongsTo($foundItem)->exists())->toBeTrue();
});
