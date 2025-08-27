<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';
    public $incrementing = false; // UUID
    protected $keyType = 'string';
    public $timestamps = false;   // la tabla no tiene created_at/updated_at

    protected $fillable = [
        'id',
        'from_user_id',
        'to_user_id',
        'amount',
        'payment_method',
        'proof_url',
        'signature',
        'status',
        'payment_date',
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
