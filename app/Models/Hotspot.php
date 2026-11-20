<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hotspot extends Model
{
    protected $fillable = ['scene_id', 'target_scene_id', 'type', 'pitch', 'yaw', 'title', 'info_text'];

    protected function casts(): array
    {
        return ['pitch' => 'float', 'yaw' => 'float'];
    }

    public function scene(): BelongsTo
    {
        return $this->belongsTo(Scene::class);
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Scene::class, 'target_scene_id');
    }

    public function toPannellum(): array
    {
        $spot = [
            'pitch' => $this->pitch,
            'yaw' => $this->yaw,
            'type' => $this->type === 'scene' && $this->target_scene_id ? 'scene' : 'info',
            'text' => $this->title ?: ($this->target?->title ?? 'Info'),
        ];

        if ($spot['type'] === 'scene') {
            $spot['sceneId'] = 'scene-'.$this->target_scene_id;
        } elseif ($this->info_text) {
            $spot['text'] = trim(($this->title ? $this->title.': ' : '').$this->info_text);
        }

        return $spot;
    }
}
