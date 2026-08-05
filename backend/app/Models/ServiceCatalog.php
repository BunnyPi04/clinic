<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCatalog extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_DISCONTINUED = 'discontinued';

    protected $fillable = [
        'code',
        'name',
        'service_type',
        'service_category',
        'default_price',
        'status',
        'is_active',
        'is_highlighted_default',
        'display_on_patient_receipt_default',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_highlighted_default' => 'boolean',
        'display_on_patient_receipt_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function doctorPrices(): HasMany
    {
        return $this->hasMany(DoctorServicePrice::class);
    }

    public function visitServices(): HasMany
    {
        return $this->hasMany(VisitService::class);
    }

    public function scopeSelectable(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_ACTIVE,
            self::STATUS_SUSPENDED,
        ]);
    }
}
