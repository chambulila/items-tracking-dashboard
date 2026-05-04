<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'device_identifier',
        'brand_model',
        'serial_imei',
        'tracking_enabled',
        'is_lost',
        'recovered_at',
    ];

    protected function casts(): array
    {
        return [
            'tracking_enabled' => 'boolean',
            'is_lost' => 'boolean',
            'recovered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DeviceLocation::class);
    }

    public function latestLocation(): HasOne
    {
        return $this->hasOne(DeviceLocation::class)->latestOfMany('recorded_at');
    }

    public function shouldSendLocation(): bool
    {
        return $this->tracking_enabled && $this->is_lost;
    }
}
