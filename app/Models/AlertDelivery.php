<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'alert_id',
        'user_id',
        'channel',
        'fcm_token',
        'sent_at',
        'delivery_status',
        'provider_message_id',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
