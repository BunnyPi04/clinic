<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClinicalNote extends Model
{
    protected $fillable = [
        'visit_id',
        'source_document_id',
        'note_type',
        'content_text',
        'is_manually_corrected',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_manually_corrected' => 'boolean',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(VisitDocument::class, 'source_document_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
