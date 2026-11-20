<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkMedia extends Model
{
    protected $table = 'park_media';

    protected $fillable = ['park_id', 'type', 'path', 'caption', 'credit', 'sort_order'];

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public function url(): string
    {
        return media_url($this->path);
    }

    /** Turns any YouTube link into an embeddable URL. */
    public function embedUrl(): ?string
    {
        if ($this->type !== 'youtube') {
            return null;
        }

        return youtube_embed($this->path);
    }
}
