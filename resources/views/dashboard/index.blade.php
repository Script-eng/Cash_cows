@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title">Welcome, {{ Auth::user()->name }}</h2>
                    <p class="text-muted">Your savings at a glance weweeeee</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Contributions</h5>
                                    <h2 class="display-4">{{ number_format($totalContribution, 2) }}</h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">This Month</h5>
                                    <h2 class="display-4">{{ number_format($monthlyData['amounts'][11], 2) }}</h2>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Contributions Count</h5>
                                    <h2 class="display-4">{{ Auth::user()->contributions()->verified()->count() }}</h2>
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
                    Contribution History
                </div>
                <div class="card-body">
                    <canvas id="contributionChart" height="300"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    Recent Contributions
                </div>
                <div class="card-body">
                    @if($contributions->isEmpty())
                        <p class="text-center text-muted">No contributions yet.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($contributions as $contribution)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ number_format($contribution->amount, 2) }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $contribution->transaction_date->format('M d, Y') }}</small>
                                        </div>
                                        <span class="badge bg-success">Verified</span>
                                    </div>
                                    @if($contribution->description)
                                        <small class="text-muted">{{ $contribution->description }}</small>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    
                    <div class="mt-3">
                        <a href="{{ route('contributions.index') }}" class="btn btn-primary btn-sm">View All</a>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mt-4">
                <div class="card-header">
                    Quick Actions
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('reports.index') }}" class="btn btn-outline-primary">View Reports</a>
                        <a href="{{ route('reports.create') }}" class="btn btn-outline-success">Generate Report</a>
                    </div>
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
        var ctx = document.getElementById('contributionChart').getContext('2d');
        var contributionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($monthlyData['months']),
                datasets: [{
                    label: 'Monthly Contributions',
                    data: @json($monthlyData['amounts']),
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                    tension: 0.4
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
                        text: 'Your Contribution History'
                    }
                }
            }
        });
    });
</script>
@endsection