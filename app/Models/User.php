<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'api_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function lostItems(): HasMany
    {
        return $this->hasMany(LostItem::class);
    }

    public function foundItems(): HasMany
    {
        return $this->hasMany(FoundItem::class, 'finder_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'reporter_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function hasPermission(string ...$permissions): bool
    {
        return $this->permissionNames()->intersect($permissions)->isNotEmpty();
    }

    public function permissionNames(): Collection
    {
        $this->loadMissing('roles.permissions');

        return $this->roles->flatMap->permissions->pluck('name')->unique()->values();
    }

    public function refreshApiToken(): string
    {
        $token = Str::random(80);

        $this->forceFill(['api_token' => hash('sha256', $token)])->save();

        return $token;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
