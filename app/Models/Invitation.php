<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    public $timestamps   = true;
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $table     = 'invitations';

    protected $fillable = [
        'inviter_id',
        'invitee_email',
        'group_id',
        'token',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
