<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceBedAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'device_id',
        'bed_id',
        'mounted_at',
        'unmounted_at',
        'mounted_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'mounted_at' => 'datetime',
            'unmounted_at' => 'datetime',
        ];
    }
}
