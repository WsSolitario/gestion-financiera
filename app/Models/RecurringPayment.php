<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecurringPayment extends Model
{
    use HasFactory;

    protected $table = 'recurring_payments';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'description',
        'amount_monthly',
        'months',
    ];

    protected $casts = [
        'amount_monthly' => 'decimal:2',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function viewers()
    {
        return $this->belongsToMany(User::class, 'recurring_payment_viewers');
    }
}
