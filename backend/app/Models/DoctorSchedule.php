<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorSchedule extends Model
{
    protected $fillable = [
        'doctor_id',
        'weekday',
        'start_time',
        'end_time',
        'effective_from',
        'effective_to',
        'status',
        'note',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
