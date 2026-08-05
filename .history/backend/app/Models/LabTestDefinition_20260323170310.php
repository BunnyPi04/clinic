<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTestDefinition extends Model
{
    protected $fillable = [
        'test_code',
        'test_name',
        'test_name_normalized',
        'panel_type',
        'sample_group',
        'standard_unit',
        'male_reference_min',
        'male_reference_max',
        'male_reference_text',
        'female_reference_min',
        'female_reference_max',
        'female_reference_text',
        'default_price',
        'default_include_in_billing',
        'is_active',
    ];

    protected $casts = [
        'male_reference_min' => 'decimal:4',
        'male_reference_max' => 'decimal:4',
        'female_reference_min' => 'decimal:4',
        'female_reference_max' => 'decimal:4',
        'default_price' => 'decimal:2',
        'default_include_in_billing' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function labResults(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }
}
