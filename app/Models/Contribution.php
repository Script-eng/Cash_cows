<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contribution extends Model
{
    //// In app/Models/Contribution.php
protected $casts = [
    'transaction_date' => 'date',
    'amount' => 'decimal:2',
];
}
