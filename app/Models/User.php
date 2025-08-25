<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;   // UUID
    protected $keyType = 'string';
    public $timestamps = false;     // timestamps manejados por la BD

    protected $fillable = [
        'id',                   // como asignas UUID manualmente
        'name',
        'email',
        'profile_picture_url',
        'phone_number',
        // no incluimos password_hash aquí; lo seteas explícitamente
    ];

    protected $hidden = [
        'password_hash',        // tu esquema usa password_hash
        'remember_token',
    ];

    protected $casts = [
        'id' => 'string',
        'email_verified_at' => 'datetime',
    ];

    public function groups()
    {
        // Si la pivot NO tiene created_at/updated_at, no uses withTimestamps()
        return $this->belongsToMany(Group::class, 'group_members', 'user_id', 'group_id')
            ->withPivot(['role', 'joined_at']);
    }
}
