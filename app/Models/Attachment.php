<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = ['user_id', 'disk', 'path', 'original_name', 'mime_type', 'size'];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
