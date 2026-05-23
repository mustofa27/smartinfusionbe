<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'code',
        'threshold_value',
        'threshold_unit',
        'cooldown_seconds',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'threshold_value' => 'decimal:4',
            'cooldown_seconds' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
