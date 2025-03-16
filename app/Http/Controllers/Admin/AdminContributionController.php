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

class AdminContributionController extends Controller
{
    /**
     * Display a listing of all contributions
     */
    public function index(Request $request)
    {
        // Base query
        $query = Contribution::with('user');
        
        // Filter by verification status
        if ($request->has('status') && in_array($request->status, ['pending', 'verified'])) {
            $query->where('verification_status', $request->status);
        }
        
        // Filter by contribution type
        if ($request->has('type')) {
            switch ($request->type) {
                case 'monthly':
                    $query->where('description', 'like', '%Monthly contribution%');
                    break;
                case 'welfare':
                    $query->where('description', 'like', '%Welfare%');
                    break;
                case 'fine':
                    $query->where('description', 'like', '%fine%');
                    break;
                case 'registration':
                    $query->where('description', 'like', '%Registration%');
                    break;
                case 'opc':
                    $query->where('description', 'like', '%Olpajeta%');
                    break;
            }
        }
        
        // Filter by member
        if ($request->has('member_id') && $request->member_id) {
            $query->where('user_id', $request->member_id);
        }
        
        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->where('transaction_date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date') && $request->end_date) {
            $query->where('transaction_date', '<=', $request->end_date);
        }
        
        // Sort the results
        $sortField = $request->sort_by ?? 'transaction_date';
        $sortDirection = $request->sort_direction ?? 'desc';
        
        if (in_array($sortField, ['transaction_date', 'amount', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }
        
        // Get paginated results
        $contributions = $query->paginate(20)
            ->appends($request->except('page'));
        
        // Get all members for the filter dropdown
        $members = User::where('role', 'user')
            ->orderBy('name')
            ->get();
        
        // Calculate totals for the filtered contributions
        $totalAmount = $contributions->sum('amount');
        $pendingCount = $contributions->where('verification_status', 'pending')->count();
        $verifiedCount = $contributions->where('verification_status', 'verified')->count();
        
        return view('admin.contributions.index', compact(
            'contributions',
            'members',
            'totalAmount',
            'pendingCount',
            'verifiedCount'
        ));
    }
    
    /**
     * Show the form for creating a new contribution
     */
    public function create()
    {
        $members = User::where('role', 'user')
            ->orderBy('name')
            ->get();
            
        return view('admin.contributions.create', compact('members'));
    }
    
    /**
     * Store a newly created contribution
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'contribution_type' => 'required|in:monthly,welfare,fine,registration,opc,other',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'verification_status' => 'required|in:pending,verified',
        ]);
        
        // Find the member
        $member = User::findOrFail($request->user_id);
        
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
            'user_id' => $request->user_id,
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
        
        return redirect()->route('admin.contributions.index')
            ->with('success', 'Contribution added successfully');
    }
    
    /**
     * Display the specified contribution
     */
    public function show(Contribution $contribution)
    {
        return view('admin.contributions.show', compact('contribution'));
    }
    
    /**
     * Show the form for editing the specified contribution
     */
    public function edit(Contribution $contribution)
    {
        // Only allow editing of pending contributions
        if ($contribution->verification_status === 'verified') {
            return redirect()->route('admin.contributions.show', $contribution)
                ->with('error', 'Verified contributions cannot be edited. You must delete and recreate them.');
        }
        
        $members = User::where('role', 'user')
            ->orderBy('name')
            ->get();
            
        return view('admin.contributions.edit', compact('contribution', 'members'));
    }
    
    /**
     * Update the specified contribution in storage
     */
    public function update(Request $request, Contribution $contribution)
    {
        // Only allow updating of pending contributions
        if ($contribution->verification_status === 'verified') {
            return redirect()->route('admin.contributions.show', $contribution)
                ->with('error', 'Verified contributions cannot be edited. You must delete and recreate them.');
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'contribution_type' => 'required|in:monthly,welfare,fine,registration,opc,other',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'verification_status' => 'required|in:pending,verified',
        ]);
        
        // Find the member
        $member = User::findOrFail($request->user_id);
        
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
        
        // Update the contribution
        $contribution->update([
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description' => $description,
            'verification_status' => $request->verification_status,
            'verified_by' => $request->verification_status === 'verified' ? Auth::id() : null,
        ]);
        
        // If now verified, create transaction
        if ($contribution->verification_status === 'verified') {
            $mainAccount = GroupAccount::firstOrCreate(
                ['name' => 'Main Savings'],
                [
                    'description' => 'Main group savings account',
                    'balance' => 0,
                ]
            );
            
            Transaction::create([
                'group_account_id' => $mainAccount->id,
                'amount' => $contribution->amount,
                'type' => 'deposit',
                'description' => "{$contribution->description} from {$member->name}",
                'performed_by' => Auth::id(),
            ]);
            
            // Update account balance
            $mainAccount->increment('balance', $contribution->amount);
        }
        
        return redirect()->route('admin.contributions.show', $contribution)
            ->with('success', 'Contribution updated successfully');
    }
    
    /**
     * Remove the specified contribution from storage
     */
    public function destroy(Contribution $contribution)
    {
        // If verified, we need to update the account balance and create a reversal transaction
        if ($contribution->verification_status === 'verified') {
            // Start transaction
            DB::beginTransaction();
            
            try {
                $mainAccount = GroupAccount::where('name', 'Main Savings')->first();
                
                if ($mainAccount) {
                    // Create reversal transaction
                    Transaction::create([
                        'group_account_id' => $mainAccount->id,
                        'amount' => $contribution->amount,
                        'type' => 'withdrawal',
                        'description' => "REVERSAL: {$contribution->description} (ID: {$contribution->id})",
                        'performed_by' => Auth::id(),
                    ]);
                    
                    // Update account balance
                    $mainAccount->decrement('balance', $contribution->amount);
                }
                
                // Delete the contribution
                $contribution->delete();
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                
                return redirect()->route('admin.contributions.index')
                    ->with('error', 'Error deleting contribution: ' . $e->getMessage());
            }
        } else {
            // For pending contributions, just delete
            $contribution->delete();
        }
        
        return redirect()->route('admin.contributions.index')
            ->with('success', 'Contribution deleted successfully');
    }
    
    /**
     * Verify a pending contribution
     */
    public function verify(Contribution $contribution)
    {
        // Only verify pending contributions
        if ($contribution->verification_status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Only pending contributions can be verified.');
        }
        
        // Start transaction
        DB::beginTransaction();
        
        try {
            // Get the member
            $member = User::findOrFail($contribution->user_id);
            
            // Update contribution status
            $contribution->update([
                'verification_status' => 'verified',
                'verified_by' => Auth::id(),
            ]);
            
            // Create transaction
            $mainAccount = GroupAccount::firstOrCreate(
                ['name' => 'Main Savings'],
                [
                    'description' => 'Main group savings account',
                    'balance' => 0,
                ]
            );
            
            Transaction::create([
                'group_account_id' => $mainAccount->id,
                'amount' => $contribution->amount,
                'type' => 'deposit',
                'description' => "{$contribution->description} from {$member->name}",
                'performed_by' => Auth::id(),
            ]);
            
            // Update account balance
            $mainAccount->increment('balance', $contribution->amount);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error verifying contribution: ' . $e->getMessage());
        }
        
        return redirect()->back()
            ->with('success', 'Contribution verified successfully');
    }
    
    /**
     * Verify multiple contributions at once
     */
    public function batchVerify(Request $request)
    {
        $request->validate([
            'contribution_ids' => 'required|array',
            'contribution_ids.*' => 'exists:contributions,id',
        ]);
        
        $count = 0;
        $totalAmount = 0;
        
        // Start transaction
        DB::beginTransaction();
        
        try {
            $mainAccount = GroupAccount::firstOrCreate(
                ['name' => 'Main Savings'],
                [
                    'description' => 'Main group savings account',
                    'balance' => 0,
                ]
            );
            
            foreach ($request->contribution_ids as $id) {
                $contribution = Contribution::where('id', $id)
                    ->where('verification_status', 'pending')
                    ->first();
                
                if (!$contribution) {
                    continue;
                }
                
                // Get the member
                $member = User::find($contribution->user_id);
                
                if (!$member) {
                    continue;
                }
                
                // Update contribution status
                $contribution->update([
                    'verification_status' => 'verified',
                    'verified_by' => Auth::id(),
                ]);
                
                // Create transaction
                Transaction::create([
                    'group_account_id' => $mainAccount->id,
                    'amount' => $contribution->amount,
                    'type' => 'deposit',
                    'description' => "{$contribution->description} from {$member->name}",
                    'performed_by' => Auth::id(),
                ]);
                
                $totalAmount += $contribution->amount;
                $count++;
            }
            
            // Update account balance
            if ($totalAmount > 0) {
                $mainAccount->increment('balance', $totalAmount);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error verifying contributions: ' . $e->getMessage());
        }
        
        return redirect()->back()
            ->with('success', "Verified {$count} contributions successfully for a total of KES " . number_format($totalAmount, 2));
    }
    
    /**
     * Show form for batch contribution entry
     */
    public function batchCreate()
    {
        $members = User::where('role', 'user')
            ->orderBy('name')
            ->get();
            
        return view('admin.contributions.batch', compact('members'));
    }
    
    /**
     * Process batch contribution entry
     */
    public function batchStore(Request $request)
    {
        $request->validate([
            'contribution_type' => 'required|in:monthly,welfare,fine,other',
            'month' => 'required|date_format:Y-m',
            'transaction_date' => 'required|date',
            'verification_status' => 'required|in:pending,verified',
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:users,id',
            'amounts' => 'required|array',
            'amounts.*' => 'nullable|numeric|min:0',
        ]);
        
        // Start transaction
        DB::beginTransaction();
        
        try {
            $count = 0;
            $totalAmount = 0;
            
            // Get main account if verifying contributions
            $mainAccount = null;
            if ($request->verification_status === 'verified') {
                $mainAccount = GroupAccount::firstOrCreate(
                    ['name' => 'Main Savings'],
                    [
                        'description' => 'Main group savings account',
                        'balance' => 0,
                    ]
                );
            }
            
            // Process each contribution
            foreach ($request->member_ids as $index => $memberId) {
                // Skip if no amount provided
                if (!isset($request->amounts[$index]) || $request->amounts[$index] <= 0) {
                    continue;
                }
                
                $amount = $request->amounts[$index];
                
                // Get the member
                $member = User::find($memberId);
                
                if (!$member) {
                    continue;
                }
                
                // Generate description
                $month = Carbon::createFromFormat('Y-m', $request->month)->format('F Y');
                
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
                    default:
                        $description = "Contribution for {$month}";
                }
                
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
                
                $count++;
            }
            
            // Update account balance if verified
            if ($request->verification_status === 'verified' && $mainAccount && $totalAmount > 0) {
                $mainAccount->increment('balance', $totalAmount);
            }
            
            DB::commit();
            
            return redirect()->route('admin.contributions.index')
                ->with('success', "Successfully added {$count} contributions for {$request->month}");
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Error adding contributions: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Show form for importing contributions from Excel/CSV
     */
    public function importForm()
    {
        return view('admin.contributions.import');
    }
    
    /**
     * Process the import of contributions from Excel/CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'contribution_type' => 'required|in:monthly,welfare,fine,other',
            'verification_status' => 'required|in:pending,verified',
        ]);
        
        // Import logic would go here
        // This would involve:
        // 1. Reading the uploaded file (using Laravel Excel or another library)
        // 2. Validating the data
        // 3. Creating contributions for each row
        // 4. Creating transactions if verified
        // 5. Updating account balance if verified
        
        // For now, we'll return a message indicating this is not implemented
        return redirect()->route('admin.contributions.index')
            ->with('info', 'File import functionality is not yet implemented. Please use the batch entry form instead.');
    }
}