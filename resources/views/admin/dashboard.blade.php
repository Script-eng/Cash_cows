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
                                    <h5 class="card-title">Total Members</h5>
                                    <h2 class="display-4">{{ $totalMembers }}</h2>
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
            
