<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentUpdate extends Model
{
    protected $fillable = ['incident_id', 'user_id', 'from_status', 'to_status', 'notes'];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
