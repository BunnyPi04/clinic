<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UltrasoundReport extends Model
{
    protected $fillable = [
        'visit_document_id',
        'body_part',
        'findings_text',
        'conclusion_text',
        'is_manually_corrected',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_manually_corrected' => 'boolean',
    ];

    public function visitDocument(): BelongsTo
    {
        return $this->belongsTo(VisitDocument::class);
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
