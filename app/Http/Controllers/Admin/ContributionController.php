<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\GroupAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContributionController extends Controller
{
    // Most methods remain the same
    public function index()
{
    $pendingContributions = Contribution::with('user')
        ->pending()
        ->orderBy('transaction_date', 'desc')
        ->get();
        
    $verifiedContributions = Contribution::with(['user', 'verifier'])
        ->verified()
        ->orderBy('transaction_date', 'desc')
        ->paginate(10);
        
    return view('admin.contributions.index', compact('pendingContributions', 'verifiedContributions'));
}
    
    public function create()
    {
        $users = User::where('role', 'user')->get();
        
        return view('admin.contributions.create', compact('users'));
    }
    
    // Other methods remain the same
}