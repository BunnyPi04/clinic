<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    protected $fillable = [
        'patient_code',
        'full_name',
        'date_of_birth',
        'gender',
        'phone',

        'city',
        'district',
        'street',
        'house_number',

        'paper_book_code',

        'primary_doctor_id',
        'primary_doctor_assigned_at',
        'primary_doctor_note',

        'patient_source_id',
        'patient_source_note',

        'zalo_phone',

        'emergency_contact_name',
        'emergency_contact_phone',

        'notes',
    ];
    protected $casts = [
        'date_of_birth' => 'date',
        'primary_doctor_assigned_at' => 'date',
    ];

    public function primaryDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'primary_doctor_id');
    }

    public function patientSource(): BelongsTo
    {
        return $this->belongsTo(PatientSource::class, 'patient_source_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }
}
