@extends('management.layouts.master')
@section('title')
    Approval Modules
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Approval Modules</h4>
                            <a href="{{ route('approval-modules.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Model Class</th>
                                        <th>Roles</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($modules as $module)
                                        <tr>
                                            <td>{{ $module->id }}</td>
                                            <td>{{ $module->name }}</td>
                                            <td>{{ $module->slug }}</td>
                                            <td>{{ $module->model_class ?? 'N/A' }}</td>
                                            <td>
                                                @foreach ($module->roles as $role)
                                                    <span class="badge badge-primary">
                                                        {{ $role->role->name }} ({{ $role->approval_count }})
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $module->is_active ? 'success' : 'danger' }}">
                                                    {{ $module->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('approval-modules.edit', $module->id) }}"
                                                    class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('approval-modules.destroy', $module->id) }}"
                                                    method="POST" style="display: inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">No approval modules found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $modules->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
