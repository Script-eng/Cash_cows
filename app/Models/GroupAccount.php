<?php
// app/Models/GroupAccount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}