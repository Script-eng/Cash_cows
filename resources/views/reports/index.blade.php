@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">My Reports</h5>
            <a href="{{ route('reports.create') }}" class="btn btn-primary btn-sm">Generate New Report</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Report Type</th>
                            <th>Date Range</th>
                            <th>Generated On</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ ucfirst($report->report_type) }}</td>
                                <td>{{ $report->start_date->format('M d, Y') }} - {{ $report->end_date->format('M d, Y') }}</td>
                                <td>{{ $report->created_at->format('M d, Y g:i A') }}</td>
                                <td>
                                    <a href="{{ route('reports.download', $report->id) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                    <a href="{{ route('reports.show', $report->id) }}" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">No reports found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
</div>
@endsection