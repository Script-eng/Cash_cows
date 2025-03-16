<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\GroupAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberDashboardController extends Controller
{
    /**
     * Display the member dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get verified contributions for the current user
        $contributions = Contribution::where('user_id', $user->id)
            ->verified()
            ->orderBy('transaction_date', 'desc')
            ->limit(5)
            ->get();
        
        // Get total contribution amount (excluding fines)
        $totalContribution = Contribution::where('user_id', $user->id)
            ->verified()
            ->monthly()
            ->sum('amount');
        
        // Calculate total fines
        $fines = Contribution::where('user_id', $user->id)
            ->verified()
            ->fines()
            ->sum('amount');
        
        // Calculate total welfare payments
        $welfare = Contribution::where('user_id', $user->id)
            ->verified()
            ->welfare()
            ->sum('amount');
        
        // Get OPC contribution
        $opcContribution = Contribution::where('user_id', $user->id)
            ->verified()
            ->opc()
            ->sum('amount');
        
        // Get registration fee
        $registrationFee = Contribution::where('user_id', $user->id)
            ->verified()
            ->registration()
            ->sum('amount');
        
        // Get monthly contribution data for chart
        $monthlyData = $this->getMonthlyContributionData($user->id);
        
        // Get all group accounts info
        $mainAccount = GroupAccount::where('name', 'Main Savings')->first();
        $groupTotalBalance = $mainAccount ? $mainAccount->balance : 0;
        
        // Get member count
        $memberCount = User::where('role', 'member')->count();
        
        // Calculate average contribution per member
        $averagePerMember = $memberCount > 0 ? ($groupTotalBalance / $memberCount) : 0;
        
        // Get compliance rate
        $complianceRate = $this->calculateComplianceRate($user->id);
        
        // Get contribution streak (consecutive months)
        $streak = $this->calculateContributionStreak($user->id);
        
        return view('member.dashboard', compact(
            'contributions',
            'totalContribution',
            'monthlyData',
            'fines',
            'welfare',
            'opcContribution',
            'registrationFee',
            'groupTotalBalance',
            'memberCount',
            'averagePerMember',
            'complianceRate',
            'streak'
        ));
    }
    
    /**
     * Get monthly contribution data for chart.
     */
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
                // Filter to include only monthly contributions (exclude fines, welfare, etc.)
                $monthlyAmount = $group->filter(function ($contribution) {
                    return $contribution->isMonthly();
                })->sum('amount');
                
                // Calculate fines for the month
                $finesAmount = $group->filter(function ($contribution) {
                    return $contribution->isFine();
                })->sum('amount');
                
                // Calculate welfare for the month
                $welfareAmount = $group->filter(function ($contribution) {
                    return $contribution->isWelfare();
                })->sum('amount');
                
                return [
                    'monthly' => $monthlyAmount,
                    'fines' => $finesAmount,
                    'welfare' => $welfareAmount,
                    'total' => $monthlyAmount + $finesAmount + $welfareAmount
                ];
            });
        
        $months = [];
        $amounts = [];
        $targets = [];
        $fines = [];
        $welfare = [];
        $compliance = [];
        
        // Fill in all months, even those with no contributions
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths(11 - $i);
            $month = $date->format('Y-m');
            $monthLabel = $date->format('M Y');
            
            $months[] = $monthLabel;
            
            // Set amounts based on available data
            $amounts[] = $monthlyContributions[$month]['monthly'] ?? 0;
            $fines[] = $monthlyContributions[$month]['fines'] ?? 0;
            $welfare[] = $monthlyContributions[$month]['welfare'] ?? 0;
            
            // Set target amount based on month
            $monthName = $date->format('F');
            if (in_array($monthName, ['June', 'July'])) {
                $targetAmount = 2000;
            } elseif (in_array($monthName, ['August', 'September'])) {
                $targetAmount = 2050;
            } else {
                $targetAmount = 2200;
            }
            
            $targets[] = $targetAmount;
            
            // Calculate compliance percentage
            $monthlyAmount = $monthlyContributions[$month]['monthly'] ?? 0;
            $compliance[] = $targetAmount > 0 ? min(100, round(($monthlyAmount / $targetAmount) * 100, 2)) : 0;
        }
        
        return [
            'months' => $months,
            'amounts' => $amounts,
            'targets' => $targets,
            'fines' => $fines,
            'welfare' => $welfare,
            'compliance' => $compliance
        ];
    }
    
    /**
     * Calculate compliance rate.
     */
    private function calculateComplianceRate($userId)
    {
        // Get total monthly contributions (excluding fines, welfare, etc.)
        $totalMonthlyContributions = Contribution::where('user_id', $userId)
            ->verified()
            ->monthly()
            ->sum('amount');
        
        // Calculate total target amount based on sacco rules
        // June, July = 2000, August, September = 2050, rest = 2200
        $totalTarget = 0;
        
        // Get user's first contribution date
        $firstContribution = Contribution::where('user_id', $userId)
            ->verified()
            ->orderBy('transaction_date', 'asc')
            ->first();
        
        if ($firstContribution) {
            $startDate = Carbon::parse($firstContribution->transaction_date)->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            
            $date = $startDate->copy();
            while ($date->lte($endDate)) {
                $monthName = $date->format('F');
                
                if (in_array($monthName, ['June', 'July'])) {
                    $totalTarget += 2000;
                } elseif (in_array($monthName, ['August', 'September'])) {
                    $totalTarget += 2050;
                } else {
                    $totalTarget += 2200;
                }
                
                $date->addMonth();
            }
        } else {
            return 0; // No contributions yet
        }
        
        // Calculate compliance rate
        $complianceRate = $totalTarget > 0 ? min(100, ($totalMonthlyContributions / $totalTarget) * 100) : 0;
        
        return [
            'totalTarget' => $totalTarget,
            'totalContributed' => $totalMonthlyContributions,
            'complianceRate' => $complianceRate,
            'complianceStatus' => $this->getComplianceStatus($complianceRate)
        ];
    }
    
    /**
     * Get compliance status based on percentage.
     */
    private function getComplianceStatus($compliancePercentage)
    {
        if ($compliancePercentage >= 100) {
            return 'Excellent';
        } elseif ($compliancePercentage >= 90) {
            return 'Good';
        } elseif ($compliancePercentage >= 75) {
            return 'Fair';
        } else {
            return 'Needs Improvement';
        }
    }
    
    /**
     * Calculate contribution streak (consecutive months).
     */
    private function calculateContributionStreak($userId)
    {
        // Get all contribution months
        $contributionMonths = Contribution::where('user_id', $userId)
            ->verified()
            ->monthly()
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->groupBy(function ($contribution) {
                return Carbon::parse($contribution->transaction_date)->format('Y-m');
            })
            ->keys()
            ->toArray();
        
        if (empty($contributionMonths)) {
            return 0;
        }
        
        // Start with the most recent month
        $currentDate = Carbon::now();
        $currentMonth = $currentDate->format('Y-m');
        
        // If the current month isn't in the list, start from the previous month
        if (!in_array($currentMonth, $contributionMonths)) {
            $currentDate = $currentDate->subMonth();
            $currentMonth = $currentDate->format('Y-m');
        }
        
        // Calculate streak
        $streak = 0;
        while (in_array($currentMonth, $contributionMonths)) {
            $streak++;
            $currentDate = $currentDate->subMonth();
            $currentMonth = $currentDate->format('Y-m');
        }
        
        return $streak;
    }
}