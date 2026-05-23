<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfusionSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'patient_id',
        'device_id',
        'bed_id',
        'started_by_user_id',
        'ended_by_user_id',
        'fluid_name',
        'bag_volume_ml',
        'bag_empty_weight_grams',
        'initial_weight_grams',
        'fluid_density_g_per_ml',
        'started_at',
        'ended_at',
        'status',
        'notes',
        'last_weight_grams',
        'last_remaining_ml',
        'last_flow_ml_per_hour',
        'last_reading_at',
        'patient_name_snapshot',
        'mrn_snapshot',
        'bed_label_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_reading_at' => 'datetime',
            'bag_volume_ml' => 'decimal:2',
            'bag_empty_weight_grams' => 'decimal:2',
            'initial_weight_grams' => 'decimal:2',
            'fluid_density_g_per_ml' => 'decimal:4',
            'last_weight_grams' => 'decimal:2',
            'last_remaining_ml' => 'decimal:2',
            'last_flow_ml_per_hour' => 'decimal:2',
        ];
    }
}
