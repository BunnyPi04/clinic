<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Visit extends Model
{
    protected $fillable = [
        'visit_code',
        'patient_id',
        'doctor_id',
        'original_doctor_id',
        'is_covering_doctor',
        'visit_date',
        'visit_type',
        'queue_number',
        'priority_flag',
        'priority_type',
        'priority_note',
        'status',
        'cashier_note',
        'clinical_note',
        'created_by',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'is_covering_doctor' => 'boolean',
        'priority_flag' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function originalDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'original_doctor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
