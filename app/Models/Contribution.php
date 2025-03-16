<?php
// app/Models/Contribution.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contribution extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'amount',
        'transaction_date',
        'description',
        'verification_status',
        'verified_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the contribution.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who verified the contribution.
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope a query to only include verified contributions.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    /**
     * Scope a query to only include pending contributions.
     */
    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }
    
    /**
     * Scope a query to only include monthly contributions.
     */
    public function scopeMonthly($query)
    {
        return $query->where('description', 'like', '%Monthly contribution%');
    }
    
    /**
     * Scope a query to only include fines.
     */
    public function scopeFines($query)
    {
        return $query->where('description', 'like', '%fine%');
    }
    
    /**
     * Scope a query to only include welfare fees.
     */
    public function scopeWelfare($query)
    {
        return $query->where('description', 'like', '%welfare%');
    }
    
    /**
     * Scope a query to only include registration fees.
     */
    public function scopeRegistration($query)
    {
        return $query->where('description', 'like', '%registration%');
    }
    
    /**
     * Scope a query to only include OPC contributions.
     */
    public function scopeOpc($query)
    {
        return $query->where('description', 'like', '%Olpajeta%');
    }
    
    /**
     * Check if the contribution is a fine.
     */
    public function isFine()
    {
        return str_contains(strtolower($this->description), 'fine');
    }
    
    /**
     * Check if the contribution is a welfare fee.
     */
    public function isWelfare()
    {
        return str_contains(strtolower($this->description), 'welfare');
    }
    
    /**
     * Check if the contribution is a registration fee.
     */
    public function isRegistration()
    {
        return str_contains(strtolower($this->description), 'registration');
    }
    
    /**
     * Check if the contribution is an OPC contribution.
     */
    public function isOpc()
    {
        return str_contains(strtolower($this->description), 'olpajeta');
    }
    
    /**
     * Check if the contribution is a regular monthly contribution.
     */
    public function isMonthly()
    {
        return str_contains(strtolower($this->description), 'monthly contribution');
    }
}