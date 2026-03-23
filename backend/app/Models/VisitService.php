<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitService extends Model
{
    protected $fillable = [
        'visit_id',
        'service_type',
        'service_category',
        'service_code',
        'service_name',
        'doctor_id',
        'unit_price',
        'quantity',
        'amount',
        'is_highlighted',
        'is_custom',
        'display_on_patient_receipt',
        'sort_order',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_highlighted' => 'boolean',
        'is_custom' => 'boolean',
        'display_on_patient_receipt' => 'boolean',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
