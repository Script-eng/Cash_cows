@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-0 text-gray-800">Admin Dashboard</h1>
            <p class="text-muted">Cash Cows Sacco Management System</p>
        </div>
    </div>

    <!-- Overview Cards -->
    <div class="row">
        <!-- Total Members Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Members</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalMembers }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="{{ route('admin.members.index') }}" class="small text-primary">View Details</a>
                </div>
            </div>
        </div>

        <!-- Total Contributions Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Contributions</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">KES {{ number_format($totalContributions, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="{{ route('admin.contributions.index') }}" class="small text-success">View Details</a>
                </div>
            </div>
        </div>

        <!-- Pending Contributions Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pending Verifications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $pendingContributions }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="{{ route('admin.contributions.index', ['status' => 'pending']) }}" class="small text-warning">View Details</a>
                </div>
            </div>
        </div>

        <!-- Fines Collected Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Fines Collected</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">KES {{ number_format($totalFines, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="{{ route('admin.contributions.index', ['type' => 'fine']) }}" class="small text-danger">View Details</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row of Cards -->
    <div class="row">
        <!-- Total Balance Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Account Balance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">KES {{ number_format($totalBalance, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-piggy-bank fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="{{ route('admin.reports.financial') }}" class="small text-info">View Financial Report</a>
                </div>
            </div>
        </div>

        <!-- Welfare Collections Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Welfare Collections</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">KES {{ number_format($totalWelfare, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hands-helping fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="{{ route('admin.contributions.index', ['type' => 'welfare']) }}" class="small text-primary">View Details</a>
                </div>
            </div>
        </div>

        <!-- Member Compliance Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Member Compliance</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        {{ number_format($complianceStats['fullCompliancePercentage'], 1) }}%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-success" role="progressbar"
                                            style="width: {{ $complianceStats['fullCompliancePercentage'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="small mt-1">{{ $complianceStats['fullCompliance'] }} members with excellent compliance</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer py-1 text-center">
                    <a href="{{ route('admin.reports.compliance') }}" class="small text-success">View Compliance Report</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Monthly Contributions Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <!-- Card Header -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Contributions Overview</h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                            aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Export Options:</div>
                            <a class="dropdown-item" href="{{ route('admin.reports.financial', ['report_period' => 'monthly', 'format' => 'csv']) }}">Export CSV</a>
                        </div>
                    </div>
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="monthlyContributionsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Contributions -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <!-- Card Header -->
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Contributions</h6>
                    <a href="{{ route('admin.contributions.index') }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <!-- Card Body -->
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @forelse($recentContributions as $contribution)
                            <div class="list-group-item px-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="font-weight-bold">{{ $contribution->user->name }}</div>
                                        <small class="text-muted">
                                            {{ $contribution->transaction_date->format('M d, Y') }}
                                        </small>
                                    </div>
                                    <div>
                                        <span class="font-weight-bold">
                                            KES {{ number_format($contribution->amount, 2) }}
                                        </span>
                                        @if($contribution->verification_status === 'pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @else
                                            <span class="badge badge-success">Verified</span>
                                        @endif
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1">{{ $contribution->description }}</small>
                            </div>
                        @empty
                            <div class="text-center py-4">No recent contributions found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.members.create') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-user-plus mr-1"></i> Add New Member
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.contributions.batch.create') }}" class="btn btn-success btn-block">
                                <i class="fas fa-money-bill-wave mr-1"></i> Record Monthly Contributions
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.contributions.index', ['status' => 'pending']) }}" class="btn btn-warning btn-block">
                                <i class="fas fa-check-circle mr-1"></i> Verify Pending Contributions
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.reports.financial') }}" class="btn btn-info btn-block">
                                <i class="fas fa-file-invoice-dollar mr-1"></i> Generate Financial Report
                            </a>
                        </div>
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
    // Set new default font family and font color to mimic Bootstrap's default styling
    Chart.defaults.font.family = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.color = '#858796';

    // Monthly Contributions Chart
    var ctx = document.getElementById("monthlyContributionsChart");
    var myLineChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($monthlyData['months']),
            datasets: [
                {
                    label: "Regular Contributions",
                    backgroundColor: "rgba(78, 115, 223, 0.8)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    data: @json($monthlyData['regular']),
                    order: 2
                },
                {
                    label: "Fines",
                    backgroundColor: "rgba(231, 74, 59, 0.8)",
                    borderColor: "rgba(231, 74, 59, 1)",
                    data: @json($monthlyData['fines']),
                    order: 3
                },
                {
                    label: "Welfare",
                    backgroundColor: "rgba(54, 185, 204, 0.8)",
                    borderColor: "rgba(54, 185, 204, 1)",
                    data: @json($monthlyData['welfare']),
                    order: 4
                },
                {
                    label: "Total",
                    type: 'line',
                    backgroundColor: "rgba(0, 0, 0, 0)",
                    borderColor: "#1cc88a",
                    pointBackgroundColor: "#1cc88a",
                    pointBorderColor: "#1cc88a",
                    pointHoverBackgroundColor: "#1cc88a",
                    pointHoverBorderColor: "#1cc88a",
                    data: @json($monthlyData['totals']),
                    borderWidth: 2,
                    tension: 0.3,
                    order: 1
                }
            ],
        },
        options: {
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 25,
                    top: 25,
                    bottom: 0
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxTicksLimit: 12
                    }
                },
                y: {
                    ticks: {
                        maxTicksLimit: 5,
                        callback: function(value, index, values) {
                            return 'KES ' + value.toLocaleString();
                        }
                    },
                    grid: {
                        color: "rgb(234, 236, 244)",
                        zeroLineColor: "rgb(234, 236, 244)",
                        drawBorder: false
                    }
                },
            },
            plugins: {
                tooltip: {
                    titleFontSize: 14,
                    bodyFontSize: 13,
                    callbacks: {
                        label: function(context) {
                            var label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += 'KES ' + context.parsed.y.toLocaleString();
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });
</script>
@endsection