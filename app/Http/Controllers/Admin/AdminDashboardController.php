<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\GroupAccount;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminDashboardController extends Controller
{
    /**
     * Display admin dashboard with comprehensive statistics
     */
    public function index()
    {
        // Get total members count
        $totalMembers = User::where('role', 'user')->count();
        
        // Get total contributions
        $totalContributions = Contribution::verified()->sum('amount');
        
        // Get pending contributions
        $pendingContributions = Contribution::pending()->count();
        
        // Get total fines
        $totalFines = Contribution::verified()
            ->where('description', 'like', '%fine%')
            ->sum('amount');
        
        // Get total welfare
        $totalWelfare = Contribution::verified()
            ->where('description', 'like', '%welfare%')
            ->sum('amount');
        
        // Get group accounts
        $groupAccounts = GroupAccount::all();
        
        // Total balance across all accounts
        $totalBalance = $groupAccounts->sum('balance');
        
        // Get recent contributions
        $recentContributions = Contribution::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Get monthly contribution data for chart
        $monthlyData = $this->getMonthlyContributionData();
        
        // Calculate compliance statistics
        $complianceStats = $this->calculateComplianceStats();
        
        return view('admin.dashboard', compact(
            'totalMembers',
            'totalContributions',
            'pendingContributions',
            'totalFines',
            'totalWelfare',
            'groupAccounts',
            'totalBalance',
            'recentContributions',
            'monthlyData',
            'complianceStats'
        ));
    }
    
    /**
     * Get monthly contribution data for charts
     */
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
                // Calculate regular contributions
                $regularContributions = $group->filter(function($contribution) {
                    return str_contains($contribution->description, 'Monthly contribution');
                })->sum('amount');
                
                // Calculate fines
                $fines = $group->filter(function($contribution) {
                    return str_contains($contribution->description, 'fine');
                })->sum('amount');
                
                // Calculate welfare
                $welfare = $group->filter(function($contribution) {
                    return str_contains($contribution->description, 'Welfare');
                })->sum('amount');
                
                return [
                    'regular' => $regularContributions,
                    'fines' => $fines,
                    'welfare' => $welfare,
                    'total' => $regularContributions + $fines + $welfare
                ];
            });
        
        $months = [];
        $regular = [];
        $fines = [];
        $welfare = [];
        $totals = [];
        
        // Fill in all months, even those with no contributions
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths(11 - $i);
            $month = $date->format('Y-m');
            $months[] = $date->format('M Y');
            
            $regular[] = $monthlyContributions[$month]['regular'] ?? 0;
            $fines[] = $monthlyContributions[$month]['fines'] ?? 0;
            $welfare[] = $monthlyContributions[$month]['welfare'] ?? 0;
            $totals[] = $monthlyContributions[$month]['total'] ?? 0;
        }
        
        return [
            'months' => $months,
            'regular' => $regular,
            'fines' => $fines,
            'welfare' => $welfare,
            'totals' => $totals,
        ];
    }
    
    /**
     * Calculate compliance statistics for all members
     */
    private function calculateComplianceStats()
    {
        $members = User::where('role', 'user')->get();
        
        $fullCompliance = 0;
        $partialCompliance = 0;
        $lowCompliance = 0;
        
        foreach ($members as $member) {
            // Get all monthly contributions
            $contributions = Contribution::where('user_id', $member->id)
                ->where('verification_status', 'verified')
                ->where('description', 'like', '%Monthly contribution%')
                ->get();
            
            // Get first contribution date
            $firstContribution = $contributions->sortBy('transaction_date')->first();
            
            if ($firstContribution) {
                $startDate = Carbon::parse($firstContribution->transaction_date)->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                
                // Calculate expected number of months
                $expectedMonths = $startDate->diffInMonths($endDate) + 1;
                
                // Get actual number of monthly contributions
                $actualMonths = $contributions->groupBy(function ($contribution) {
                    return Carbon::parse($contribution->transaction_date)->format('Y-m');
                })->count();
                
                // Calculate compliance rate
                $complianceRate = $expectedMonths > 0 ? ($actualMonths / $expectedMonths) * 100 : 0;
                
                if ($complianceRate >= 90) {
                    $fullCompliance++;
                } elseif ($complianceRate >= 50) {
                    $partialCompliance++;
                } else {
                    $lowCompliance++;
                }
            } else {
                $lowCompliance++;
            }
        }
        
        return [
            'fullCompliance' => $fullCompliance,
            'partialCompliance' => $partialCompliance,
            'lowCompliance' => $lowCompliance,
            'fullCompliancePercentage' => $members->count() > 0 ? ($fullCompliance / $members->count()) * 100 : 0,
            'partialCompliancePercentage' => $members->count() > 0 ? ($partialCompliance / $members->count()) * 100 : 0,
            'lowCompliancePercentage' => $members->count() > 0 ? ($lowCompliance / $members->count()) * 100 : 0,
        ];
    }
    
    /**
     * Show member management page
     */
    public function members()
    {
        $members = User::where('role', 'user')
            ->withCount(['contributions as total_contributions' => function($query) {
                $query->where('verification_status', 'verified');
            }])
            ->withSum(['contributions as contribution_amount' => function($query) {
                $query->where('verification_status', 'verified');
            }], 'amount')
            ->orderBy('name')
            ->paginate(15);
        
        return view('admin.members.index', compact('members'));
    }
    
    /**
     * Show member creation form
     */
    public function createMember()
    {
        return view('admin.members.create');
    }
    
    /**
     * Store a new member
     */
    public function storeMember(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:20',
            'id_number' => 'required|string|max:20',
            'registration_fee' => 'required|numeric|min:0',
        ]);
        
        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make('CashCows2024_' . substr($request->phone_number, -4)),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);
        
        // If registration fee is specified, create a contribution record
        if ($request->registration_fee > 0) {
            $mainAccount = GroupAccount::firstOrCreate(
                ['name' => 'Main Savings'],
                [
                    'description' => 'Main group savings account',
                    'balance' => 0,
                ]
            );
            
            // Create contribution
            $contribution = Contribution::create([
                'user_id' => $user->id,
                'amount' => $request->registration_fee,
                'transaction_date' => now(),
                'description' => 'Registration fee',
                'verification_status' => 'verified',
                'verified_by' => Auth::id(),
            ]);
            
            // Create transaction
            Transaction::create([
                'group_account_id' => $mainAccount->id,
                'amount' => $request->registration_fee,
                'type' => 'deposit',
                'description' => "Registration fee from {$user->name}",
                'performed_by' => Auth::id(),
            ]);
            
            // Update account balance
            $mainAccount->increment('balance', $request->registration_fee);
        }
        
        return redirect()->route('admin.members')
            ->with('success', 'Member created successfully. Default password: CashCows2024_' . substr($request->phone_number, -4));
    }
    
    /**
     * Show member edit form
     */
    public function editMember(User $member)
    {
        return view('admin.members.edit', compact('member'));
    }
    
    /**
     * Update a member
     */
    public function updateMember(Request $request, User $member)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $member->id,
            'phone_number' => 'required|string|max:20',
            'id_number' => 'required|string|max:20',
        ]);
        
        $member->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
        ]);
        
        return redirect()->route('admin.members')
            ->with('success', 'Member updated successfully.');
    }
    
    /**
     * Show member details including contributions
     */
    public function showMember(User $member)
    {
        $contributions = Contribution::where('user_id', $member->id)
            ->orderBy('transaction_date', 'desc')
            ->paginate(15);
        
        $totalContribution = Contribution::where('user_id', $member->id)
            ->where('verification_status', 'verified')
            ->sum('amount');
        
        $monthlyData = $this->getMemberMonthlyData($member->id);
        
        return view('admin.members.show', compact('member', 'contributions', 'totalContribution', 'monthlyData'));
    }
    
    /**
     * Get monthly data for a specific member
     */
    private function getMemberMonthlyData($userId)
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
                // Calculate different types of contributions
                $regularContributions = $group->filter(function($contribution) {
                    return str_contains($contribution->description, 'Monthly contribution');
                })->sum('amount');
                
                $fines = $group->filter(function($contribution) {
                    return str_contains($contribution->description, 'fine');
                })->sum('amount');
                
                $welfare = $group->filter(function($contribution) {
                    return str_contains($contribution->description, 'Welfare');
                })->sum('amount');
                
                return [
                    'regular' => $regularContributions,
                    'fines' => $fines,
                    'welfare' => $welfare,
                    'total' => $regularContributions + $fines + $welfare
                ];
            });
        
        $months = [];
        $regular = [];
        $fines = [];
        $welfare = [];
        $totals = [];
        
        // Fill in all months, even those with no contributions
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths(11 - $i);
            $month = $date->format('Y-m');
            $months[] = $date->format('M Y');
            
            // Set target amount based on month
            $monthName = $date->format('F');
            if (in_array($monthName, ['June', 'July'])) {
                $target = 2000;
            } elseif (in_array($monthName, ['August', 'September'])) {
                $target = 2050;
            } else {
                $target = 2200;
            }
            
            $regularAmount = $monthlyContributions[$month]['regular'] ?? 0;
            $fineAmount = $monthlyContributions[$month]['fines'] ?? 0;
            $welfareAmount = $monthlyContributions[$month]['welfare'] ?? 0;
            $totalAmount = $monthlyContributions[$month]['total'] ?? 0;
            
            $regular[] = $regularAmount;
            $fines[] = $fineAmount;
            $welfare[] = $welfareAmount;
            $totals[] = $totalAmount;
            
            $targets[] = $target;
            $compliance[] = $target > 0 ? min(100, round(($regularAmount / $target) * 100, 2)) : 0;
        }
        
        return [
            'months' => $months,
            'regular' => $regular,
            'fines' => $fines,
            'welfare' => $welfare,
            'totals' => $totals,
            'targets' => $targets,
            'compliance' => $compliance,
        ];
    }
    
    /**
     * Delete a member
     */
    public function destroyMember(User $member)
    {
        // Check if there are contributions
        $hasContributions = Contribution::where('user_id', $member->id)->exists();
        
        if ($hasContributions) {
            return redirect()->route('admin.members')
                ->with('error', 'Cannot delete member with existing contributions. Consider deactivating them instead.');
        }
        
        $member->delete();
        
        return redirect()->route('admin.members')
            ->with('success', 'Member deleted successfully.');
    }
    
    /**
     * Show form to add a new contribution for a member
     */
    public function createContribution(User $member)
    {
        return view('admin.contributions.create', compact('member'));
    }
    
    /**
     * Store a new contribution for a member
     */
    public function storeContribution(Request $request, User $member)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'description' => 'required|string',
            'verification_status' => 'required|in:pending,verified',
        ]);
        
        // Create the contribution
        $contribution = Contribution::create([
            'user_id' => $member->id,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $request->description,
            'verification_status' => $request->verification_status,
            'verified_by' => $request->verification_status === 'verified' ? Auth::id() : null,
        ]);
        
        // If verified, create transaction and update account balance
        if ($request->verification_status === 'verified') {
            $mainAccount = GroupAccount::where('name', 'Main Savings')->first();
            
            if ($mainAccount) {
                Transaction::create([
                    'group_account_id' => $mainAccount->id,
                    'amount' => $request->amount,
                    'type' => 'deposit',
                    'description' => "{$request->description} from {$member->name}",
                    'performed_by' => Auth::id(),
                ]);
                
                $mainAccount->increment('balance', $request->amount);
            }
        }
        
        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Contribution added successfully.');
    }
    
    /**
     * Verify a pending contribution
     */
    public function verifyContribution(Contribution $contribution)
    {
        // Update contribution status
        $contribution->update([
            'verification_status' => 'verified',
            'verified_by' => Auth::id(),
        ]);
        
        // Get the member
        $member = User::find($contribution->user_id);
        
        // Create transaction and update account balance
        $mainAccount = GroupAccount::where('name', 'Main Savings')->first();
        
        if ($mainAccount) {
            Transaction::create([
                'group_account_id' => $mainAccount->id,
                'amount' => $contribution->amount,
                'type' => 'deposit',
                'description' => "{$contribution->description} from {$member->name}",
                'performed_by' => Auth::id(),
            ]);
            
            $mainAccount->increment('balance', $contribution->amount);
        }
        
        return redirect()->back()
            ->with('success', 'Contribution verified successfully.');
    }
    
    /**
     * Batch add monthly contributions for all members
     */
    public function batchContributions()
    {
        $members = User::where('role', 'user')->orderBy('name')->get();
        
        return view('admin.contributions.batch', compact('members'));
    }
    
    /**
     * Store batch contributions
     */
    public function storeBatchContributions(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'contribution_type' => 'required|in:monthly,welfare,fine,other',
            'transaction_date' => 'required|date',
            'verification_status' => 'required|in:pending,verified',
            'member_id' => 'required|array',
            'member_id.*' => 'exists:users,id',
            'amount' => 'required|array',
            'amount.*' => 'numeric|min:0',
            'description_template' => 'required|string',
        ]);
        
        $contributionCount = 0;
        $totalAmount = 0;
        
        // Get main account for transactions if contributions are verified
        $mainAccount = null;
        if ($request->verification_status === 'verified') {
            $mainAccount = GroupAccount::where('name', 'Main Savings')->first();
        }
        
        // Process each member's contribution
        foreach ($request->member_id as $index => $memberId) {
            $amount = $request->amount[$index] ?? 0;
            
            // Skip if amount is zero
            if ($amount <= 0) {
                continue;
            }
            
            // Find the member
            $member = User::find($memberId);
            
            if (!$member) {
                continue;
            }
            
            // Create month-specific description
            $month = Carbon::createFromFormat('Y-m', $request->month)->format('F Y');
            $description = str_replace('{month}', $month, $request->description_template);
            
            // Create contribution
            $contribution = Contribution::create([
                'user_id' => $memberId,
                'amount' => $amount,
                'transaction_date' => $request->transaction_date,
                'description' => $description,
                'verification_status' => $request->verification_status,
                'verified_by' => $request->verification_status === 'verified' ? Auth::id() : null,
            ]);
            
            // Create transaction if verified
            if ($request->verification_status === 'verified' && $mainAccount) {
                Transaction::create([
                    'group_account_id' => $mainAccount->id,
                    'amount' => $amount,
                    'type' => 'deposit',
                    'description' => "{$description} from {$member->name}",
                    'performed_by' => Auth::id(),
                ]);
                
                $totalAmount += $amount;
            }
            
            $contributionCount++;
        }
        
        // Update account balance if contributions were verified
        if ($request->verification_status === 'verified' && $mainAccount && $totalAmount > 0) {
            $mainAccount->increment('balance', $totalAmount);
        }
        
        return redirect()->route('admin.dashboard')
            ->with('success', "Added {$contributionCount} contributions successfully for {$request->month}.");
    }
    
    /**
     * Show reports dashboard
     */
    public function reports()
    {
        $members = User::where('role', 'user')->orderBy('name')->get();
        
        return view('admin.reports.index', compact('members'));
    }
    
    /**
     * Generate a comprehensive report
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:all,member,period',
            'member_id' => 'required_if:report_type,member|nullable|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        // Base query
        $query = Contribution::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('verification_status', 'verified');
        
        // If member specific report
        if ($request->report_type === 'member' && $request->member_id) {
            $query->where('user_id', $request->member_id);
            $member = User::find($request->member_id);
            $reportTitle = "Contribution Report for {$member->name}";
        } else {
            $reportTitle = "Contribution Report for All Members";
        }
        
        // Get contributions
        $contributions = $query->with('user')->orderBy('transaction_date')->get();
        
        // Group by member
        $memberContributions = $contributions->groupBy('user_id');
        
        // Calculate totals
        $totalAmount = $contributions->sum('amount');
        $regularTotal = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'Monthly contribution');
        })->sum('amount');
        
        $finesTotal = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'fine');
        })->sum('amount');
        
        $welfareTotal = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'Welfare');
        })->sum('amount');
        
        $otherTotal = $totalAmount - $regularTotal - $finesTotal - $welfareTotal;
        
        return view('admin.reports.show', compact(
            'reportTitle',
            'startDate',
            'endDate',
            'contributions',
            'memberContributions',
            'totalAmount',
            'regularTotal',
            'finesTotal',
            'welfareTotal',
            'otherTotal'
        ));
    }
}