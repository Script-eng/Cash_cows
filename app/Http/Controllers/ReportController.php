<?php

namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Report;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    
    public function generate(Request $request)
    {
        $request->validate([
            'report_type' => 'required|in:monthly,quarterly,annual,custom',
            'start_date' => 'required_if:report_type,custom|date',
            'end_date' => 'required_if:report_type,custom|date|after_or_equal:start_date',
        ]);
        
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
        }
        
        // Get contributions for the date range
        $contributions = $user->contributions()
            ->where('verification_status', 'verified')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();
        
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
    }
    
    public function show(Report $report)
    {
        $this->authorize('view', $report);
        
        return view('reports.show', compact('report'));
    }
    
    public function download(Report $report)
    {
        $this->authorize('view', $report);
        
        return Storage::download('public/' . $report->file_path);
    }
}