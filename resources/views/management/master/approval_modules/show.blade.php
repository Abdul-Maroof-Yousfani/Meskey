@extends('management.layouts.master')
@section('title')
    Approval Module Details
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Approval Module Details</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Name:</label>
                                    <p class="form-control-static">{{ $approvalModule->name }}</p>
                                </div>
                                <div class="form-group">
                                    <label>Slug:</label>
                                    <p class="form-control-static">{{ $approvalModule->slug }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Model Class:</label>
                                    <p class="form-control-static">{{ $approvalModule->model_class ?? 'N/A' }}</p>
                                </div>
                                <div class="form-group">
                                    <label>Sequential Approval:</label>
                                    <p class="form-control-static">
                                        <span
                                            class="badge badge-{{ $approvalModule->requires_sequential_approval ? 'success' : 'danger' }}">
                                            {{ $approvalModule->requires_sequential_approval ? 'Yes' : 'No' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Approval Roles</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Role</th>
                                                <th>Required Approvals</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($approvalModule->roles as $role)
                                                <tr>
                                                    <td>{{ $role->approval_order + 1 }}</td>
                                                    <td>{{ $role->role->name }}</td>
                                                    <td>{{ $role->approval_count }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="text-center">No roles assigned</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <a href="{{ route('approval-modules.index') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
