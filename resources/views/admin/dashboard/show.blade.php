@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Member Details: {{ $member->name }}</h1>
        <h1 class="h3 mb-0 text-gray-800">Member Details: {{ $member->name }}</h1>
        <div>
            <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-edit fa-sm text-white-50"></i> Edit Member
            </a>
            <a href="{{ route('admin.members.contribution.create', $member) }}" class="btn btn-success btn-sm shadow-sm">
                <i class="fas fa-money-bill fa-sm text-white-50"></i> Add Contribution
            </a>
            <a href="{{ route('admin.members.reset-password', $member) }}" class="btn btn-warning btn-sm shadow-sm"
               onclick="return confirm('Are you sure you want to reset the password for this member?');">
                <i class="fas fa-key fa-sm text-white-50"></i> Reset Password
            </a>
            <a href="{{ route('admin.members.index') }}" class="btn btn-secondary btn-sm shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Members
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Member Information Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Member Information</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($member->name) }}&size=128&background=4e73df&color=ffffff" 
                             class="img-profile rounded-circle img-fluid" style="width: 128px; height: 128px;">
                        <h4 class="mt-3">{{ $member->name }}</h4>
                        <span class="badge bg-primary">Member ID: {{ $member->id }}</span>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>Email:</strong>
                            <span>{{ $member->email }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>Phone:</strong>
                            <span>{{ $member->phone_number }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <strong>Join Date:</strong>
                            <span>{{ $member->created_at->format('M d, Y') }}</span>
                        </li>
                    </ul>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-primary btn-sm btn-block">
                        <i class="fas fa-edit fa-sm"></i> Edit Member
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contribution Summary Card -->
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Contribution Summary</h6>
                </div>
                <div class="card-body">
                    <!-- Total Contribution -->
                    <div class="text-center mb-4">
                        <h2 class="font-weight-bold text-primary">KES {{ number_format($totalContribution, 2) }}</h2>
                        <p class="text-muted">Total Contributions</p>
                    </div>
                    
                    <!-- Contribution Breakdown -->
                    <div class="mb-4">
                        <h6 class="font-weight-bold">Contribution Breakdown</h6>
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar bg-primary" style="width: {{ $contributionBreakdown['regular'] / max(1, $totalContribution) * 100 }}%">
                                {{ number_format($contributionBreakdown['regular'] / max(1, $totalContribution) * 100, 0) }}%
                            </div>
                            <div class="progress-bar bg-danger" style="width: {{ $contributionBreakdown['fines'] / max(1, $totalContribution) * 100 }}%">
                                {{ number_format($contributionBreakdown['fines'] / max(1, $totalContribution) * 100, 0) }}%
                            </div>
                            <div class="progress-bar bg-info" style="width: {{ $contributionBreakdown['welfare'] / max(1, $totalContribution) * 100 }}%">
                                {{ number_format($contributionBreakdown['welfare'] / max(1, $totalContribution) * 100, 0) }}%
                            </div>
                            <div class="progress-bar bg-warning" style="width: {{ ($contributionBreakdown['registration'] + $contributionBreakdown['opc'] + $contributionBreakdown['other']) / max(1, $totalContribution) * 100 }}%">
                                {{ number_format(($contributionBreakdown['registration'] + $contributionBreakdown['opc'] + $contributionBreakdown['other']) / max(1, $totalContribution) * 100, 0) }}%
                            </div>
                        </div>
                        <div class="row small">
                            <div class="col-6">
                                <span class="mr-2"><i class="fas fa-circle text-primary"></i> Regular</span>
                            </div>
                            <div class="col-6">
                                <span class="mr-2"><i class="fas fa-circle text-danger"></i> Fines</span>
                            </div>
                            <div class="col-6">
                                <span class="mr-2"><i class="fas fa-circle text-info"></i> Welfare</span>
                            </div>
                            <div class="col-6">
                                <span class="mr-2"><i class="fas fa-circle text-warning"></i> Other</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Compliance Rate -->
                    <div>
                        <h6 class="font-weight-bold">Compliance Rate</h6>
                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar 
                                @if($complianceRate >= 90)
                                    bg-success
                                @elseif($complianceRate >= 75)
                                    bg-info
                                @elseif($complianceRate >= 50)
                                    bg-warning
                                @else
                                    bg-danger
                                @endif"
                                style="width: {{ min(100, $complianceRate) }}%">
                                {{ number_format($complianceRate, 1) }}%
                            </div>
                        </div>
                        <div class="text-center">
                            <span class="badge 
                                @if($complianceRate >= 90)
                                    bg-success
                                @elseif($complianceRate >= 75)
                                    bg-info
                                @elseif($complianceRate >= 50)
                                    bg-warning
                                @else
                                    bg-danger
                                @endif">
                                {{ $complianceStatus }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('admin.reports.member-statement', ['member_id' => $member->id, 'start_date' => now()->subYear()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d'), 'format' => 'html']) }}" 
                       class="btn btn-info btn-sm btn-block">
                        <i class="fas fa-file-invoice-dollar fa-sm"></i> Generate Statement
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Monthly Chart Card -->
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Monthly Contribution Chart</h6>
                </div>
                <div class="card-body">
                    <canvas id="monthlyContributionChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Contributions List Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Contribution History</h6>
            <a href="{{ route('admin.members.contribution.create', $member) }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus fa-sm"></i> Add Contribution
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contributions as $contribution)
                            <tr>
                                <td>{{ $contribution->transaction_date->format('M d, Y') }}</td>
                                <td>{{ $contribution->description }}</td>
                                <td>KES {{ number_format($contribution->amount, 2) }}</td>
                                <td>
                                    @if($contribution->verification_status === 'verified')
                                        <span class="badge bg-success">Verified</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @if($contribution->verification_status === 'pending')
                                            <form action="{{ route('admin.contributions.verify', $contribution) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> Verify
                                                </button>
                                            </form>
                                            <a href="{{ route('admin.contributions.edit', $contribution) }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                data-bs-toggle="modal" data-bs-target="#deleteContributionModal{{ $contribution->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Delete Contribution Modal -->
                                    <div class="modal fade" id="deleteContributionModal{{ $contribution->id }}" tabindex="-1" 
                                         aria-labelledby="deleteContributionModalLabel{{ $contribution->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteContributionModalLabel{{ $contribution->id }}">
                                                        Confirm Delete
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete this contribution?<br>
                                                    <strong>Date:</strong> {{ $contribution->transaction_date->format('M d, Y') }}<br>
                                                    <strong>Amount:</strong> KES {{ number_format($contribution->amount, 2) }}<br>
                                                    <strong>Description:</strong> {{ $contribution->description }}
                                                    
                                                    @if($contribution->verification_status === 'verified')
                                                        <div class="alert alert-warning mt-3">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            This is a verified contribution. Deleting it will also reverse the corresponding transaction.
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form action="{{ route('admin.contributions.destroy', $contribution) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No contributions found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $contributions->links() }}
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
    var ctx = document.getElementById("monthlyContributionChart");
    var myLineChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($monthlyData['months']),
            datasets: [
                {
                    label: "Contributions",
                    backgroundColor: "rgba(78, 115, 223, 0.8)",
                    borderColor: "rgba(78, 115, 223, 1)",
                    data: @json($monthlyData['regular']),
                    order: 2
                },
                {
                    label: "Target",
                    type: 'line',
                    backgroundColor: "rgba(0, 0, 0, 0)",
                    borderColor: "#1cc88a",
                    pointBackgroundColor: "#1cc88a",
                    pointBorderColor: "#1cc88a",
                    pointHoverBackgroundColor: "#1cc88a",
                    pointHoverBorderColor: "#1cc88a",
                    data: @json($monthlyData['targets']),
                    borderWidth: 2,
                    borderDash: [5, 5],
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
                        maxTicksLimit: 6
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