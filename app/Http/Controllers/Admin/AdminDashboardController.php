<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\GroupAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Get total users count
        $totalUsers = User::where('role', 'user')->count();
        
        // Get total contributions
        $totalContributions = Contribution::verified()->sum('amount');
        
        // Get pending contributions
        $pendingContributions = Contribution::pending()->get();
        
        // Get group accounts
        $groupAccounts = GroupAccount::all();
        
        // Get recent contributions
        $recentContributions = Contribution::with('user')
            ->verified()
            ->orderBy('transaction_date', 'desc')
            ->limit(10)
            ->get();
        
        // Get monthly contribution data for chart
        $monthlyData = $this->getMonthlyContributionData();
        
        return view('admin.dashboard', compact(
            'totalUsers',
            'totalContributions',
            'pendingContributions',
            'groupAccounts',
            'recentContributions',
            'monthlyData'
        ));
    }
    
    private function getMonthlyContributionData()
    {
        // Method implementation remains the same
        // ...
    }
}