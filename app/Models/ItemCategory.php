<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    protected $fillable = ['name', 'is_electronic'];

    protected function casts(): array
    {
        return ['is_electronic' => 'boolean'];
    }
}
