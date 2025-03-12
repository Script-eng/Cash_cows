<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_account_id',
        'amount',
        'type',
        'description',
        'performed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function groupAccount()
    {
        return $this->belongsTo(GroupAccount::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
