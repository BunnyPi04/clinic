<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Doctor extends Model
{
    protected $fillable = [
        'doctor_code',
        'title',
        'full_name',
        'specialty',
        'phone',
        'is_active',
        'hospital_id',
        'hospital_position',
        'consultation_service_catalog_id',
        'employment_status',
        'professional_note',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function primaryPatients(): HasMany
    {
        return $this->hasMany(Patient::class, 'primary_doctor_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class, 'doctor_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(
            DoctorScheduleException::class
        );
    }

    public function consultationService(): BelongsTo
    {
        return $this->belongsTo(
            ServiceCatalog::class,
            'consultation_service_catalog_id'
        );
    }
}
