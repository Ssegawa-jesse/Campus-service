<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalResource extends Model
{
    public const TYPES = ['past_paper' => 'Past paper', 'ebook' => 'E-book', 'journal' => 'Journal', 'other' => 'Other'];

    protected $fillable = ['title', 'course', 'year', 'type', 'file_path', 'original_name', 'downloads', 'uploaded_by'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
