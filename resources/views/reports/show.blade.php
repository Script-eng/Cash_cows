@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Report Details</h5>
            <div>
                <a href="{{ route('reports.download', $report->id) }}" class="btn btn-primary btn-sm">Download PDF</a>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary btn-sm">Back to Reports</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>Report Type:</strong> {{ ucfirst($report->report_type) }}</p>
                    <p><strong>Date Range:</strong> {{ $report->start_date->format('M d, Y') }} - {{ $report->end_date->format('M d, Y') }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Generated On:</strong> {{ $report->created_at->format('M d, Y g:i A') }}</p>
                    <p><strong>Generated By:</strong> {{ $report->generator->name }}</p>
                </div>
            </div>
            
            <div class="embed-responsive">
                <iframe src="{{ route('reports.download', $report->id) }}" class="embed-responsive-item w-100" style="height: 600px;"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection