@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Manage Contributions</h5>
            <a href="{{ route('admin.contributions.create') }}" class="btn btn-primary btn-sm">Add New Contribution</a>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-4">
                <li class="nav-item">
                    <a class="nav-link active" href="#pending" data-bs-toggle="tab">Pending Verification</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#verified" data-bs-toggle="tab">Verified Contributions</a>
                </li>
            </ul>
            
            <div class="tab-content">
                <div class="tab-pane fade show active" id="pending">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingContributions as $contribution)
                                    <tr>
                                        <td>{{ $contribution->user->name }}</td>
                                        <td>{{ $contribution->transaction_date->format('M d, Y') }}</td>
                                        <td>{{ number_format($contribution->amount, 2) }}</td>
                                        <td>{{ $contribution->description ?: 'N/A' }}</td>
                                        <td>
                                            <form action="{{ route('admin.contributions.verify', $contribution->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">Verify</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No pending contributions found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="tab-pane fade" id="verified">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Verified By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($verifiedContributions as $contribution)
                                    <tr>
                                        <td>{{ $contribution->user->name }}</td>
                                        <td>{{ $contribution->transaction_date->format('M d, Y') }}</td>
                                        <td>{{ number_format($contribution->amount, 2) }}</td>
                                        <td>{{ $contribution->description ?: 'N/A' }}</td>
                                        <td>{{ $contribution->verifier->name }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No verified contributions found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        
                        <div class="d-flex justify-content-center mt-4">
                            {{ $verifiedContributions->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection