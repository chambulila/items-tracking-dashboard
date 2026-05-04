<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Building extends Model
{
    protected $fillable = ['campus_id', 'name'];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
