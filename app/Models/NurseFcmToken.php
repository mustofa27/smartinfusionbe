<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseFcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'nurse_user_id',
        'fcm_token',
        'app_version',
        'device_os',
        'device_model',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
