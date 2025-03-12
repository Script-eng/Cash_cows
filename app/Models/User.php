<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone_number',
        'profile_picture',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    // In User model
public function personalReports()
{
    return $this->hasMany(Report::class, 'user_id');
}

public function generatedReports()
{
    return $this->hasMany(Report::class, 'generated_by');
}

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function verifiedContributions()
    {
        return $this->hasMany(Contribution::class, 'verified_by');
    }

    
    

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'performed_by');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function totalContributions()
    {
        return $this->contributions()
            ->where('verification_status', 'verified')
            ->sum('amount');
    }
}
