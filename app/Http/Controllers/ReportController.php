<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use App\Models\Contribution;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $reports = $user->personalReports()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('reports.index', compact('reports'));
    }


    public function preview(Report $report)
{
    $this->authorize('view', $report);
    
    $filePath = storage_path('app/public/' . $report->file_path);
    
    if (!file_exists($filePath)) {
        \Log::error("File does not exist: " . $filePath);
        return response('PDF not found', 404);
    }
    
    return response()->file($filePath);
}
    
    
    
    /**
     * Show the form for creating a new report.
     */
    public function create()
    {
        return view('reports.create');
    }
    
    /**
     * Store a newly created report in storage.
     */
    public function store(Request $request)
    {
        try {
            // Only validate date fields if custom report type is selected
            $validationRules = [
                'report_type' => 'required|in:monthly,quarterly,annual,custom',
            ];
            
            // Add conditional validation for date fields
            if ($request->report_type === 'custom') {
                $validationRules['start_date'] = 'required|date';
                $validationRules['end_date'] = 'required|date|after_or_equal:start_date';
            }
            
            $request->validate($validationRules);
            
            $user = Auth::user();
            $reportType = $request->report_type;
            
            // Determine date range based on report type
            switch ($reportType) {
                case 'monthly':
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                    break;
                case 'quarterly':
                    $startDate = Carbon::now()->startOfQuarter();
                    $endDate = Carbon::now()->endOfQuarter();
                    break;
                case 'annual':
                    $startDate = Carbon::now()->startOfYear();
                    $endDate = Carbon::now()->endOfYear();
                    break;
                case 'custom':
                    $startDate = Carbon::parse($request->start_date);
                    $endDate = Carbon::parse($request->end_date);
                    break;
                default:
                    throw new \Exception("Invalid report type selected");
            }
            
            Log::info("Report date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
            
            // Get contributions for the date range
            $contributions = $user->contributions()
                ->where('verification_status', 'verified')
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->orderBy('transaction_date')
                ->get();
            
            Log::info("Found {$contributions->count()} contributions for report");
            
            // Create reports directory if it doesn't exist
            Storage::makeDirectory('public/reports');
            
            try {
                // Generate PDF
                $pdf = PDF::loadView('reports.contribution_report', [
                    'user' => $user,
                    'contributions' => $contributions,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'totalAmount' => $contributions->sum('amount'),
                    'generatedAt' => Carbon::now(),
                ]);
                
                // Save PDF file
                $fileName = 'contribution_report_' . $user->id . '_' . time() . '.pdf';
                $filePath = 'reports/' . $fileName;
                
                Log::info("Saving PDF to {$filePath}");
                
                Storage::put('public/' . $filePath, $pdf->output());
                
                // Create report record
                $report = Report::create([
                    'report_type' => $reportType,
                    'user_id' => $user->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'generated_by' => $user->id,
                    'file_path' => $filePath,
                ]);
                
                return redirect()->route('reports.show', $report->id)
                    ->with('success', 'Report generated successfully.');
            } catch (\Exception $e) {
                Log::error("PDF Generation Error: " . $e->getMessage());
                Log::error($e->getTraceAsString());
                return back()->withErrors(['pdf_error' => 'Error generating PDF: ' . $e->getMessage()]);
            }
        } catch (\Exception $e) {
            Log::error("Report Generation Error: " . $e->getMessage());
            Log::error($e->getTraceAsString());
            return back()->withErrors(['error' => 'Error generating report: ' . $e->getMessage()]);
        }
    }
    
    public function show(Report $report)
    {
        try {
            $this->authorize('view', $report);
            
            return view('reports.show', compact('report'));
        } catch (\Exception $e) {
            Log::error("Error viewing report: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error viewing report: ' . $e->getMessage()]);
        }
    }
    
    public function download(Report $report)
    {
        try {
            $this->authorize('view', $report);
            
            $filePath = 'public/' . $report->file_path;
            
            if (!Storage::exists($filePath)) {
                Log::error("Report file not found: {$filePath}");
                return back()->withErrors(['error' => 'Report file not found']);
            }
            
            return Storage::download($filePath);
        } catch (\Exception $e) {
            Log::error("Error downloading report: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error downloading report: ' . $e->getMessage()]);
        }
    }
}