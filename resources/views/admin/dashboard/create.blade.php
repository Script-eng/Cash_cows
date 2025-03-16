@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Member</h1>
        <a href="{{ route('admin.members.index') }}" class="d-none d-sm-inline-block btn btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Members
        </a>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Member Information</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.members.store') }}" method="POST">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                        id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                        id="email" name="email" value="{{ old('email') }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone_number">Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('phone_number') is-invalid @enderror" 
                                        id="phone_number" name="phone_number" value="{{ old('phone_number') }}" required>
                                    @error('phone_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Format: Enter digits only. This will be used to generate the initial password.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_number">ID/Passport Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('id_number') is-invalid @enderror" 
                                        id="id_number" name="id_number" value="{{ old('id_number') }}" required>
                                    @error('id_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <hr class="mt-4 mb-4">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="registration_fee">Registration Fee</label>
                                    <div class="input-group">
                                        <span class="input-group-text">KES</span>
                                        <input type="number" class="form-control @error('registration_fee') is-invalid @enderror" 
                                            id="registration_fee" name="registration_fee" value="{{ old('registration_fee', 1000) }}" step="0.01">
                                    </div>
                                    @error('registration_fee')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        The registration fee will be added as a contribution record.
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="send_welcome_email" name="send_welcome_email" checked>
                                    <label class="form-check-label" for="send_welcome_email">
                                        Send welcome email with login instructions
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            A temporary password will be automatically generated using the format: 
                            <strong>CashCows2024_XXXX</strong> (where XXXX are the last 4 digits of the phone number).
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus mr-1"></i> Create Member
                            </button>
                            <a href="{{ route('admin.members.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection