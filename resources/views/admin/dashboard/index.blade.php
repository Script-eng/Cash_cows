@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Member Management</h1>
        <a href="{{ route('admin.members.create') }}" class="d-none d-sm-inline-block btn btn-primary shadow-sm">
            <i class="fas fa-user-plus fa-sm text-white-50"></i> Add New Member
        </a>
    </div>

    <!-- Filters Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Member Filters</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.members.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                            placeholder="Name, Email or Phone" value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="compliance">Compliance</label>
                        <select class="form-select" id="compliance" name="compliance">
                            <option value="">All Compliance Levels</option>
                            <option value="high" {{ request('compliance') == 'high' ? 'selected' : '' }}>
                                High (90%+)
                            </option>
                            <option value="medium" {{ request('compliance') == 'medium' ? 'selected' : '' }}>
                                Medium (50-90%)
                            </option>
                            <option value="low" {{ request('compliance') == 'low' ? 'selected' : '' }}>
                                Low (Below 50%)
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="sort_by">Sort By</label>
                        <select class="form-select" id="sort_by" name="sort_by">
                            <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Name</option>
                            <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Join Date</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="sort_direction">Direction</label>
                        <select class="form-select" id="sort_direction" name="sort_direction">
                            <option value="asc" {{ request('sort_direction') == 'asc' ? 'selected' : '' }}>Ascending</option>
                            <option value="desc" {{ request('sort_direction') == 'desc' || !request('sort_direction') ? 'selected' : '' }}>
                                Descending
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.members.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Members List Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Members List</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in"
                    aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Export Options:</div>
                    <a class="dropdown-item" href="#">Export as CSV</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="membersTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Contributions</th>
                            <th>Compliance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->email }}</td>
                                <td>{{ $member->phone_number }}</td>
                                <td>
                                    <span class="badge bg-primary">{{ $member->total_contributions }} contributions</span><br>
                                    <strong>KES {{ number_format($member->contribution_amount ?? 0, 2) }}</strong>
                                </td>
                                <td>
                                    @if(isset($member->compliance_rate))
                                        <div class="d-flex align-items-center">
                                            <div class="progress mr-2" style="width: 80%; height: 8px;">
                                                <div class="progress-bar 
                                                    @if($member->compliance_rate >= 90)
                                                        bg-success
                                                    @elseif($member->compliance_rate >= 75)
                                                        bg-info
                                                    @elseif($member->compliance_rate >= 50)
                                                        bg-warning
                                                    @else
                                                        bg-danger
                                                    @endif
                                                " 
                                                role="progressbar" style="width: {{ min(100, $member->compliance_rate) }}%"></div>
                                            </div>
                                            <span class="small">{{ number_format($member->compliance_rate, 1) }}%</span>
                                        </div>
                                        <span class="small text-muted">{{ $member->compliance_status }}</span>
                                    @else
                                        <span class="text-muted">Not calculated</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.members.show', $member) }}" class="btn btn-info btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.members.contribution.create', $member) }}" 
                                            class="btn btn-success btn-sm" title="Add Contribution">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                            data-bs-toggle="modal" data-bs-target="#deleteModal{{ $member->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal{{ $member->id }}" tabindex="-1" 
                                        aria-labelledby="deleteModalLabel{{ $member->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel{{ $member->id }}">
                                                        Confirm Delete
                                                    </h5>
                                                    <button type="button" class="btn-close" 
                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete <strong>{{ $member->name }}</strong>? 
                                                    This action cannot be undone.
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" 
                                                        data-bs-dismiss="modal">Cancel</button>
                                                    <form action="{{ route('admin.members.destroy', $member) }}" method="POST">
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
                                <td colspan="6" class="text-center">No members found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-4">
                {{ $members->links() }}
            </div>
        </div>
    </div>
</div>
@endsection