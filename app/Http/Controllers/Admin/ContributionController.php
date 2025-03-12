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
    public function index()
    {
        $pendingContributions = Contribution::with('user')
            ->pending()
            ->orderBy('transaction_date', 'desc')
            ->paginate(10);
            
        return view('admin.contributions.index', compact('pendingContributions'));
    }
    
    public function create()
    {
        $members = User::where('role', 'member')->get();
        
        return view('admin.contributions.create', compact('members'));
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date|before_or_equal:today',
            'description' => 'nullable|string|max:255',
        ]);
        
        $contribution = Contribution::create([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'verification_status' => 'verified',
            'verified_by' => Auth::id(),
        ]);
        
        // Update group account balance
        $mainAccount = GroupAccount::firstOrCreate(
            ['name' => 'Main Savings'],
            [
                'description' => 'Main group savings account',
                'balance' => 0,
            ]
        );
        
        $mainAccount->increment('balance', $request->amount);
        
        // Record transaction
        Transaction::create([
            'group_account_id' => $mainAccount->id,
            'amount' => $request->amount,
            'type' => 'deposit',
            'description' => 'Contribution from ' . User::find($request->user_id)->name,
            'performed_by' => Auth::id(),
        ]);
        
        return redirect()->route('admin.contributions.index')
            ->with('success', 'Contribution added successfully.');
    }
    
    public function verify(Contribution $contribution)
    {
        // Update contribution status
        $contribution->update([
            'verification_status' => 'verified',
            'verified_by' => Auth::id(),
        ]);
        
        // Update group account balance
        $mainAccount = GroupAccount::firstOrCreate(
            ['name' => 'Main Savings'],
            [
                'description' => 'Main group savings account',
                'balance' => 0,
            ]
        );
        
        $mainAccount->increment('balance', $contribution->amount);
        
        // Record transaction
        Transaction::create([
            'group_account_id' => $mainAccount->id,
            'amount' => $contribution->amount,
            'type' => 'deposit',
            'description' => 'Verified contribution from ' . $contribution->user->name,
            'performed_by' => Auth::id(),
        ]);
        
        return redirect()->route('admin.contributions.index')
            ->with('success', 'Contribution verified successfully.');
    }
}