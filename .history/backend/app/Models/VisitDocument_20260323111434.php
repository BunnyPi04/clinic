<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitDocument extends Model
{
    protected $fillable = [
        'visit_id',
        'document_type',
        'title',
        'description',
        'file_path',
        'file_name',
        'original_file_name',
        'mime_type',
        'file_size',
        'captured_at',
        'uploaded_by',
        'ai_status',
        'review_status',
        'ai_raw_text',
        'ai_structured_data_json',
        'ai_error_message',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'ai_structured_data_json' => 'array',
    ];

    protected $appends = [
        'file_url',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return asset('storage/' . $this->file_path);
    }

    public function labPanels(): HasMany
    {
        return $this->hasMany(LabPanel::class);
    }
}
