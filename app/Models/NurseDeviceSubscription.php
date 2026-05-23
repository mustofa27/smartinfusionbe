<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseDeviceSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'nurse_user_id',
        'device_id',
    ];
}
