<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfusionReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'infusion_session_id',
        'device_id',
        'measured_weight_grams',
        'remaining_ml',
        'flow_ml_per_hour',
        'battery_percent',
        'signal_quality',
        'recorded_at',
        'received_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'received_at' => 'datetime',
            'raw_payload' => 'array',
            'measured_weight_grams' => 'decimal:2',
            'remaining_ml' => 'decimal:2',
            'flow_ml_per_hour' => 'decimal:2',
        ];
    }
}
