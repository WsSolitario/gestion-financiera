<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = 'groups';
    protected $primaryKey = 'id';
    public $incrementing = false; // UUID
    protected $keyType = 'string';

    // La tabla solo tiene created_at. Para evitar que Eloquent quiera updated_at:
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'description',
        'owner_id',
        'created_at',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->withPivot(['role', 'joined_at']);
    }
}
