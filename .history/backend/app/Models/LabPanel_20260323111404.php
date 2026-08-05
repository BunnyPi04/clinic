<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabPanel extends Model
{
    protected $fillable = [
        'visit_document_id',
        'panel_type',
        'source_name',
        'sample_taken_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'sample_taken_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function visitDocument(): BelongsTo
    {
        return $this->belongsTo(VisitDocument::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class)->orderBy('sort_order')->orderBy('id');
    }
}
