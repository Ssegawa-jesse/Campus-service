<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hotspot extends Model
{
    protected $fillable = ['scene_id', 'target_scene_id', 'activity_id', 'type', 'pitch', 'yaw', 'title', 'info_text', 'audio_path', 'note'];

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

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** Data the public viewer needs for the custom hotspot popup (title, text, audio, "Add to itinerary"). */
    public function toViewer(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'pitch' => $this->pitch,
            'yaw' => $this->yaw,
            'title' => $this->title ?: ($this->target?->title ?? 'Info'),
            'text' => $this->info_text,
            'audio' => $this->audio_path ? media_url($this->audio_path) : null,
            'target' => $this->target_scene_id ? 'scene-'.$this->target_scene_id : null,
            'activity' => $this->activity ? ['slug' => $this->activity->slug, 'name' => $this->activity->name] : null,
        ];
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
