<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Contribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContributionController extends Controller
{
    public function index()
    {
        $contributions = Auth::user()->contributions()
            ->orderBy('transaction_date', 'desc')
            ->paginate(10);
            
        return view('contributions.index', compact('contributions'));
    }
    
    public function show(Contribution $contribution)
    {
        // Ensure the user can only view their own contributions
        if ($contribution->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }
        
        return view('contributions.show', compact('contribution'));
    }
}