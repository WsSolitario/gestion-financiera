<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    public $incrementing = false; // UUID
    protected $keyType = 'string';
    public $timestamps = false;   // timestamps manejados manualmente

    protected $fillable = [
        'id',
        'from_user_id',
        'to_user_id',
        'group_id',
        'amount',
        'unapplied_amount',
        'note',
        'payment_method',
        'evidence_url',
        'signature',
        'status',
        'payment_date',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    public function payer()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function expenseParticipants()
    {
        return $this->hasMany(ExpenseParticipant::class, 'payment_id');
    }
}
