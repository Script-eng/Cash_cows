<?php
// app/Models/Transaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_account_id',
        'amount',
        'type',
        'description',
        'performed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the group account that owns the transaction.
     */
    public function groupAccount()
    {
        return $this->belongsTo(GroupAccount::class);
    }

    /**
     * Get the user who performed the transaction.
     */
    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
    
    /**
     * Scope a query to only include deposit transactions.
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }
    
    /**
     * Scope a query to only include withdrawal transactions.
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdrawal');
    }
    
    /**
     * Scope a query to only include transfer transactions.
     */
    public function scopeTransfers($query)
    {
        return $query->where('type', 'transfer');
    }
    
    /**
     * Scope a query to only include monthly contribution transactions.
     */
    public function scopeMonthlyContributions($query)
    {
        return $query->where('description', 'like', '%Monthly contribution%');
    }
    
    /**
     * Scope a query to only include fine transactions.
     */
    public function scopeFines($query)
    {
        return $query->where('description', 'like', '%fine%');
    }
    
    /**
     * Scope a query to only include welfare transactions.
     */
    public function scopeWelfare($query)
    {
        return $query->where('description', 'like', '%welfare%');
    }
    
    /**
     * Scope a query to only include registration transactions.
     */
    public function scopeRegistration($query)
    {
        return $query->where('description', 'like', '%registration%');
    }
    
    /**
     * Scope a query to only include OPC transactions.
     */
    public function scopeOpc($query)
    {
        return $query->where('description', 'like', '%OPC%');
    }
}