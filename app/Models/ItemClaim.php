<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemClaim extends Model
{
    protected $fillable = [
        'found_item_id',
        'claimant_id',
        'verified_by',
        'proof_description',
        'status',
        'verified_at',
        'decision_notes',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function foundItem(): BelongsTo
    {
        return $this->belongsTo(FoundItem::class);
    }

    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimant_id');
    }
}
