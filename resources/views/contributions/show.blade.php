@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Contribution Details</h5>
                    <a href="{{ route('contributions.index') }}" class="btn btn-sm btn-secondary">Back to List</a>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Amount:</div>
                        <div class="col-md-8">{{ number_format($contribution->amount, 2) }}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Date:</div>
                        <div class="col-md-8">{{ $contribution->transaction_date->format('M d, Y') }}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Description:</div>
                        <div class="col-md-8">{{ $contribution->description ?: 'N/A' }}</div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Status:</div>
                        <div class="col-md-8">
                            @if($contribution->verification_status == 'verified')
                                <span class="badge bg-success">Verified</span>
                            @elseif($contribution->verification_status == 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @else
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </div>
                    </div>
                    
                    @if($contribution->verification_status == 'verified' && $contribution->verified_by)
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Verified By:</div>
                            <div class="col-md-8">{{ $contribution->verifier->name }}</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 fw-bold">Verified On:</div>
                            <div class="col-md-8">{{ $contribution->updated_at->format('M d, Y g:i A') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection