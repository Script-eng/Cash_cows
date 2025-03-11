@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">My Contributions</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contributions as $contribution)
                            <tr>
                                <td>{{ $contribution->transaction_date->format('M d, Y') }}</td>
                                <td>{{ number_format($contribution->amount, 2) }}</td>
                                <td>{{ $contribution->description ?: 'N/A' }}</td>
                                <td>
                                    @if($contribution->verification_status == 'verified')
                                        <span class="badge bg-success">Verified</span>
                                    @elseif($contribution->verification_status == 'pending')
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @else
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">No contributions found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                {{ $contributions->links() }}
            </div>
        </div>
    </div>
</div>
@endsection