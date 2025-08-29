@extends('management.layouts.master')
@section('title')
    Edit Approval Module
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Edit Approval Module</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('approval-modules.update', $approvalModule->id) }}" method="POST"
                            id="ajaxSubmit">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="url" value="{{ route('approval-modules.index') }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Module Name</label>
                                        <input type="text" class="form-control" id="name" name="name" disabled
                                            value="{{ $approvalModule->name }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="slug">Slug</label>
                                        <input type="text" class="form-control" id="slug" name="slug" disabled
                                            value="{{ $approvalModule->slug }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="model_class">Model Class</label>
                                <select class="form-control" id="model_class" name="model_class" disabled>
                                    <option value="">Select Model Class</option>
                                    @php
                                        $allModels = [
                                            [
                                                'value' => 'App\Models\PaymentVoucher',
                                                'label' => 'Payment Voucher',
                                            ],
                                            [
                                                'value' => 'App\Models\Procurement\Store\PurchaseRequestData',
                                                'label' => 'Purchase Request Item',
                                            ],
                                        ];
                                    @endphp
                                    @foreach ($allModels as $class)
                                        <option value="{{ $class['value'] }}"
                                            {{ $approvalModule->model_class == $class['value'] ? 'selected' : '' }}>
                                            {{ $class['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Fully qualified class name if applicable</small>
                            </div>

                            <div class="form-group form-check">
                                <input type="checkbox" class="form-check-input" id="requires_sequential_approval" disabled
                                    name="requires_sequential_approval" value="1"
                                    {{ $approvalModule->requires_sequential_approval ? 'checked' : '' }}>
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
                                        @php
                                            $selectedRoles = [];
                                            $unselectedRoles = [];
                                            $selectedModuleRoles = [];

                                            foreach ($roles as $role) {
                                                $moduleRole = $approvalModule->roles
                                                    ->where('role_id', $role->id)
                                                    ->first();
                                                $isChecked = $moduleRole ? true : false;

                                                if ($isChecked) {
                                                    $selectedRoles[] = [
                                                        'role' => $role,
                                                        'moduleRole' => $moduleRole,
                                                        'isChecked' => $isChecked,
                                                        'approvalOrder' => $moduleRole->approval_order,
                                                    ];
                                                } else {
                                                    $unselectedRoles[] = [
                                                        'role' => $role,
                                                        'moduleRole' => null,
                                                        'isChecked' => false,
                                                    ];
                                                }
                                            }

                                            $selectedModuleRoles = collect($selectedRoles)
                                                ->sortBy('approvalOrder')
                                                ->values()
                                                ->all();
                                        @endphp

                                        @foreach ($selectedModuleRoles as $index => $roleData)
                                            @php
                                                $role = $roleData['role'];
                                                $moduleRole = $roleData['moduleRole'];
                                                $isChecked = $roleData['isChecked'];
                                            @endphp
                                            <div class="form-row mb-2 role-row" data-role-id="{{ $role->id }}">
                                                <div class="col-md-1 handle" style="cursor: move;">
                                                    <i class="fa fa-arrows-alt"></i>
                                                    <input type="hidden" class="role-order"
                                                        name="roles[{{ $role->id }}][order]"
                                                        value="{{ $isChecked ? $moduleRole->approval_order : $index }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input role-checkbox" type="checkbox"
                                                            name="roles[{{ $role->id }}][id]"
                                                            value="{{ $role->id }}" id="role_{{ $role->id }}"
                                                            {{ $isChecked ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                                            {{ $role->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="number" class="form-control approval-count"
                                                        name="roles[{{ $role->id }}][count]" min="1"
                                                        value="{{ $isChecked ? $moduleRole->approval_count : 1 }}"
                                                        {{ $isChecked ? '' : 'disabled' }}>
                                                </div>
                                                <div class="col-md-4">
                                                    @if ($isChecked)
                                                        <div class="approval-progress">
                                                            <small class="text-muted">Current order:
                                                                {{ $moduleRole->approval_order + 1 }}</small>
                                                        </div>
                                                    @else
                                                        <div class="approval-progress">
                                                            <small class="text-muted">Will be assigned order when
                                                                checked</small>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach

                                        @foreach ($unselectedRoles as $index => $roleData)
                                            @php
                                                $role = $roleData['role'];
                                                $isChecked = $roleData['isChecked'];
                                            @endphp
                                            <div class="form-row mb-2 role-row" data-role-id="{{ $role->id }}">
                                                <div class="col-md-1 handle" style="cursor: move;">
                                                    <i class="fa fa-arrows-alt"></i>
                                                    <input type="hidden" class="role-order"
                                                        name="roles[{{ $role->id }}][order]"
                                                        value="{{ $index + count($selectedRoles) }}">
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input role-checkbox" type="checkbox"
                                                            name="roles[{{ $role->id }}][id]"
                                                            value="{{ $role->id }}" id="role_{{ $role->id }}"
                                                            {{ $isChecked ? 'checked' : '' }}>
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
                                                    <div class="approval-progress">
                                                        <small class="text-muted">Will be assigned order when
                                                            checked</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <button type="submit" class="btn btn-primary">Update</button>
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
            $('.role-checkbox').change(function() {
                const approvalCountInput = $(this).closest('.role-row').find('.approval-count');
                approvalCountInput.prop('disabled', !this.checked);
                if (!this.checked) {
                    approvalCountInput.val('1');

                    const roleRow = $(this).closest('.role-row');
                    $('#roles-container').append(roleRow);

                    updateRoleOrders();
                } else {
                    const roleRow = $(this).closest('.role-row');
                    const firstUnchecked = $('.role-checkbox:not(:checked)').first().closest('.role-row');

                    if (firstUnchecked.length) {
                        roleRow.insertBefore(firstUnchecked);
                    } else {
                        $('#roles-container').append(roleRow);
                    }

                    updateRoleOrders();
                }
            });

            new Sortable(document.getElementById('roles-container'), {
                handle: '.handle',
                animation: 150,
                onEnd: function() {
                    updateRoleOrders();
                }
            });

            function updateRoleOrders() {
                $('.role-row').each(function(index) {
                    const checkbox = $(this).find('.role-checkbox');
                    $(this).find('.role-order').val(index);

                    if (checkbox.prop('checked')) {
                        $(this).find('.approval-progress').html(
                            `<small class="text-muted">Current order: ${index + 1}</small>`
                        );
                    }
                });
            }
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

        .approval-progress {
            padding-top: 8px;
        }

        /* Visual indicator for selected roles */
        .role-checkbox:checked~.form-check-label {
            font-weight: bold;
        }
    </style>
@endsection
