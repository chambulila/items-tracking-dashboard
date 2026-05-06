<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    public const TYPES = ['phone', 'tablet', 'laptop'];

    public const TRACKING_MODES = ['idle', 'heartbeat', 'live'];

    protected $fillable = [
        'user_id',
        'lost_item_id',
        'device_uuid',
        'name',
        'device_type',
        'device_identifier',
        'brand_model',
        'brand',
        'model',
        'os_name',
        'os_version',
        'app_version',
        'serial_imei',
        'manual_imei',
        'serial_number',
        'fcm_token',
        'location_permission_status',
        'tracking_enabled',
        'is_lost',
        'tracking_mode',
        'latitude',
        'longitude',
        'last_latitude',
        'last_longitude',
        'last_accuracy',
        'last_battery_level',
        'last_seen_at',
        'active_search_started_at',
        'active_search_ended_at',
        'recovered_at',
    ];

    protected function casts(): array
    {
        return [
            'tracking_enabled' => 'boolean',
            'is_lost' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'last_latitude' => 'decimal:7',
            'last_longitude' => 'decimal:7',
            'last_accuracy' => 'decimal:2',
            'last_seen_at' => 'datetime',
            'active_search_started_at' => 'datetime',
            'active_search_ended_at' => 'datetime',
            'recovered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lostItem(): BelongsTo
    {
        return $this->belongsTo(LostItem::class);
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
        return $this->tracking_enabled && $this->is_lost && $this->tracking_mode === 'live';
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->gt(now()->subMinutes(5));
    }
}
