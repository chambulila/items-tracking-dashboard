<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FoundItem extends Model
{
    protected $fillable = [
        'finder_id',
        'item_category_id',
        'campus_id',
        'building_id',
        'name',
        'description',
        'color',
        'brand_model',
        'serial_imei',
        'found_date',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'found_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function finder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finder_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(ItemClaim::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ItemMatch::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
