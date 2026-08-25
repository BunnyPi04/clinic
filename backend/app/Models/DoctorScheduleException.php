<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorScheduleException extends Model
{
    protected $fillable = [
        'doctor_id',
        'work_date',
        'exception_type',
        'start_time',
        'end_time',
        'reason',
    ];

    protected $casts = [
        'work_date' => 'date',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
