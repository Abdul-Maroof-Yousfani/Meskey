<style>
    .info-label {
        font-weight: 600;
        color: #555;
        font-size: 13px;
    }
    .info-value {
        font-size: 14px;
        color: #222;
        margin-bottom: 12px;
    }
</style>

@php
    $conf = $inspection->confirmation;
    $isCompleted = $conf && $conf->is_completed;
    $isApproved = $conf && strtolower($conf->am_approval_status ?? '') === 'approved';
    $vehicleNo = $conf?->vehicle_no ?? $inspection->vehicle_no;
@endphp

<div class="row">
    <div class="col-12">
        <div class="card shadow-none border mb-0">
            <div class="card-body">

                {{-- Status Overview Banner --}}
                <div class="alert alert-light border mb-3 p-2 d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="text-primary font-medium-1">
                            Pre Sale Dekh #{{ $inspection->inspection_no }}
                        </strong>
                        <span class="badge badge-success ml-2">Stage 1 Approved</span>
                    </div>
                    <div>
                        @if($isApproved)
                            <span class="badge badge-success ml-2">Confirmation Approved</span>
                        @elseif($isCompleted)
                            <span class="badge badge-info ml-2">Confirmation Approval Pending</span>
                        @else
                            <span class="badge badge-warning ml-2">Pending Completion</span>
                        @endif
                    </div>
                </div>

                {{-- General Info --}}
                <div class="row border-bottom pb-2 mb-3">
                    <div class="col-md-4">
                        <div class="info-label">Location:</div>
                        <div class="info-value">
                            <span class="badge badge-light border text-dark font-medium-1">
                                {{ $inspection->location?->name ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Dekh Number:</div>
                        <div class="info-value font-weight-bold text-primary">#{{ $inspection->inspection_no }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Dekh Date:</div>
                        <div class="info-value">{{ $inspection->date ? $inspection->date->format('d M Y') : 'N/A' }}</div>
                    </div>
                </div>

                {{-- Party Info --}}
                <div class="row border-bottom pb-2 mb-3">
                    <div class="col-md-4">
                        <div class="info-label">Party Name:</div>
                        <div class="info-value font-weight-bold">{{ $inspection->party_name }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Party Contact No:</div>
                        <div class="info-value">{{ $inspection->party_contact_no ?: 'N/A' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Reference:</div>
                        <div class="info-value font-weight-bold text-dark">{{ $inspection->reference ?: 'N/A' }}</div>
                    </div>
                </div>

                {{-- Items, Factory, Section & Weights --}}
                <div class="row border-bottom pb-3 mb-3">
                    <div class="col-12">
                        <div class="info-label mb-2"><i class="ft-package"></i> Items Details:</div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm m-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="width: 5%;">#</th>
                                        <th style="width: 30%;">Item (Product)</th>
                                        <th style="width: 25%;">Factory</th>
                                        <th style="width: 25%;">Section</th>
                                        <th class="text-right" style="width: 15%;">Weight sample in (kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalWeight = 0; @endphp
                                    @forelse($inspection->items as $idx => $pi)
                                        @php $totalWeight += (float)$pi->weight; @endphp
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="font-weight-bold text-dark">{{ $pi->item?->name ?? 'N/A' }}</td>
                                            <td>{{ $pi->factory?->name ?? '-' }}</td>
                                            <td>{{ $pi->section?->name ?? '-' }}</td>
                                            <td class="text-right font-weight-bold">{{ number_format($pi->weight, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No items recorded.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-light font-weight-bold">
                                    <tr>
                                        <td colspan="4" class="text-right">Total Weight:</td>
                                        <td class="text-right text-primary font-medium-1">{{ number_format($totalWeight, 2) }} kg</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Remarks & Creator --}}
                <div class="row border-bottom pb-2 mb-3">
                    <div class="col-md-8">
                        <div class="info-label">Remarks:</div>
                        <div class="info-value text-muted font-italic">{{ $inspection->remarks ?: 'No remarks provided.' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Prepared By:</div>
                        <div class="info-value">{{ $inspection->creator?->name ?? 'System' }} ({{ $inspection->created_at ? $inspection->created_at->format('d M Y, h:i A') : '' }})</div>
                    </div>
                </div>

                {{-- ================================================================= --}}
                {{-- STAGE 2: MARK AS COMPLETE SECTION (Controlled by Permission)      --}}
                {{-- ================================================================= --}}
                <div class="card border mb-3" style="background-color: #fcfdfe;">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <strong class="text-dark">
                            1. Dekh Completion Status
                        </strong>
                        @if($isCompleted)
                            <span class="badge badge-success">
                                Completed by {{ $conf->completedBy?->name ?? 'User' }} on {{ $conf->completed_at ? \Carbon\Carbon::parse($conf->completed_at)->format('d M Y, h:i A') : '' }}
                            </span>
                        @else
                            <span class="badge badge-warning">Pending Completion</span>
                        @endif
                    </div>
                    <div class="card-body p-3">
                        @if(!$isCompleted)
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <p class="mb-0 text-muted">
                                        Once the Dekh inspection is fully verified on ground, mark this Dekh as complete to initiate the confirmation approval cycle.
                                    </p>
                                </div>
                                <div class="col-md-4 text-right">
                                    @canAccess('dekh-confirmation-complete')
                                        <button type="button" class="btn btn-success btn-lg font-weight-bold" id="btnMarkComplete"
                                            onclick="markDekhComplete({{ $inspection->id }})">
                                            <i class="ft-check-circle mr-1"></i> Mark as Complete
                                        </button>
                                    @else
                                        <span class="badge badge-light border text-muted p-2" title="Permission required: dekh-confirmation-complete">
                                            <i class="ft-lock mr-1"></i> Awaiting completion by authorized in-charge
                                        </span>
                                    @endcanAccess
                                </div>
                            </div>
                        @else
                            <div class="alert alert-success mb-0 py-2">
                                <strong>Completed!</strong> This Pre Sale Dekh was marked as complete by 
                                <strong>{{ $conf->completedBy?->name ?? 'Authorized User' }}</strong> on 
                                <strong>{{ $conf->completed_at ? \Carbon\Carbon::parse($conf->completed_at)->format('d M Y, h:i A') : '' }}</strong>.
                            </div>
                        @endif
                    </div>
                </div>

                {{-- ================================================================= --}}
                {{-- STAGE 3: CONFIRMATION APPROVAL FLOW (Only after Mark as Complete) --}}
                {{-- ================================================================= --}}
                @if($isCompleted && $conf)
                    <div class="card border mb-3">
                        <div class="card-header bg-light py-2">
                            <strong class="text-dark">
                                2. Dekh Confirmation Approval Workflow
                            </strong>
                        </div>
                        <div class="card-body p-3">
                            <x-approval-status :model="$conf" :list-refresh="route('sales.get.dekh-confirmation.list')" />
                        </div>
                    </div>
                @else
                    <div class="alert alert-secondary border py-2 mb-3 text-muted">
                        <i class="ft-info mr-1"></i> <strong>Approval Notice:</strong> Dekh confirmation approval will be automatically initiated once this inspection is marked as complete.
                    </div>
                @endif

                {{-- ================================================================= --}}
                {{-- STAGE 4: VEHICLE NUMBER ASSIGNMENT (Only after Confirmation Approved)--}}
                {{-- ================================================================= --}}
                <div class="card border mb-0" style="background-color: #f8fbff;">
                    <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                        <strong class="text-dark">
                            3. Vehicle Assignment
                        </strong>
                        @if(!empty($vehicleNo))
                            <span class="badge badge-primary px-2 py-1 font-medium-1">
                                Vehicle: {{ $vehicleNo }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body p-3">
                        @if($isApproved)
                            @canAccess('dekh-confirmation-vehicle')
                                <form id="vehicleAssignForm" onsubmit="saveVehicleNumber(event, {{ $inspection->id }})">
                                    @csrf
                                    <div class="row align-items-end">
                                        <div class="col-md-7">
                                            <label class="form-label font-weight-bold text-dark">
                                                Vehicle Number <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="ft-truck"></i></span>
                                                </div>
                                                <input type="text" name="vehicle_no" id="vehicle_no" class="form-control"
                                                    value="{{ $vehicleNo }}" placeholder="e.g. LES-1234, TKL-567" required autocomplete="off">
                                            </div>
                                            <small class="text-muted">Enter the verified vehicle number for this confirmed Dekh.</small>
                                        </div>
                                        <div class="col-md-5 text-right">
                                            <button type="submit" class="btn btn-primary font-weight-bold" id="btnSaveVehicle">
                                                Save Vehicle Number
                                            </button>
                                        </div>
                                    </div>
                                </form>
                                @if($conf && $conf->vehicle_assigned_at)
                                    <div class="mt-2 text-muted small">
                                        <i class="ft-info mr-1"></i> Last updated by <strong>{{ $conf->vehicleAssignedBy?->name ?? 'User' }}</strong> on {{ \Carbon\Carbon::parse($conf->vehicle_assigned_at)->format('d M Y, h:i A') }}.
                                    </div>
                                @endif
                            @else
                                {{-- User without vehicle permission --}}
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="p-2 border rounded bg-white">
                                            <strong>Assigned Vehicle:</strong>
                                            @if(!empty($vehicleNo))
                                                <span class="badge badge-primary font-medium-1 ml-2">{{ $vehicleNo }}</span>
                                                @if($conf && $conf->vehicle_assigned_at)
                                                    <small class="text-muted ml-2">(Assigned by {{ $conf->vehicleAssignedBy?->name ?? 'User' }} on {{ \Carbon\Carbon::parse($conf->vehicle_assigned_at)->format('d M Y') }})</small>
                                                @endif
                                            @else
                                                <span class="badge badge-light border text-muted ml-2">Pending Vehicle Assignment by authorized staff</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endcanAccess
                        @else
                            <div class="text-muted font-italic">
                                <i class="ft-lock mr-1"></i> Vehicle number field will be unlocked once Dekh Confirmation is <strong>Approved</strong>.
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="row bottom-button-bar mt-3">
    <div class="col-12 text-right">
        <button type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</button>
    </div>
</div>

<script>
    function markDekhComplete(inspectionId) {
        Swal.fire({
            title: 'Mark as Complete?',
            text: 'Are you sure you want to mark this Dekh as complete? This will initiate the confirmation approval cycle.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Mark Complete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#btnMarkComplete').prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Processing...');

                $.ajax({
                    url: `{{ url('sales/dekh-confirmation') }}/${inspectionId}/mark-complete`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: res.success || 'Dekh marked as complete successfully!',
                            confirmButtonColor: '#3085d6',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        $('#btnMarkComplete').prop('disabled', false).html('<i class="ft-check-circle mr-1"></i> Mark as Complete');
                        let err = xhr.responseJSON?.message || xhr.responseJSON?.error || 'Failed to mark as complete.';
                        Swal.fire('Error', err, 'error');
                    }
                });
            }
        });
    }

    function saveVehicleNumber(e, inspectionId) {
        e.preventDefault();
        let vehicleNo = $('#vehicle_no').val().trim();
        if (!vehicleNo) {
            Swal.fire('Error', 'Please enter a valid vehicle number.', 'warning');
            return;
        }

        $('#btnSaveVehicle').prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Saving...');

        $.ajax({
            url: `{{ url('sales/dekh-confirmation') }}/${inspectionId}/save-vehicle`,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                vehicle_no: vehicleNo
            },
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: res.success || 'Vehicle number saved successfully!',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                $('#btnSaveVehicle').prop('disabled', false).html('Save Vehicle Number');
                let err = xhr.responseJSON?.message || xhr.responseJSON?.error || 'Failed to save vehicle number.';
                Swal.fire('Error', err, 'error');
            }
        });
    }
</script>
