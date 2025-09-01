<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationToken extends Model
{
    public $timestamps = true;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'registration_tokens';

    protected $fillable = [
        'email',
        'token',
        'expires_at',
        'status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
