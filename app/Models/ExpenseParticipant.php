<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseParticipant extends Model
{
    protected $table = 'expense_participants';
    protected $primaryKey = 'id';
    public $incrementing = false; // UUID
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'expense_id',
        'user_id',
        'amount_due',
        'is_paid',
        'payment_id',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'is_paid'    => 'boolean',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
