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
use Illuminate\Support\Str;

class AdminMemberController extends Controller
{
    /**
     * Display a listing of the members
     */
    public function index(Request $request)
    {
        // Handle search and filtering
        $query = User::where('role', 'user');
        
        // Search by name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }
        
        // Filter by compliance
        if ($request->has('compliance') && in_array($request->compliance, ['high', 'medium', 'low'])) {
            // We'll need to calculate compliance for each user
            // For efficiency, we'll get all users first, then filter in PHP
            $allMembers = $query->get();
            
            $filteredIds = [];
            foreach ($allMembers as $member) {
                $complianceRate = $this->calculateMemberCompliance($member->id);
                
                if ($request->compliance === 'high' && $complianceRate >= 90) {
                    $filteredIds[] = $member->id;
                } elseif ($request->compliance === 'medium' && $complianceRate >= 50 && $complianceRate < 90) {
                    $filteredIds[] = $member->id;
                } elseif ($request->compliance === 'low' && $complianceRate < 50) {
                    $filteredIds[] = $member->id;
                }
            }
            
            // Apply the filter
            $query = User::whereIn('id', $filteredIds);
        }
        
        // Apply sorting
        $sortField = $request->sort_by ?? 'name';
        $sortDirection = $request->sort_direction ?? 'asc';
        
        if (in_array($sortField, ['name', 'email', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }
        
        // Get paginated results with contribution counts and sums
        $members = $query->withCount([
                'contributions as total_contributions' => function($query) {
                    $query->where('verification_status', 'verified');
                }
            ])
            ->withSum([
                'contributions as contribution_amount' => function($query) {
                    $query->where('verification_status', 'verified');
                }
            ], 'amount')
            ->paginate(15)
            ->appends($request->except('page'));
        
        // Calculate compliance rate for each member
        foreach ($members as $member) {
            $member->compliance_rate = $this->calculateMemberCompliance($member->id);
            $member->compliance_status = $this->getComplianceStatus($member->compliance_rate);
        }
        
        return view('admin.members.index', compact('members'));
    }
    
    /**
     * Calculate a member's compliance rate
     */
    private function calculateMemberCompliance($userId)
    {
        // Get all monthly contributions
        $contributions = Contribution::where('user_id', $userId)
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
            return $expectedMonths > 0 ? ($actualMonths / $expectedMonths) * 100 : 0;
        }
        
        return 0;
    }
    
    /**
     * Get compliance status text based on rate
     */
    private function getComplianceStatus($rate)
    {
        if ($rate >= 90) {
            return 'Excellent';
        } elseif ($rate >= 75) {
            return 'Good';
        } elseif ($rate >= 50) {
            return 'Fair';
        } else {
            return 'Poor';
        }
    }
    
    /**
     * Show the form for creating a new member
     */
    public function create()
    {
        return view('admin.members.create');
    }
    
    /**
     * Store a newly created member
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:20',
            'registration_fee' => 'nullable|numeric|min:0',
        ]);
        
        // Generate a secure password
        $phoneDigits = substr($request->phone_number, -4);
        $password = 'CashCows2024_' . $phoneDigits;
        
        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($password),
            'role' => 'user',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);
        
        // Add registration fee if provided
        if ($request->filled('registration_fee') && $request->registration_fee > 0) {
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
        
        return redirect()->route('admin.members.index')
            ->with('success', "Member created successfully. Temporary password: {$password}");
    }
    
    /**
     * Display the specified member's details
     */
    public function show(User $member)
    {
        // Get member contributions with pagination
        $contributions = Contribution::where('user_id', $member->id)
            ->orderBy('transaction_date', 'desc')
            ->paginate(15);
        
        // Calculate total verified contributions
        $totalContribution = Contribution::where('user_id', $member->id)
            ->where('verification_status', 'verified')
            ->sum('amount');
        
        // Get monthly contribution statistics
        $monthlyData = $this->getMemberMonthlyData($member->id);
        
        // Calculate compliance rate
        $complianceRate = $this->calculateMemberCompliance($member->id);
        $complianceStatus = $this->getComplianceStatus($complianceRate);
        
        // Calculate breakdown of contribution types
        $regularContributions = Contribution::where('user_id', $member->id)
            ->where('verification_status', 'verified')
            ->where('description', 'like', '%Monthly contribution%')
            ->sum('amount');
            
        $fines = Contribution::where('user_id', $member->id)
            ->where('verification_status', 'verified')
            ->where('description', 'like', '%fine%')
            ->sum('amount');
            
        $welfare = Contribution::where('user_id', $member->id)
            ->where('verification_status', 'verified')
            ->where('description', 'like', '%Welfare%')
            ->sum('amount');
            
        $registration = Contribution::where('user_id', $member->id)
            ->where('verification_status', 'verified')
            ->where('description', 'like', '%Registration%')
            ->sum('amount');
            
        $opc = Contribution::where('user_id', $member->id)
            ->where('verification_status', 'verified')
            ->where('description', 'like', '%Olpajeta%')
            ->sum('amount');
            
        $other = $totalContribution - $regularContributions - $fines - $welfare - $registration - $opc;
        
        $contributionBreakdown = [
            'regular' => $regularContributions,
            'fines' => $fines,
            'welfare' => $welfare,
            'registration' => $registration,
            'opc' => $opc,
            'other' => $other,
        ];
        
        return view('admin.members.show', compact(
            'member',
            'contributions',
            'totalContribution',
            'monthlyData',
            'complianceRate',
            'complianceStatus',
            'contributionBreakdown'
        ));
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
        $targets = [];
        $compliance = [];
        
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
     * Show the form for editing the specified member
     */
    public function edit(User $member)
    {
        return view('admin.members.edit', compact('member'));
    }
    
    /**
     * Update the specified member in storage
     */
    public function update(Request $request, User $member)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $member->id,
            'phone_number' => 'required|string|max:20',
        ]);
        
        $member->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
        ]);
        
        return redirect()->route('admin.members.index')
            ->with('success', 'Member updated successfully');
    }
    
    /**
     * Reset a member's password
     */
    public function resetPassword(User $member)
    {
        // Generate a new password based on phone number
        $phoneDigits = substr($member->phone_number, -4);
        $newPassword = 'CashCows2024_' . $phoneDigits;
        
        // Update the password
        $member->update([
            'password' => Hash::make($newPassword),
        ]);
        
        return redirect()->route('admin.members.show', $member)
            ->with('success', "Password reset successfully. New password: {$newPassword}");
    }
    
    /**
     * Remove the specified member from storage
     */
    public function destroy(User $member)
    {
        // Check if there are verified contributions
        $hasVerifiedContributions = Contribution::where('user_id', $member->id)
            ->where('verification_status', 'verified')
            ->exists();
        
        if ($hasVerifiedContributions) {
            return redirect()->route('admin.members.index')
                ->with('error', 'Cannot delete member with verified contributions. Use the "Remove with Refund" option instead.');
        }
        
        // Delete any pending contributions
        Contribution::where('user_id', $member->id)
            ->where('verification_status', 'pending')
            ->delete();
        
        // Delete the member
        $member->delete();
        
        return redirect()->route('admin.members.index')
            ->with('success', 'Member deleted successfully');
    }
    
    /**
     * Show form for removing a member with refund
     */
    public function showRefund(User $member)
    {
        // Calculate total contribution amount
        $totalContribution = Contribution::where('user_id', $member->id)
            ->where('verification_status', 'verified')
            ->sum('amount');
        
        // Calculate refund amount (80% of total contributions)
        $refundAmount = $totalContribution * 0.8;
        
        return view('admin.members.refund', compact('member', 'totalContribution', 'refundAmount'));
    }
    
    /**
     * Process member removal with refund
     */
    public function processRefund(Request $request, User $member)
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0',
            'refund_date' => 'required|date',
            'refund_reference' => 'required|string|max:255',
            'confirmation' => 'required|in:confirm',
        ]);
        
        // Start a database transaction
        DB::beginTransaction();
        
        try {
            // Get the main account
            $mainAccount = GroupAccount::where('name', 'Main Savings')->firstOrFail();
            
            // Check if there's enough balance
            if ($mainAccount->balance < $request->refund_amount) {
                throw new \Exception('Insufficient balance in the main account for this refund.');
            }
            
            // Create a withdrawal transaction
            Transaction::create([
                'group_account_id' => $mainAccount->id,
                'amount' => $request->refund_amount,
                'type' => 'withdrawal',
                'description' => "Refund to {$member->name} (Ref: {$request->refund_reference})",
                'performed_by' => Auth::id(),
            ]);
            
            // Update account balance
            $mainAccount->decrement('balance', $request->refund_amount);
            
            // Create a note in the system about this refund
            Contribution::create([
                'user_id' => $member->id,
                'amount' => -$request->refund_amount, // Negative amount for refund
                'transaction_date' => $request->refund_date,
                'description' => "Refund upon leaving Sacco (Ref: {$request->refund_reference})",
                'verification_status' => 'verified',
                'verified_by' => Auth::id(),
            ]);
            
            // Mark all member contributions as "refunded"
            // This is done by adding a note in the description rather than deleting
            Contribution::where('user_id', $member->id)
                ->where('verification_status', 'verified')
                ->update([
                    'description' => DB::raw("CONCAT(description, ' [REFUNDED]')"),
                ]);
            
            // Delete the member
            $member->delete();
            
            DB::commit();
            
            return redirect()->route('admin.members.index')
                ->with('success', "Member removed successfully with a refund of KES " . number_format($request->refund_amount, 2));
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error processing refund: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Show form for adding a contribution
     */
    public function createContribution(User $member)
    {
        return view('admin.members.contribution', compact('member'));
    }
    
    /**
     * Store a new contribution
     */
    public function storeContribution(Request $request, User $member)
    {
        $request->validate([
            'contribution_type' => 'required|in:monthly,welfare,fine,registration,opc,other',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'verification_status' => 'required|in:pending,verified',
        ]);
        
        // Generate description if not provided
        $description = $request->description;
        if (empty($description)) {
            $month = Carbon::parse($request->transaction_date)->format('F Y');
            
            switch ($request->contribution_type) {
                case 'monthly':
                    $description = "Monthly contribution for {$month}";
                    break;
                case 'welfare':
                    $description = "Welfare fee for {$month}";
                    break;
                case 'fine':
                    $description = "Late payment fine for {$month}";
                    break;
                case 'registration':
                    $description = "Registration fee";
                    break;
                case 'opc':
                    $description = "Olpajeta trip contribution";
                    break;
                default:
                    $description = "Contribution for {$month}";
            }
        }
        
        // Create the contribution
        $contribution = Contribution::create([
            'user_id' => $member->id,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $description,
            'verification_status' => $request->verification_status,
            'verified_by' => $request->verification_status === 'verified' ? Auth::id() : null,
        ]);
        
        // Create transaction if verified
        if ($request->verification_status === 'verified') {
            $mainAccount = GroupAccount::firstOrCreate(
                ['name' => 'Main Savings'],
                [
                    'description' => 'Main group savings account',
                    'balance' => 0,
                ]
            );
            
            Transaction::create([
                'group_account_id' => $mainAccount->id,
                'amount' => $request->amount,
                'type' => 'deposit',
                'description' => "{$description} from {$member->name}",
                'performed_by' => Auth::id(),
            ]);
            
            // Update account balance
            $mainAccount->increment('balance', $request->amount);
        }
        
        return redirect()->route('admin.members.show', $member)
            ->with('success', 'Contribution added successfully');
    }
}