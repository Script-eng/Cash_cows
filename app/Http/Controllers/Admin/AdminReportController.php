<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\GroupAccount;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class AdminReportController extends Controller
{
    /**
     * Display the report dashboard
     */
    public function index()
    {
        // Get some basic stats for the dashboard
        $totalContributions = Contribution::verified()->sum('amount');
        $totalMembers = User::where('role', 'user')->count();
        $totalAccounts = GroupAccount::count();
        
        // Get member list for filters
        $members = User::where('role', 'user')->orderBy('name')->get();
        
        return view('admin.reports.index', compact(
            'totalContributions',
            'totalMembers',
            'totalAccounts',
            'members'
        ));
    }
    
    /**
     * Generate a comprehensive financial report
     */
    public function generateFinancialReport(Request $request)
    {
        $request->validate([
            'report_period' => 'required|in:monthly,quarterly,annual,custom',
            'start_date' => 'required_if:report_period,custom|nullable|date',
            'end_date' => 'required_if:report_period,custom|nullable|date|after_or_equal:start_date',
            'format' => 'required|in:html,pdf,csv',
        ]);
        
        // Determine date range based on report period
        $startDate = null;
        $endDate = Carbon::now();
        
        switch ($request->report_period) {
            case 'monthly':
                $startDate = Carbon::now()->startOfMonth();
                $periodName = 'Monthly Report - ' . $startDate->format('F Y');
                break;
            case 'quarterly':
                $startDate = Carbon::now()->startOfQuarter();
                $periodName = 'Quarterly Report - Q' . ceil($startDate->month / 3) . ' ' . $startDate->year;
                break;
            case 'annual':
                $startDate = Carbon::now()->startOfYear();
                $periodName = 'Annual Report - ' . $startDate->year;
                break;
            case 'custom':
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                $periodName = 'Custom Report - ' . $startDate->format('M d, Y') . ' to ' . $endDate->format('M d, Y');
                break;
        }
        
        // Collect all financial data for the period
        $data = $this->collectFinancialData($startDate, $endDate);
        
        // Generate the appropriate format
        if ($request->format === 'html') {
            return view('admin.reports.financial', compact('data', 'periodName', 'startDate', 'endDate'));
        } elseif ($request->format === 'pdf') {
            // PDF generation would be implemented here using a library like DomPDF
            return redirect()->back()->with('info', 'PDF generation not implemented yet.');
        } elseif ($request->format === 'csv') {
            return $this->generateCsvReport($data, $periodName);
        }
    }
    
    /**
     * Collect all financial data for the specified period
     */
    private function collectFinancialData($startDate, $endDate)
    {
        // Get all contributions for the period
        $contributions = Contribution::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('verification_status', 'verified')
            ->with('user')
            ->get();
        
        // Get all transactions for the period
        $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->with('performer')
            ->get();
        
        // Group contributions by type
        $regularContributions = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'Monthly contribution');
        })->sum('amount');
        
        $fines = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'fine');
        })->sum('amount');
        
        $welfare = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'Welfare');
        })->sum('amount');
        
        $registration = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'Registration');
        })->sum('amount');
        
        $opc = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'Olpajeta');
        })->sum('amount');
        
        $otherContributions = $contributions->sum('amount') - $regularContributions - $fines - $welfare - $registration - $opc;
        
        // Group contributions by month
        $contributionsByMonth = $contributions->groupBy(function($contribution) {
            return Carbon::parse($contribution->transaction_date)->format('Y-m');
        })->map(function($group) {
            return [
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ];
        });
        
        // Group contributions by member
        $contributionsByMember = $contributions->groupBy('user_id')->map(function($group) {
            return [
                'member' => $group->first()->user,
                'total' => $group->sum('amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('total')->values();
        
        // Group transactions by type
        $deposits = $transactions->where('type', 'deposit')->sum('amount');
        $withdrawals = $transactions->where('type', 'withdrawal')->sum('amount');
        $transfers = $transactions->where('type', 'transfer')->sum('amount');
        
        // Get account balances
        $accounts = GroupAccount::all()->map(function($account) {
            return [
                'name' => $account->name,
                'description' => $account->description,
                'balance' => $account->balance,
            ];
        });
        
        // Calculate total income, expenses, and net change
        $totalIncome = $deposits;
        $totalExpenses = $withdrawals;
        $netChange = $totalIncome - $totalExpenses;
        
        // Compile all data
        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'overview' => [
                'total_contributions' => $contributions->sum('amount'),
                'contribution_count' => $contributions->count(),
                'regular_contributions' => $regularContributions,
                'fines' => $fines,
                'welfare' => $welfare,
                'registration' => $registration,
                'opc' => $opc,
                'other' => $otherContributions,
            ],
            'transactions' => [
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'transfers' => $transfers,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_change' => $netChange,
            ],
            'accounts' => $accounts,
            'by_month' => $contributionsByMonth,
            'by_member' => $contributionsByMember,
            'raw_data' => [
                'contributions' => $contributions,
                'transactions' => $transactions,
            ],
        ];
    }
    
    /**
     * Generate CSV report from financial data
     */
    private function generateCsvReport($data, $periodName)
    {
        $csv = [];
        
        // Add report header
        $csv[] = [$periodName];
        $csv[] = ['Period:', $data['period']['start_date']->format('M d, Y'), 'to', $data['period']['end_date']->format('M d, Y')];
        $csv[] = []; // Empty row
        
        // Add overview section
        $csv[] = ['CONTRIBUTION SUMMARY'];
        $csv[] = ['Total Contributions:', 'KES ' . number_format($data['overview']['total_contributions'], 2)];
        $csv[] = ['Regular Contributions:', 'KES ' . number_format($data['overview']['regular_contributions'], 2)];
        $csv[] = ['Fines:', 'KES ' . number_format($data['overview']['fines'], 2)];
        $csv[] = ['Welfare:', 'KES ' . number_format($data['overview']['welfare'], 2)];
        $csv[] = ['Registration:', 'KES ' . number_format($data['overview']['registration'], 2)];
        $csv[] = ['OPC:', 'KES ' . number_format($data['overview']['opc'], 2)];
        $csv[] = ['Other:', 'KES ' . number_format($data['overview']['other'], 2)];
        $csv[] = []; // Empty row
        
        // Add transaction summary
        $csv[] = ['TRANSACTION SUMMARY'];
        $csv[] = ['Total Income:', 'KES ' . number_format($data['transactions']['total_income'], 2)];
        $csv[] = ['Total Expenses:', 'KES ' . number_format($data['transactions']['total_expenses'], 2)];
        $csv[] = ['Net Change:', 'KES ' . number_format($data['transactions']['net_change'], 2)];
        $csv[] = []; // Empty row
        
        // Add account balances
        $csv[] = ['ACCOUNT BALANCES'];
        foreach ($data['accounts'] as $account) {
            $csv[] = [$account['name'], 'KES ' . number_format($account['balance'], 2)];
        }
        $csv[] = []; // Empty row
        
        // Add member contributions
        $csv[] = ['CONTRIBUTIONS BY MEMBER'];
        $csv[] = ['Member', 'Total Amount', 'Number of Contributions'];
        foreach ($data['by_member'] as $memberData) {
            $csv[] = [
                $memberData['member']->name,
                'KES ' . number_format($memberData['total'], 2),
                $memberData['count'],
            ];
        }
        $csv[] = []; // Empty row
        
        // Add monthly contributions
        $csv[] = ['CONTRIBUTIONS BY MONTH'];
        $csv[] = ['Month', 'Total Amount', 'Number of Contributions'];
        foreach ($data['by_month'] as $month => $monthData) {
            $csv[] = [
                Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                'KES ' . number_format($monthData['total'], 2),
                $monthData['count'],
            ];
        }
        
        // Generate CSV content
        $content = '';
        foreach ($csv as $row) {
            $content .= implode(',', $row) . "\n";
        }
        
        // Generate response with CSV content
        $filename = str_replace(' ', '_', $periodName) . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return Response::make($content, 200, $headers);
    }
    
    /**
     * Generate member compliance report
     */
    public function generateComplianceReport(Request $request)
    {
        $request->validate([
            'compliance_threshold' => 'nullable|numeric|min:0|max:100',
            'format' => 'required|in:html,pdf,csv',
        ]);
        
        $threshold = $request->input('compliance_threshold', 75); // Default 75%
        
        // Get all members
        $members = User::where('role', 'user')->get();
        
        // Calculate compliance for each member
        $membersWithCompliance = [];
        
        foreach ($members as $member) {
            // Get first contribution date
            $firstContribution = Contribution::where('user_id', $member->id)
                ->where('verification_status', 'verified')
                ->where('description', 'like', '%Monthly contribution%')
                ->orderBy('transaction_date', 'asc')
                ->first();
            
            if (!$firstContribution) {
                // No contributions yet
                $membersWithCompliance[] = [
                    'member' => $member,
                    'first_contribution' => null,
                    'last_contribution' => null,
                    'expected_months' => 0,
                    'actual_months' => 0,
                    'compliance_rate' => 0,
                    'compliance_status' => 'No Data',
                    'total_amount' => 0,
                ];
                continue;
            }
            
            $startDate = Carbon::parse($firstContribution->transaction_date)->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            
            // Calculate expected number of months
            $expectedMonths = $startDate->diffInMonths($endDate) + 1;
            
            // Get all monthly contributions
            $contributions = Contribution::where('user_id', $member->id)
                ->where('verification_status', 'verified')
                ->where('description', 'like', '%Monthly contribution%')
                ->get();
            
            // Get actual number of monthly contributions
            $actualMonths = $contributions->groupBy(function ($contribution) {
                return Carbon::parse($contribution->transaction_date)->format('Y-m');
            })->count();
            
            // Get last contribution
            $lastContribution = Contribution::where('user_id', $member->id)
                ->where('verification_status', 'verified')
                ->where('description', 'like', '%Monthly contribution%')
                ->orderBy('transaction_date', 'desc')
                ->first();
            
            // Calculate compliance rate
            $complianceRate = $expectedMonths > 0 ? ($actualMonths / $expectedMonths) * 100 : 0;
            
            // Determine compliance status
            $complianceStatus = '';
            if ($complianceRate >= 90) {
                $complianceStatus = 'Excellent';
            } elseif ($complianceRate >= 75) {
                $complianceStatus = 'Good';
            } elseif ($complianceRate >= 50) {
                $complianceStatus = 'Fair';
            } else {
                $complianceStatus = 'Poor';
            }
            
            // Calculate total contribution amount
            $totalAmount = $contributions->sum('amount');
            
            $membersWithCompliance[] = [
                'member' => $member,
                'first_contribution' => $firstContribution,
                'last_contribution' => $lastContribution,
                'expected_months' => $expectedMonths,
                'actual_months' => $actualMonths,
                'compliance_rate' => $complianceRate,
                'compliance_status' => $complianceStatus,
                'total_amount' => $totalAmount,
            ];
        }
        
        // Sort by compliance rate (descending)
        usort($membersWithCompliance, function($a, $b) {
            return $b['compliance_rate'] <=> $a['compliance_rate'];
        });
        
        // Filter by threshold if requested
        $filteredMembers = collect($membersWithCompliance)->filter(function($item) use ($threshold) {
            return $item['compliance_rate'] < $threshold;
        })->values()->all();
        
        // Prepare data for the report
        $reportData = [
            'all_members' => $membersWithCompliance,
            'filtered_members' => $filteredMembers,
            'threshold' => $threshold,
            'generated_at' => Carbon::now(),
        ];
        
        // Generate the appropriate format
        if ($request->format === 'html') {
            return view('admin.reports.compliance', compact('reportData'));
        } elseif ($request->format === 'pdf') {
            // PDF generation would be implemented here
            return redirect()->back()->with('info', 'PDF generation not implemented yet.');
        } elseif ($request->format === 'csv') {
            return $this->generateCsvComplianceReport($reportData);
        }
    }
    
    /**
     * Generate CSV for compliance report
     */
    private function generateCsvComplianceReport($reportData)
    {
        $csv = [];
        
        // Add report header
        $csv[] = ['Member Compliance Report'];
        $csv[] = ['Generated on:', $reportData['generated_at']->format('M d, Y H:i:s')];
        $csv[] = ['Compliance threshold:', $reportData['threshold'] . '%'];
        $csv[] = []; // Empty row
        
        // Add compliance data headers
        $csv[] = ['Member', 'First Contribution', 'Last Contribution', 'Expected Months', 'Actual Months', 'Compliance Rate', 'Status', 'Total Amount'];
        
        // Add member data
        foreach ($reportData['all_members'] as $data) {
            $csv[] = [
                $data['member']->name,
                $data['first_contribution'] ? Carbon::parse($data['first_contribution']->transaction_date)->format('M Y') : 'N/A',
                $data['last_contribution'] ? Carbon::parse($data['last_contribution']->transaction_date)->format('M Y') : 'N/A',
                $data['expected_months'],
                $data['actual_months'],
                round($data['compliance_rate'], 2) . '%',
                $data['compliance_status'],
                'KES ' . number_format($data['total_amount'], 2),
            ];
        }
        
        // Generate CSV content
        $content = '';
        foreach ($csv as $row) {
            $content .= implode(',', $row) . "\n";
        }
        
        // Generate response with CSV content
        $filename = 'Member_Compliance_Report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        return Response::make($content, 200, $headers);
    }
    
    /**
     * Generate individual member statement
     */
    public function generateMemberStatement(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:html,pdf',
        ]);
        
        $member = User::findOrFail($request->member_id);
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        
        // Get all contributions for the period
        $contributions = Contribution::where('user_id', $member->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();
        
        // Group by type
        $regularContributions = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'Monthly contribution');
        });
        
        $fines = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'fine');
        });
        
        $welfare = $contributions->filter(function($contribution) {
            return str_contains($contribution->description, 'Welfare');
        });
        
        $other = $contributions->filter(function($contribution) {
            return !str_contains($contribution->description, 'Monthly contribution') &&
                   !str_contains($contribution->description, 'fine') &&
                   !str_contains($contribution->description, 'Welfare');
        });
        
        // Calculate totals
        $totalRegular = $regularContributions->sum('amount');
        $totalFines = $fines->sum('amount');
        $totalWelfare = $welfare->sum('amount');
        $totalOther = $other->sum('amount');
        $grandTotal = $contributions->sum('amount');
        
        // Calculate compliance rate for the period
        $monthsInPeriod = $startDate->diffInMonths($endDate) + 1;
        $monthsContributed = $regularContributions->groupBy(function($contribution) {
            return Carbon::parse($contribution->transaction_date)->format('Y-m');
        })->count();
        
        $complianceRate = $monthsInPeriod > 0 ? ($monthsContributed / $monthsInPeriod) * 100 : 0;
        
        // Determine compliance status
        $complianceStatus = '';
        if ($complianceRate >= 90) {
            $complianceStatus = 'Excellent';
        } elseif ($complianceRate >= 75) {
            $complianceStatus = 'Good';
        } elseif ($complianceRate >= 50) {
            $complianceStatus = 'Fair';
        } else {
            $complianceStatus = 'Poor';
        }
        
        // Prepare statement data
        $statementData = [
            'member' => $member,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'generated_at' => Carbon::now(),
            'contributions' => [
                'all' => $contributions,
                'regular' => $regularContributions,
                'fines' => $fines,
                'welfare' => $welfare,
                'other' => $other,
            ],
            'totals' => [
                'regular' => $totalRegular,
                'fines' => $totalFines,
                'welfare' => $totalWelfare,
                'other' => $totalOther,
                'grand_total' => $grandTotal,
            ],
            'compliance' => [
                'months_in_period' => $monthsInPeriod,
                'months_contributed' => $monthsContributed,
                'rate' => $complianceRate,
                'status' => $complianceStatus,
            ],
        ];
        
        // Generate the appropriate format
        if ($request->format === 'html') {
            return view('admin.reports.member_statement', compact('statementData'));
        } elseif ($request->format === 'pdf') {
            // PDF generation would be implemented here
            return redirect()->back()->with('info', 'PDF generation not implemented yet.');
        }
    }
}