<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get verified contributions for the current user
        $contributions = $user->contributions()
            ->where('verification_status', 'verified')
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get();
        
        // Calculate total contribution amount
        $totalContribution = $user->totalContributions();
        
        // Get monthly contribution data for chart
        $monthlyData = $this->getMonthlyContributionData($user->id);
        
        return view('dashboard.index', compact('contributions', 'totalContribution', 'monthlyData'));
    }
    
    private function getMonthlyContributionData($userId)
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        
        $monthlyContributions = Contribution::where('user_id', $userId)
            ->where('verification_status', 'verified')
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