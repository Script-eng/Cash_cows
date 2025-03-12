<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get verified contributions for the current user
        $contributions = Contribution::where('user_id', $user->id)
            ->where('verification_status', 'verified')
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get();
        
        // Calculate total contribution amount
        $totalContribution = Contribution::where('user_id', $user->id)
            ->where('verification_status', 'verified')
            ->sum('amount');
        
        // Get monthly contribution data for chart
        $monthlyData = $this->getMonthlyContributionData($user->id);
        
        // Get compliance percentage
        $totalTarget = $this->getTargetAmount();
        $complianceRate = ($totalContribution / $totalTarget) * 100;
        
        // Get fines amount
        $fines = 0; // You would need to implement this based on your data structure
        
        return view('member.dashboard', compact(
            'contributions',
            'totalContribution',
            'monthlyData',
            'complianceRate',
            'fines'
        ));
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
        $targets = [];
        
        // Fill in all months, even those with no contributions
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths(11 - $i);
            $month = $date->format('Y-m');
            $monthLabel = $date->format('M Y');
            
            $months[] = $monthLabel;
            $amounts[] = $monthlyContributions[$month] ?? 0;
            
            // Set target amount based on month
            $monthName = $date->format('F');
            if (in_array($monthName, ['June', 'July'])) {
                $targets[] = 2000;
            } elseif (in_array($monthName, ['August', 'September'])) {
                $targets[] = 2050;
            } else {
                $targets[] = 2200;
            }
        }
        
        return [
            'months' => $months,
            'amounts' => $amounts,
            'targets' => $targets
        ];
    }
    
    private function getTargetAmount()
    {
        // This should calculate the expected total based on your SACCO rules
        // For example: June, July = 2000, August, September = 2050, rest = 2200
        return 2000 + 2000 + 2050 + 2050 + 2200 + 2200 + 2200;
    }
}