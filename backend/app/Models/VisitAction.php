<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitAction extends Model
{
    protected $fillable = [
        'visit_id',
        'user_id',
        'action_type',
        'action_label',
        'note',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
