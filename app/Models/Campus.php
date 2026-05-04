<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    protected $fillable = ['name'];

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
