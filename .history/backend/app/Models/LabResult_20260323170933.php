<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = [
        'lab_panel_id',
        'lab_test_definition_id',
        'test_code',
        'test_name_original',
        'test_name_normalized',
        'value_text',
        'value_number',
        'unit',
        'standard_unit',
        'reference_range_text',
        'reference_min',
        'reference_max',
        'reference_source',
        'flag',
        'ai_confidence',
        'price',
        'include_in_billing',
        'billing_note',
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

    public function definition(): BelongsTo
    {
        return $this->belongsTo(LabTestDefinition::class, 'lab_test_definition_id');
    }
}
