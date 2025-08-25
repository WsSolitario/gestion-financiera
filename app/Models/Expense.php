<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $table = 'expenses';
    protected $primaryKey = 'id';
    public $incrementing = false; // UUID
    protected $keyType = 'string';

    // Tu tabla SÍ tiene created_at y updated_at, pero las maneja la BD → para evitar sobrescrituras:
    public $timestamps = false;

    protected $fillable = [
        'id',
        'description',
        'total_amount',
        'payer_id',
        'group_id',
        'ticket_image_url',
        'ocr_status',
        'ocr_raw_text',
        'status',
        'expense_date',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'expense_date' => 'date',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function participants()
    {
        return $this->hasMany(ExpenseParticipant::class, 'expense_id');
    }
}
