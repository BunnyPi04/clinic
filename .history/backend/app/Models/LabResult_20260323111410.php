<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = [
        'lab_panel_id',
        'test_code',
        'test_name_original',
        'test_name_normalized',
        'value_text',
        'value_number',
        'unit',
        'reference_range_text',
        'reference_min',
        'reference_max',
        'flag',
        'ai_confidence',
        'is_manually_corrected',
        'sort_order',
    ];

    protected $casts = [
        'value_number' => 'decimal:4',
        'reference_min' => 'decimal:4',
        'reference_max' => 'decimal:4',
        'ai_confidence' => 'decimal:4',
        'is_manually_corrected' => 'boolean',
    ];

    public function labPanel(): BelongsTo
    {
        return $this->belongsTo(LabPanel::class);
    }
}
