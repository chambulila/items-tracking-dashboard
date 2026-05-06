<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LostItem extends Model
{
    protected $fillable = [
        'user_id',
        'item_category_id',
        'campus_id',
        'building_id',
        'name',
        'description',
        'color',
        'brand_model',
        'serial_imei',
        'lost_date',
        'latitude',
        'longitude',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'lost_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function matches(): HasMany
    {
        return $this->hasMany(ItemMatch::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
