@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add Contribution for {{ $member->name }}</h1>
        <a href="{{ route('admin.members.show', $member) }}" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Member
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-7 mx-auto">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Contribution Details</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.members.contribution.store', $member) }}" method="POST">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="contribution_type">Contribution Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('contribution_type') is-invalid @enderror" 
                                        id="contribution_type" name="contribution_type" required>
                                        <option value="monthly" {{ old('contribution_type') == 'monthly' ? 'selected' : '' }}>
                                            Monthly Contribution
                                        </option>
                                        <option value="welfare" {{ old('contribution_type') == 'welfare' ? 'selected' : '' }}>
                                            Welfare Fee
                                        </option>
                                        <option value="fine" {{ old('contribution_type') == 'fine' ? 'selected' : '' }}>
                                            Fine
                                        </option>
                                        <option value="registration" {{ old('contribution_type') == 'registration' ? 'selected' : '' }}>
                                            Registration Fee
                                        </option>
                                        <option value="opc" {{ old('contribution_type') == 'opc' ? 'selected' : '' }}>
                                            OPC Contribution
                                        </option>
                                        <option value="other" {{ old('contribution_type') == 'other' ? 'selected' : '' }}>
                                            Other
                                        </option>
                                    </select>
                                    @error('contribution_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount">Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">KES</span>
                                        <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                            id="amount" name="amount" value="{{ old('amount') }}" step="0.01" required>
                                    </div>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_date">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('transaction_date') is-invalid @enderror" 
                                        id="transaction_date" name="transaction_date" value="{{ old('transaction_date', now()->format('Y-m-d')) }}" required>
                                    @error('transaction_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="verification_status">Status <span class="text-danger">*</span></label>
                                    <select class="form-select @error('verification_status') is-invalid @enderror" 
                                        id="verification_status" name="verification_status" required>
                                        <option value="verified" {{ old('verification_status') == 'verified' ? 'selected' : '' }}>
                                            Verified (Add to account immediately)
                                        </option>
                                        <option value="pending" {{ old('verification_status') == 'pending' ? 'selected' : '' }}>
                                            Pending (Requires verification)
                                        </option>
                                    </select>
                                    @error('verification_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="description">Description</label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror" 
                                id="description" name="description" value="{{ old('description') }}" 
                                placeholder="Leave blank for automatic description">
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                If left blank, a description will be automatically generated based on the contribution type and date.
                            </small>
                        </div>
                        
                        <div class="alert alert-info" id="suggestedAmountAlert">
                            <i class="fas fa-info-circle mr-1"></i>
                            <span id="suggestedAmountText">
                                Select a contribution type to see suggested amounts.
                            </span>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Save Contribution
                            </button>
                            <a href="{{ route('admin.members.show', $member) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const contributionTypeSelect = document.getElementById('contribution_type');
        const amountInput = document.getElementById('amount');
        const suggestedAmountAlert = document.getElementById('suggestedAmountAlert');
        const suggestedAmountText = document.getElementById('suggestedAmountText');
        
        // Function to update suggested amount based on contribution type
        function updateSuggestedAmount() {
            const contributionType = contributionTypeSelect.value;
            const currentMonth = new Date().getMonth();
            
            // Set default amounts based on contribution type
            let suggestedAmount = 0;
            let message = '';
            
            switch (contributionType) {
                case 'monthly':
                    // June & July = 2000, August & September = 2050, rest = 2200
                    if (currentMonth === 5 || currentMonth === 6) { // June (5) or July (6)
                        suggestedAmount = 2000;
                    } else if (currentMonth === 7 || currentMonth === 8) { // August (7) or September (8)
                        suggestedAmount = 2050;
                    } else {
                        suggestedAmount = 2200;
                    }
                    message = `The suggested monthly contribution amount for this month is KES ${suggestedAmount.toLocaleString()}.`;
                    break;
                case 'welfare':
                    suggestedAmount = 100;
                    message = `The standard welfare fee is KES ${suggestedAmount.toLocaleString()} per month.`;
                    break;
                case 'fine':
                    suggestedAmount = 200;
                    message = `The standard fine for late payment is KES ${suggestedAmount.toLocaleString()}.`;
                    break;
                case 'registration':
                    suggestedAmount = 1000;
                    message = `The standard registration fee is KES ${suggestedAmount.toLocaleString()}.`;
                    break;
                case 'opc':
                    suggestedAmount = 2500;
                    message = `The OPC (Olpajeta trip) contribution is KES ${suggestedAmount.toLocaleString()}.`;
                    break;
                default:
                    message = 'Enter the appropriate amount for this contribution.';
                    break;
            }
            
            // Update the UI
            if (amountInput.value === '' || amountInput.value === '0') {
                amountInput.value = suggestedAmount;
            }
            
            suggestedAmountText.textContent = message;
        }
        
        // Update on initial load and when contribution type changes
        updateSuggestedAmount();
        contributionTypeSelect.addEventListener('change', updateSuggestedAmount);
    });
</script>
@endsection