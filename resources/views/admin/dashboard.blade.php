@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title">Admin Dashboard</h2>
                    <p class="text-muted">Sacco overview and management</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Users</h5>
                                    <h2 class="display-4">{{ $totalUsers }}</h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Contributions</h5>
                                    <h2 class="display-4">{{ number_format($totalContributions, 2) }}</h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Verifications</h5>
                                    <h2 class="display-4">{{ $pendingContributions->count() }}</h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Group Accounts</h5>
                                    <h2 class="display-4">{{ $groupAccounts->count() }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    Monthly Contribution Totals
                </div>
                <div class="card-body">
                    <canvas id="monthlyContributionsChart" height="300"></canvas>
                </div>
            </div>
            
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    Recent Contributions
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentContributions as $contribution)
                                <tr>
                                    <td>{{ $contribution->user->name }}</td>
                                    <td>{{ number_format($contribution->amount, 2) }}</td>
                                    <td>{{ $contribution->transaction_date->format('M d, Y') }}</td>
                                    <td><span class="badge bg-success">Verified</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    Pending Verifications
                </div>
                <div class="card-body">
                    @if($pendingContributions->isEmpty())
                        <p class="text-center text-muted">No pending contributions</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($pendingContributions as $contribution)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $contribution->user->name }}</strong><br>
                                            <span>{{ number_format($contribution->amount, 2) }}</span><br>
                                            <small class="text-muted">{{ $contribution->transaction_date->format('M d, Y') }}</small>
                                        </div>
                                        <form action="{{ route('admin.contributions.verify', $contribution->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Verify</button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    Quick Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.contributions.create') }}" class="btn btn-primary">Add Contribution</a>
                        <a href="{{ route('admin.contributions.index') }}" class="btn btn-outline-primary">Manage Contributions</a>
                        <a href="{{ route('admin.reports.group') }}" class="btn btn-outline-info">Group Reports</a>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    Group Accounts
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach($groupAccounts as $account)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $account->name }}</strong><br>
                                        <small class="text-muted">{{ $account->description }}</small>
                                    </div>
                                    <span class="badge bg-primary">{{ number_format($account->balance, 2) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('monthlyContributionsChart').getContext('2d');
        var contributionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($monthlyData['months']),
                datasets: [{
                    label: 'Monthly Contributions Total',
                    data: @json($monthlyData['amounts']),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly Contribution Totals'
                    }
                }
            }
        });
    });
</script>
@endsection