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
        // Get total members count
        $totalMembers = User::where('role', 'member')->count();
        
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
            'totalMembers',
            'totalContributions',
            'pendingContributions',
            'groupAccounts',
            'recentContributions',
            'monthlyData'
        ));
    }
    
    private function getMonthlyContributionData()
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        
        $monthlyContributions = Contribution::where('verification_status', 'verified')
            ->where('transaction_date', '>=', $startDate)
            ->orderBy('transaction_date')
            ->get()
            ->groupBy(function ($contribution) {
                return Carbon::parse($contribution->transaction_date)->format('Y-m');
            })
            ->map(function ($group) {
                return $group->sum('amount');
            });
        
        $months = [];
        $amounts = [];
        
        // Fill in all months, even those with no contributions
        for ($i = 0; $i < 12; $i++) {
            $month = Carbon::now()->subMonths(11 - $i)->format('Y-m');
            $months[] = Carbon::now()->subMonths(11 - $i)->format('M Y');
            $amounts[] = $monthlyContributions[$month] ?? 0;
        }
        
        return [
            'months' => $months,
            'amounts' => $amounts,
        ];
    }
}