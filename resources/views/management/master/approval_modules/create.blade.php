@extends('management.layouts.master')
@section('title')
    Create Approval Module
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Create Approval Module</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('approval-modules.store') }}" method="POST" id="ajaxSubmit">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Module Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="slug">Slug</label>
                                        <input type="text" class="form-control" id="slug" name="slug" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="model_class">Model Class (optional)</label>
                                <input type="text" class="form-control" id="model_class" name="model_class">
                                <small class="text-muted">Fully qualified class name if applicable</small>
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="requires_sequential_approval"
                                    name="requires_sequential_approval" value="1">
                                <label class="form-check-label" for="requires_sequential_approval">
                                    Requires sequential approval
                                </label>
                                <small class="text-muted d-block">
                                    When enabled, each role must complete their approvals before the next role can approve
                                </small>
                            </div>

                            <div class="card mt-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Approval Roles</h5>
                                    <small class="text-muted">Drag to reorder (order matters if sequential approval is
                                        enabled)</small>
                                </div>
                                <div class="card-body">
                                    <div id="roles-container" class="sortable-roles">
                                        @foreach ($roles as $role)
                                            <div class="form-row mb-2 role-row" data-role-id="{{ $role->id }}">
                                                <div class="col-md-1 handle" style="cursor: move;">
                                                    <i class="fas fa-arrows-alt"></i>
                                                    <input type="hidden" class="role-order"
                                                        name="roles[{{ $role->id }}][order]"
                                                        value="{{ $loop->index }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input role-checkbox" type="checkbox"
                                                            name="roles[{{ $role->id }}][id]"
                                                            value="{{ $role->id }}" id="role_{{ $role->id }}">
                                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                                            {{ $role->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="number" class="form-control approval-count"
                                                        name="roles[{{ $role->id }}][count]" min="1"
                                                        value="1" disabled>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="approval-progress d-none">
                                                        <small class="text-muted">Approval progress will appear here when
                                                            editing</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="{{ route('approval-modules.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        $(document).ready(function() {
            // Enable/disable approval count based on checkbox
            $('.role-checkbox').change(function() {
                const approvalCountInput = $(this).closest('.role-row').find('.approval-count');
                approvalCountInput.prop('disabled', !this.checked);
                if (!this.checked) {
                    approvalCountInput.val('1');
                }
            });

            // Initialize sortable
            new Sortable(document.getElementById('roles-container'), {
                handle: '.handle',
                animation: 150,
                onEnd: function() {
                    $('.role-row').each(function(index) {
                        $(this).find('.role-order').val(index);
                    });
                }
            });
        });
    </script>
@endsection

@section('style')
    <style>
        .sortable-roles .role-row {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .sortable-roles .role-row:hover {
            background: #e9ecef;
        }

        .sortable-roles .handle {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }

        .sortable-roles .form-check {
            padding-left: 0;
        }

        .sortable-roles .form-check-input {
            margin-left: 0;
            margin-right: 8px;
        }
    </style>
@endsection
