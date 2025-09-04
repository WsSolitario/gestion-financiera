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
        'title',
        'description',
        'amount_monthly',
        'months',
        'start_date',
        'day_of_month',
        'reminder_days_before',
    ];

    protected $casts = [
        'amount_monthly' => 'decimal:2',
        'start_date' => 'date',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
