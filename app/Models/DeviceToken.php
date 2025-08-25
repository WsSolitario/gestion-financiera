<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    // ¡OJO! Tu tabla real es user_devices
    protected $table = 'user_devices';

    protected $primaryKey = 'id';
    public $incrementing = false; // UUID
    protected $keyType = 'string';
    public $timestamps = false;   // solo created_at en tu esquema

    protected $fillable = [
        'id',
        'user_id',
        'device_token',
        'device_type',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
