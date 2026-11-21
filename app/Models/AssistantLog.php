<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantLog extends Model
{
    protected $fillable = ['user_id', 'question', 'answer', 'source', 'intent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
