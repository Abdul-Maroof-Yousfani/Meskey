<form action="{{ route('machine-plan-setting.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.machine-plan-setting') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration mb-2">Machine Plan Setting Information</h6>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="mb-1" style="font-size: 12px; font-weight: 600;">Date:</label>
                        <input type="date" name="date" id="date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" style="height: 34px;" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="mb-1" style="font-size: 12px; font-weight: 600;">Plant:</label>
                        <select name="plant_id" id="plant_id" class="form-control select2" style="width: 100%;" required>
                            <option value="">Select Plant</option>
                            @foreach($plants as $plant)
                                <option value="{{ $plant->id }}">{{ $plant->arrivalLocation->name ?? '' }} -
                                    {{ $plant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="mb-1" style="font-size: 12px; font-weight: 600;">Production Voucher:</label>
                        <select name="production_voucher_id" id="production_voucher_id" class="form-control select2" style="width: 100%;">
                            <option value="">Select Production Voucher (Optional)</option>
                            @foreach($productionVouchers as $voucher)
                                <option value="{{ $voucher->id }}">{{ $voucher->prod_no }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="mb-1" style="font-size: 12px; font-weight: 600;">Remarks:</label>
                        <input type="text" name="remarks" id="remarks_input" class="form-control form-control-sm" placeholder="General remarks..." style="height: 34px;">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mt-2" id="productionMachinesSection">
            <h6 class="header-heading-sepration mb-2">Machine Settings</h6>
            <div class="row">
                <div class="col-md-12">
                    <div id="productionMachinesContainer">
                        <div class="text-center text-muted py-3">
                            Please select a plant to load machines
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mt-2">
            <h6 class="header-heading-sepration mb-2">Overall Plant Breakdown</h6>
            <div class="row">
                <div class="col-md-12">
                    <div id="breakdownItemsTable" class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 12px;">
                            <thead style="background: #f4f6f9;">
                                <tr>
                                    <th style="width: 28%">Breakdown Type</th>
                                    <th style="width: 14%">From</th>
                                    <th style="width: 14%">To</th>
                                    <th style="width: 10%">Hours</th>
                                    <th style="width: 26%">Remarks</th>
                                    <th style="width: 8%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="breakdownItemsBody">
                                <tr>
                                    <td>
                                        <select name="breakdown_type_id[]" class="form-control form-control-sm" style="height: 30px;">
                                            <option value="">Select Breakdown Type</option>
                                            @foreach($breakdownTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" name="from[]" class="form-control form-control-sm from-time" style="height: 30px;">
                                    </td>
                                    <td>
                                        <input type="time" name="to[]" class="form-control form-control-sm to-time" style="height: 30px;">
                                    </td>
                                    <td>
                                        <input type="number" name="hours[]" class="form-control form-control-sm hours-input" step="0.01" min="0" readonly style="height: 30px; background: #f8f9fa; text-align: center;">
                                    </td>
                                    <td>
                                        <input type="text" name="breakdown_remarks[]" class="form-control form-control-sm" placeholder="Breakdown remarks..." style="height: 30px;">
                                    </td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-sm btn-primary copythis py-0 px-2" style="height: 26px;"><i class="fa fa-plus"></i></button>
                                        <button type="button" class="btn btn-sm btn-danger removethis py-0 px-2" style="height: 26px;"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar text-right mt-3">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save Machine Plan Setting</button>
        </div>
    </div>
</form>


<script>
    $(document).ready(function () {
        $('#plant_id, #production_voucher_id').select2({
            width: '100%',
            dropdownParent: $('.select2').closest('.modal').length ? $('.select2').closest('.modal') : $(document.body)
        });

        // Load production vouchers and breakdowns when date changes
        $(document).off('change.mps_date', '#date').on('change.mps_date', '#date', function () {
            var selectedDate = $(this).val();
            var plantId = $('#plant_id').val();
            loadProductionVouchers(selectedDate, plantId);
            if (plantId) {
                loadPlantBreakdowns(selectedDate, plantId);
                loadMachines(plantId);
            }
        });

        // Load machines and breakdowns when plant is selected
        $(document).off('change.mps_plant', '#plant_id').on('change.mps_plant', '#plant_id', function () {
            var plantId = $(this).val();
            var selectedDate = $('#date').val();
            loadProductionVouchers(selectedDate, plantId);
            if (plantId) {
                loadMachines(plantId);
                if (selectedDate) {
                    loadPlantBreakdowns(selectedDate, plantId);
                }
            } else {
                $('#productionMachinesContainer').html('<div class="text-center text-muted py-3">Please select a plant to load machines</div>');
            }
        });

        $(document).off('submit.mps_submit', '#ajaxSubmit').on('submit.mps_submit', '#ajaxSubmit', function (e) {
            if (!validateBreakdownTimes() || !validateAllSlotBreakdowns()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });

        calculateHours();
    });

    function renderSlotBreakdownRowHtml(machineId, slotIdx, bd) {
        bd = bd || {};
        var fromVal = bd.from ? bd.from.substring(0, 5) : '';
        var toVal = bd.to ? bd.to.substring(0, 5) : '';
        var hoursVal = bd.hours ? parseFloat(bd.hours).toFixed(2) : '';
        var remarksVal = bd.remarks || '';

        return `
            <tr class="slot-breakdown-row">
                <td style="padding: 3px 6px;">
                    <input type="time" name="slot_breakdown_from[${machineId}][${slotIdx}][]" class="form-control form-control-sm slot-breakdown-from" value="${fromVal}" onchange="validateAndCalculateBreakdown(this, ${machineId})" style="height: 28px; font-size: 11px; padding: 2px 6px;">
                </td>
                <td style="padding: 3px 6px;">
                    <input type="time" name="slot_breakdown_to[${machineId}][${slotIdx}][]" class="form-control form-control-sm slot-breakdown-to" value="${toVal}" onchange="validateAndCalculateBreakdown(this, ${machineId})" style="height: 28px; font-size: 11px; padding: 2px 6px;">
                </td>
                <td style="padding: 3px 6px;">
                    <input type="number" step="0.01" min="0" name="slot_breakdown_hours[${machineId}][${slotIdx}][]" class="form-control form-control-sm slot-breakdown-hours" value="${hoursVal}" readonly style="background: #f8f9fa; height: 28px; font-size: 11px; padding: 2px 6px; text-align: center;">
                </td>
                <td style="padding: 3px 6px;">
                    <input type="text" name="slot_breakdown_remarks[${machineId}][${slotIdx}][]" class="form-control form-control-sm slot-breakdown-remarks" value="${remarksVal}" placeholder="Breakdown remarks..." style="height: 28px; font-size: 11px; padding: 2px 8px;">
                </td>
                <td class="text-center align-middle" style="padding: 3px 6px;">
                    <button type="button" class="btn btn-xs btn-link text-danger p-0" onclick="removeSlotBreakdownRow(this, ${machineId})" title="Delete Breakdown" style="font-size: 13px;"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        `;
    }

    function loadProductionVouchers(date, plantId = null, selectedVoucherId = null) {
        var $voucherSelect = $('#production_voucher_id');
        var currentSelected = (selectedVoucherId !== null && selectedVoucherId !== undefined) ? selectedVoucherId : $voucherSelect.val();
        $voucherSelect.empty().append('<option value="">Select Production Voucher (Optional)</option>');

        if (!plantId) {
            plantId = $('#plant_id').val();
        }

        if (!date) {
            $voucherSelect.trigger('change');
            return;
        }

        $.ajax({
            url: '{{ route("getProductionVouchersByDate") }}',
            type: 'GET',
            data: { 
                date: date,
                plant_id: plantId
            },
            success: function (response) {
                if (response.vouchers && response.vouchers.length > 0) {
                    $.each(response.vouchers, function (index, voucher) {
                        var isSelected = (currentSelected && currentSelected == voucher.id) ? 'selected' : '';
                        $voucherSelect.append(`<option value="${voucher.id}" ${isSelected}>${voucher.prod_no}</option>`);
                    });
                }
                $voucherSelect.trigger('change');
            },
            error: function (xhr) {
                console.error('Error loading production vouchers:', xhr);
                toastr.error('Error loading production vouchers');
            }
        });
    }

    function loadMachines(plantId) {
        var date = $('#date').val();
        var container = $('#productionMachinesContainer');

        if (!plantId) {
            container.html('<div class="text-center text-muted py-3">Please select a plant to load machines</div>');
            return;
        }

        $.ajax({
            url: '{{ route("getMachinesByPlant") }}',
            type: 'GET',
            data: { plant_id: plantId, date: date },
            success: function (response) {
                container.empty();
                if (response.machines && response.machines.length > 0) {
                    let html = `<div class="row">`;
                    $.each(response.machines, function (index, machine) {
                        const isEnabled = machine.is_enabled;
                        const headerBg = isEnabled ? '#e8f3fc' : '#f8f9fa';
                        const statusLabel = isEnabled ? 'Active' : 'Inactive';
                        const statusColor = isEnabled ? '#007bff' : '#6c757d';
                        const cardBorder = isEnabled ? '#93c3f2' : '#e2e8f0';

                        let timeSlotsHtml = '';
                        const slots = (machine.time_slots && machine.time_slots.length > 0) 
                            ? machine.time_slots 
                            : [{ start_time: machine.start_time || '', end_time: machine.end_time || '', breakdowns: [] }];

                        $.each(slots, function(sIdx, slot) {
                            const startTimeVal = slot.start_time || '';
                            const endTimeVal = slot.end_time || '';
                            const slotBreakdowns = slot.breakdowns || [];

                            let bRowsHtml = '';
                            $.each(slotBreakdowns, function(bIdx, bd) {
                                bRowsHtml += renderSlotBreakdownRowHtml(machine.id, sIdx, bd);
                            });

                            const hasBreakdowns = slotBreakdowns.length > 0;

                            timeSlotsHtml += `
                                <div class="time-slot-block mb-2 p-2" data-slot-index="${sIdx}" style="border: 1px solid #e2e8f0; border-radius: 5px; background: #fafbfc;">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between p-1 mb-1" style="background: #f1f4f8; border-radius: 4px; gap: 8px;">
                                        <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
                                            <div class="d-flex align-items-center" style="gap: 5px;">
                                                <label class="mb-0" style="font-size: 11px; font-weight: 600; color: #4a5568;">Start Time:</label>
                                                <input type="time" name="machine_start_time[${machine.id}][]" class="form-control form-control-sm start-time" value="${startTimeVal}" onchange="handleSlotTimeChange(this, ${machine.id})" style="font-size: 11px; height: 28px; width: 130px; background: #fff;">
                                            </div>
                                            <div class="d-flex align-items-center" style="gap: 5px;">
                                                <label class="mb-0" style="font-size: 11px; font-weight: 600; color: #4a5568;">End Time:</label>
                                                <input type="time" name="machine_end_time[${machine.id}][]" class="form-control form-control-sm end-time" value="${endTimeVal}" onchange="handleSlotTimeChange(this, ${machine.id})" style="font-size: 11px; height: 28px; width: 130px; background: #fff;">
                                            </div>
                                            <div class="d-flex align-items-center" style="gap: 5px;">
                                                <label class="mb-0" style="font-size: 11px; font-weight: 600; color: #4a5568;">Duration:</label>
                                                <input type="text" class="form-control form-control-sm duration-display" readonly style="background: #fff; font-weight: bold; font-size: 11px; text-align: center; height: 28px; width: 90px; color: #2b6cb0;">
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center" style="gap: 8px;">
                                            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" onclick="addSlotBreakdownRow(${machine.id}, this)" style="font-size: 11px; height: 26px;">
                                                Inner Breakdown
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2" onclick="removeMachineTimeRow(this, ${machine.id})" title="Remove Time Slot" style="font-size: 11px; height: 26px;">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Child Breakdowns Section -->
                                    <div class="slot-breakdowns-container px-1">
                                        <div class="table-responsive slot-breakdown-table-wrapper" style="${hasBreakdowns ? '' : 'display: none;'}">
                                            <table class="table table-sm table-bordered mb-1" style="font-size: 11px; background: #fff;">
                                                <thead style="background: #f1f5f9; color: #475569;">
                                                    <tr>
                                                        <th style="width: 18%; font-weight: 600; padding: 4px 6px;">Breakdown From</th>
                                                        <th style="width: 18%; font-weight: 600; padding: 4px 6px;">Breakdown To</th>
                                                        <th style="width: 12%; font-weight: 600; padding: 4px 6px; text-align: center;">Hours</th>
                                                        <th style="width: 46%; font-weight: 600; padding: 4px 6px;">Remarks</th>
                                                        <th style="width: 6%; font-weight: 600; padding: 4px 6px; text-align: center;">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="slot-breakdown-tbody">
                                                    ${bRowsHtml}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        // do col-12 here
                        html += `
                            <div class="col-md-6 mb-3">
                                <input type="hidden" name="all_machine_ids[]" value="${machine.id}">
                                <div class="machine-card" id="machine_card_${machine.id}" style="border: 1px solid ${cardBorder}; border-radius: 6px; overflow: hidden; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: all 0.3s;">
                                    <div class="machine-card-header d-flex align-items-center justify-content-between px-3 py-2" id="machine_card_header_${machine.id}" style="background: ${headerBg}; border-bottom: 1px solid ${cardBorder};">
                                        <div class="d-flex align-items-center" style="gap: 8px;">
                                            <i class="fa fa-cogs text-primary" style="font-size: 14px;"></i>
                                            <span style="font-weight: 700; font-size: 13px; color: #2d3748;">${machine.name}</span>
                                        </div>
                                        <div class="d-flex align-items-center" style="gap: 15px;">
                                            <div class="d-flex align-items-center" style="gap: 6px;">
                                                <span style="font-size: 11px; font-weight: 600; color: #4a5568;">Total Hours:</span>
                                                <span class="badge badge-light grand-total-display px-2 py-1" style="font-size: 11px; font-weight: 700; border: 1px solid #cbd5e0; color: #1a202c; min-width: 60px; text-align: center;">0h 0m</span>
                                            </div>
                                            <button type="button" class="btn btn-xs btn-primary py-1 px-2" onclick="addMachineTimeRow(${machine.id})" style="font-size: 11px; font-weight: 600; height: 26px;">
                                                <i class="fa fa-plus mr-1"></i> Add Slot
                                            </button>
                                            <div class="d-flex align-items-center" style="border-left: 1px solid #cbd5e0; padding-left: 12px;">
                                                <label class="machine-toggle-label mb-0" for="machine_${machine.id}" style="cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                                    <span id="machine_status_${machine.id}" style="font-size: 11px; font-weight: bold; color: ${statusColor};">${statusLabel}</span>
                                                    <div class="custom-control custom-switch mb-0">
                                                        <input type="checkbox" class="custom-control-input machine-toggle" name="production_machine_id[]" value="${machine.id}" id="machine_${machine.id}" ${isEnabled ? 'checked' : ''} onchange="updateMachineCardStyle(this, ${machine.id})">
                                                        <label class="custom-control-label" for="machine_${machine.id}"></label>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="machine-card-body p-2" style="background: #fff;">
                                        <div id="machine_time_table_${machine.id}">
                                            ${timeSlotsHtml}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    html += `</div>`;
                    container.append(html);

                    $.each(response.machines, function (index, machine) {
                        calculateMachineTime(machine.id);
                        updateMachineCardStyle($(`#machine_${machine.id}`), machine.id);
                    });
                } else {
                    container.html('<div class="text-center text-muted py-3">No machines found for this plant</div>');
                }

                // Pre-fill or clear remarks and voucher based on selected date
                if (response.existing_plan) {
                    $('#remarks_input, [name="remarks"]').val(response.existing_plan.remarks || '');
                    if (response.existing_plan.production_voucher_id) {
                        $('#production_voucher_id').val(response.existing_plan.production_voucher_id).trigger('change');
                    }
                } else {
                    $('#remarks_input, [name="remarks"]').val('');
                    $('#production_voucher_id').val('').trigger('change');
                }
            },
            error: function (xhr) {
                console.error('Error loading machines:', xhr);
                toastr.error('Error loading machines');
            }
        });
    }

    function loadPlantBreakdowns(date, plantId) {
        if (!date || !plantId) return;

        $.ajax({
            url: '{{ route("getBreakdownsByPlantAndDate") }}',
            type: 'GET',
            data: { date: date, plant_id: plantId },
            success: function (response) {
                if (response.items && response.items.length > 0) {
                    var html = '';
                    $.each(response.items, function (index, item) {
                        var fromVal = item.from ? item.from.substring(0, 5) : '';
                        var toVal = item.to ? item.to.substring(0, 5) : '';
                        var hoursVal = item.hours || '';
                        var remarksVal = item.remarks || '';

                        var optionsHtml = '<option value="">Select Breakdown Type</option>';
                        @foreach($breakdownTypes as $type)
                            var selected = (item.breakdown_type_id == '{{ $type->id }}') ? 'selected' : '';
                            optionsHtml += `<option value="{{ $type->id }}" ${selected}>{{ $type->name }}</option>`;
                        @endforeach

                        html += `
                            <tr>
                                <td>
                                    <select name="breakdown_type_id[]" class="form-control form-control-sm" style="height: 30px;">
                                        ${optionsHtml}
                                    </select>
                                </td>
                                <td>
                                    <input type="time" name="from[]" class="form-control form-control-sm from-time" value="${fromVal}" style="height: 30px;">
                                </td>
                                <td>
                                    <input type="time" name="to[]" class="form-control form-control-sm to-time" value="${toVal}" style="height: 30px;">
                                </td>
                                <td>
                                    <input type="number" name="hours[]" class="form-control form-control-sm hours-input" step="0.01" min="0" value="${hoursVal}" readonly style="height: 30px; background: #f8f9fa; text-align: center;">
                                </td>
                                <td>
                                    <input type="text" name="breakdown_remarks[]" class="form-control form-control-sm" placeholder="Breakdown remarks..." value="${remarksVal}" style="height: 30px;">
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-primary copythis py-0 px-2" style="height: 26px;"><i class="fa fa-plus"></i></button>
                                    <button type="button" class="btn btn-sm btn-danger removethis py-0 px-2" style="height: 26px;"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        `;
                    });
                    $('#breakdownItemsBody').html(html);
                    calculateHours();
                } else {
                    // Reset to 1 clean default empty row if no breakdown exists for this date/plant
                    var optionsHtml = '<option value="">Select Breakdown Type</option>';
                    @foreach($breakdownTypes as $type)
                        optionsHtml += `<option value="{{ $type->id }}">{{ $type->name }}</option>`;
                    @endforeach

                    var emptyRowHtml = `
                        <tr>
                            <td>
                                <select name="breakdown_type_id[]" class="form-control form-control-sm" style="height: 30px;">
                                    ${optionsHtml}
                                </select>
                            </td>
                            <td>
                                <input type="time" name="from[]" class="form-control form-control-sm from-time" style="height: 30px;">
                            </td>
                            <td>
                                <input type="time" name="to[]" class="form-control form-control-sm to-time" style="height: 30px;">
                            </td>
                            <td>
                                <input type="number" name="hours[]" class="form-control form-control-sm hours-input" step="0.01" min="0" readonly style="height: 30px; background: #f8f9fa; text-align: center;">
                            </td>
                            <td>
                                <input type="text" name="breakdown_remarks[]" class="form-control form-control-sm" placeholder="Breakdown remarks..." style="height: 30px;">
                            </td>
                            <td class="text-center align-middle">
                                <button type="button" class="btn btn-sm btn-primary copythis py-0 px-2" style="height: 26px;"><i class="fa fa-plus"></i></button>
                                <button type="button" class="btn btn-sm btn-danger removethis py-0 px-2" style="height: 26px;"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
                    $('#breakdownItemsBody').html(emptyRowHtml);
                    calculateHours();
                }
            },
            error: function (xhr) {
                console.error('Error loading breakdowns:', xhr);
            }
        });
    }

    function timeToMinutes(timeStr) {
        if (!timeStr) return null;
        var parts = timeStr.split(':');
        return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }

    function validateBreakdownTimes($changedInput) {
        var hasError = false;
        var rows = [];

        $('#breakdownItemsBody tr').each(function (index) {
            var $row = $(this);
            var $fromInput = $row.find('.from-time');
            var $toInput = $row.find('.to-time');
            var fromVal = $fromInput.val();
            var toVal = $toInput.val();

            $fromInput.removeClass('is-invalid');
            $toInput.removeClass('is-invalid');

            if (fromVal && toVal) {
                var fromMin = timeToMinutes(fromVal);
                var toMin = timeToMinutes(toVal);

                if (fromMin >= toMin) {
                    $toInput.addClass('is-invalid');
                    toastr.error('End time (' + toVal + ') must be after start time (' + fromVal + ') in row ' + (index + 1));
                    if ($changedInput && $changedInput.is($toInput)) {
                        $toInput.val('');
                    }
                    hasError = true;
                    return false;
                }

                rows.push({
                    index: index,
                    fromMin: fromMin,
                    toMin: toMin,
                    fromVal: fromVal,
                    toVal: toVal,
                    $fromInput: $fromInput,
                    $toInput: $toInput
                });
            }
        });

        if (hasError) {
            calculateHours();
            return false;
        }

        // Check for overlaps among rows
        for (var i = 0; i < rows.length; i++) {
            for (var j = i + 1; j < rows.length; j++) {
                var r1 = rows[i];
                var r2 = rows[j];

                // Overlap condition: max(start1, start2) < min(end1, end2)
                if (Math.max(r1.fromMin, r2.fromMin) < Math.min(r1.toMin, r2.toMin)) {
                    r1.$fromInput.addClass('is-invalid');
                    r1.$toInput.addClass('is-invalid');
                    r2.$fromInput.addClass('is-invalid');
                    r2.$toInput.addClass('is-invalid');

                    toastr.error('Time interval (' + r2.fromVal + ' - ' + r2.toVal + ') in row ' + (r2.index + 1) + ' overlaps with (' + r1.fromVal + ' - ' + r1.toVal + ') in row ' + (r1.index + 1));

                    if ($changedInput) {
                        $changedInput.val('');
                        $changedInput.addClass('is-invalid');
                    }
                    calculateHours();
                    return false;
                }
            }
        }

        calculateHours();
        return true;
    }

    function calculateHours() {
        $('#breakdownItemsBody tr').each(function () {
            var $row = $(this);
            var fromTime = $row.find('.from-time').val();
            var toTime = $row.find('.to-time').val();
            var $hoursInput = $row.find('.hours-input');

            if (fromTime && toTime) {
                var fromMin = timeToMinutes(fromTime);
                var toMin = timeToMinutes(toTime);

                if (toMin > fromMin) {
                    var diffMinutes = toMin - fromMin;
                    var diffHours = diffMinutes / 60;
                    $hoursInput.val(diffHours.toFixed(2));
                } else {
                    $hoursInput.val('');
                }
            } else {
                $hoursInput.val('');
            }
        });
    }

    $(document).off('change.mps_breakdown', '.from-time, .to-time').on('change.mps_breakdown', '.from-time, .to-time', function () {
        validateBreakdownTimes($(this));
    });

    $(document).off('click.mps_copy', '.copythis').on('click.mps_copy', '.copythis', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        var $row = $(this).closest('tr');
        var clone = $row.clone();

        clone.find('.select2-container').remove();
        clone.find('select')
            .removeClass('select2-hidden-accessible')
            .removeAttr('data-select2-id')
            .removeAttr('tabindex')
            .removeAttr('aria-hidden')
            .val('');

        clone.find('*').removeAttr('data-select2-id');

        clone.find('input[type="time"]').val('').removeClass('is-invalid');
        clone.find('input[type="number"]').val('');
        clone.find('textarea').val('');

        $row.closest('tbody').append(clone);

        calculateHours();
    });

    $(document).off('click.mps_remove', '.removethis').on('click.mps_remove', '.removethis', function (e) {
        e.preventDefault();
        if ($(this).closest('tbody').find('tr').length > 1) {
            $(this).closest('tr').remove();
            calculateHours();
        } else {
            var $tr = $(this).closest('tr');
            $tr.find('select').val('');
            $tr.find('input').val('').removeClass('is-invalid');
            $tr.find('textarea').val('');
            calculateHours();
        }
    });

    function addMachineTimeRow(machineId) {
        const container = $(`#machine_time_table_${machineId}`);
        const slotIdx = container.find('.time-slot-block').length;
        const slotHtml = `
            <div class="time-slot-block mb-2 p-2" data-slot-index="${slotIdx}" style="border: 1px solid #e2e8f0; border-radius: 5px; background: #fafbfc;">
                <div class="d-flex flex-wrap align-items-center justify-content-between p-1 mb-1" style="background: #f1f4f8; border-radius: 4px; gap: 8px;">
                    <div class="d-flex align-items-center flex-wrap" style="gap: 12px;">
                        <div class="d-flex align-items-center" style="gap: 5px;">
                            <label class="mb-0" style="font-size: 11px; font-weight: 600; color: #4a5568;">Start Time:</label>
                            <input type="time" name="machine_start_time[${machineId}][]" class="form-control form-control-sm start-time" onchange="handleSlotTimeChange(this, ${machineId})" style="font-size: 11px; height: 28px; width: 130px; background: #fff;">
                        </div>
                        <div class="d-flex align-items-center" style="gap: 5px;">
                            <label class="mb-0" style="font-size: 11px; font-weight: 600; color: #4a5568;">End Time:</label>
                            <input type="time" name="machine_end_time[${machineId}][]" class="form-control form-control-sm end-time" onchange="handleSlotTimeChange(this, ${machineId})" style="font-size: 11px; height: 28px; width: 130px; background: #fff;">
                        </div>
                        <div class="d-flex align-items-center" style="gap: 5px;">
                            <label class="mb-0" style="font-size: 11px; font-weight: 600; color: #4a5568;">Duration:</label>
                            <input type="text" class="form-control form-control-sm duration-display" readonly style="background: #fff; font-weight: bold; font-size: 11px; text-align: center; height: 28px; width: 90px; color: #2b6cb0;">
                        </div>
                    </div>
                    <div class="d-flex align-items-center" style="gap: 8px;">
                        <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" onclick="addSlotBreakdownRow(${machineId}, this)" style="font-size: 11px; height: 26px;">
                            Inner Breakdown
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-danger py-1 px-2" onclick="removeMachineTimeRow(this, ${machineId})" title="Remove Time Slot" style="font-size: 11px; height: 26px;">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Child Breakdowns Section -->
                <div class="slot-breakdowns-container px-1">
                    <div class="table-responsive slot-breakdown-table-wrapper" style="display: none;">
                        <table class="table table-sm table-bordered mb-1" style="font-size: 11px; background: #fff;">
                            <thead style="background: #f1f5f9; color: #475569;">
                                <tr>
                                    <th style="width: 18%; font-weight: 600; padding: 4px 6px;">Breakdown From</th>
                                    <th style="width: 18%; font-weight: 600; padding: 4px 6px;">Breakdown To</th>
                                    <th style="width: 12%; font-weight: 600; padding: 4px 6px; text-align: center;">Hours</th>
                                    <th style="width: 46%; font-weight: 600; padding: 4px 6px;">Remarks</th>
                                    <th style="width: 6%; font-weight: 600; padding: 4px 6px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody class="slot-breakdown-tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        container.append(slotHtml);
        reindexMachineTimeSlots(machineId);
        calculateMachineTime(machineId);
    }

    function removeMachineTimeRow(btn, machineId) {
        $(btn).closest('.time-slot-block').remove();
        reindexMachineTimeSlots(machineId);
        calculateMachineTime(machineId);
    }

    function reindexMachineTimeSlots(machineId) {
        $(`#machine_time_table_${machineId} .time-slot-block`).each(function(slotIdx) {
            $(this).attr('data-slot-index', slotIdx);
            $(this).find('.slot-breakdown-from').attr('name', `slot_breakdown_from[${machineId}][${slotIdx}][]`);
            $(this).find('.slot-breakdown-to').attr('name', `slot_breakdown_to[${machineId}][${slotIdx}][]`);
            $(this).find('.slot-breakdown-hours').attr('name', `slot_breakdown_hours[${machineId}][${slotIdx}][]`);
            $(this).find('.slot-breakdown-remarks').attr('name', `slot_breakdown_remarks[${machineId}][${slotIdx}][]`);
        });
    }

    function addSlotBreakdownRow(machineId, btn) {
        const $slotBlock = $(btn).closest('.time-slot-block');
        const slotIdx = $slotBlock.data('slot-index') !== undefined ? $slotBlock.data('slot-index') : $slotBlock.index();
        const rowHtml = renderSlotBreakdownRowHtml(machineId, slotIdx, {});
        const $tbody = $slotBlock.find('.slot-breakdown-tbody');
        $tbody.append(rowHtml);
        $slotBlock.find('.slot-breakdown-table-wrapper').show();
    }

    function removeSlotBreakdownRow(btn, machineId) {
        const $slotBlock = $(btn).closest('.time-slot-block');
        const $tbody = $(btn).closest('.slot-breakdown-tbody');
        $(btn).closest('.slot-breakdown-row').remove();
        if ($tbody.find('.slot-breakdown-row').length === 0) {
            $slotBlock.find('.slot-breakdown-table-wrapper').hide();
        }
        validateAllSlotBreakdowns($slotBlock, machineId);
    }

    function handleSlotTimeChange(input, machineId) {
        calculateMachineTime(machineId);
        const $slotBlock = $(input).closest('.time-slot-block');
        validateAllSlotBreakdowns($slotBlock, machineId);
    }

    function validateAndCalculateBreakdown(input, machineId) {
        const $slotBlock = $(input).closest('.time-slot-block');
        validateAllSlotBreakdowns($slotBlock, machineId, $(input));
    }

    function validateAllSlotBreakdowns($slotBlock = null, machineId = null, $changedInput = null) {
        if (!$slotBlock || $slotBlock.length === 0) {
            let allValid = true;
            $('.time-slot-block').each(function() {
                const $card = $(this).closest('.machine-card');
                if ($card.length && !$card.find('.machine-toggle').is(':checked')) {
                    return; // Skip inactive machines
                }
                if (!validateAllSlotBreakdowns($(this), null, null)) {
                    allValid = false;
                }
            });
            return allValid;
        }

        const parentStart = $slotBlock.find('.start-time').val();
        const parentEnd = $slotBlock.find('.end-time').val();

        let pStartMin = timeToMinutes(parentStart);
        let pEndMin = timeToMinutes(parentEnd);

        if (pStartMin !== null && pEndMin !== null) {
            if (pEndMin < pStartMin) {
                pEndMin += 1440;
            }
        }

        let rows = [];
        let hasError = false;

        $slotBlock.find('.slot-breakdown-row').each(function(index) {
            const $row = $(this);
            const $from = $row.find('.slot-breakdown-from');
            const $to = $row.find('.slot-breakdown-to');
            const $hours = $row.find('.slot-breakdown-hours');

            $from.removeClass('is-invalid');
            $to.removeClass('is-invalid');

            const fromVal = $from.val();
            const toVal = $to.val();

            if (fromVal && toVal) {
                let fromMin = timeToMinutes(fromVal);
                let toMin = timeToMinutes(toVal);

                if (pStartMin !== null && pEndMin !== null && pEndMin > 1440) {
                    if (fromMin < (pStartMin % 1440) && fromMin < 720) {
                        fromMin += 1440;
                    }
                    if (toMin < (pStartMin % 1440) && toMin < 720) {
                        toMin += 1440;
                    }
                }

                if (toMin <= fromMin) {
                    $to.addClass('is-invalid');
                    toastr.error(`Breakdown End Time (${toVal}) must be after Start Time (${fromVal})`);
                    if ($changedInput && $changedInput.is($to)) {
                        $to.val('');
                    }
                    $hours.val('');
                    hasError = true;
                    return;
                }

                if (pStartMin !== null && pEndMin !== null) {
                    if (fromMin < pStartMin || toMin > pEndMin) {
                        $from.addClass('is-invalid');
                        $to.addClass('is-invalid');
                        toastr.warning(`Breakdown time (${fromVal} - ${toVal}) must be within the slot time (${parentStart} - ${parentEnd})`);
                        if ($changedInput && ($changedInput.is($from) || $changedInput.is($to))) {
                            $changedInput.val('');
                            $hours.val('');
                        }
                        hasError = true;
                        return;
                    }
                }

                const diffMins = toMin - fromMin;
                $hours.val((diffMins / 60).toFixed(2));

                rows.push({
                    index: index,
                    fromMin: fromMin,
                    toMin: toMin,
                    fromVal: fromVal,
                    toVal: toVal,
                    $from: $from,
                    $to: $to
                });
            } else {
                $hours.val('');
            }
        });

        if (hasError) return false;

        for (let i = 0; i < rows.length; i++) {
            for (let j = i + 1; j < rows.length; j++) {
                let r1 = rows[i];
                let r2 = rows[j];
                if (Math.max(r1.fromMin, r2.fromMin) < Math.min(r1.toMin, r2.toMin)) {
                    r1.$from.addClass('is-invalid');
                    r1.$to.addClass('is-invalid');
                    r2.$from.addClass('is-invalid');
                    r2.$to.addClass('is-invalid');
                    toastr.error(`Breakdown (${r2.fromVal} - ${r2.toVal}) overlaps with (${r1.fromVal} - ${r1.toVal})`);
                    if ($changedInput) {
                        $changedInput.val('');
                        $changedInput.addClass('is-invalid');
                    }
                    return false;
                }
            }
        }

        return true;
    }

    function calculateMachineTime(machineId) {
        let totalMinutes = 0;
        $(`#machine_time_table_${machineId} .time-slot-block`).each(function() {
            const startTime = $(this).find('.start-time').val();
            const endTime = $(this).find('.end-time').val();
            let durationInput = $(this).find('.duration-display');

            if (startTime && endTime) {
                const start = new Date(`1970-01-01T${startTime}:00`);
                let end = new Date(`1970-01-01T${endTime}:00`);
                if (end < start) {
                    end.setDate(end.getDate() + 1);
                }
                const diffMs = end - start;
                const diffMins = Math.floor(diffMs / 60000);
                totalMinutes += diffMins;
                
                const hours = Math.floor(diffMins / 60);
                const mins = diffMins % 60;
                durationInput.val(`${hours}h ${mins}m`);
            } else {
                durationInput.val('');
            }
        });

        const grandTotalHours = Math.floor(totalMinutes / 60);
        const grandTotalMins = totalMinutes % 60;
        $(`#machine_card_${machineId}`).find('.grand-total-display').text(`${grandTotalHours}h ${grandTotalMins}m`);
    }

    function updateMachineCardStyle(checkbox, machineId) {
        const isChecked = $(checkbox).is(':checked');
        const card = $(`#machine_card_${machineId}`);
        const header = $(`#machine_card_header_${machineId}`);
        const statusLabel = $(`#machine_status_${machineId}`);
        const cardBody = card.find('.machine-card-body');
        const addSlotBtn = card.find('button[onclick*="addMachineTimeRow"]');

        if (isChecked) {
            card.css({
                'border-color': '#93c3f2',
                'opacity': '1'
            });
            header.css({
                'background': '#e8f3fc',
                'border-bottom': '1px solid #93c3f2'
            });
            statusLabel.text('Active').css('color', '#007bff');

            // Enable Add Slot button
            addSlotBtn.prop('disabled', false).css({
                'opacity': '1',
                'pointer-events': 'auto',
                'cursor': 'pointer'
            });

            // Enable inputs & buttons inside machine card body
            cardBody.css({
                'opacity': '1',
                'pointer-events': 'auto'
            });
            cardBody.find('input:not([type="hidden"])').prop('disabled', false);
            cardBody.find('input:not(.duration-display):not(.slot-breakdown-hours):not([type="hidden"])').prop('readonly', false).css({
                'background': '#fff',
                'cursor': 'text'
            });
            cardBody.find('button').prop('disabled', false).css({
                'opacity': '1',
                'pointer-events': 'auto',
                'cursor': 'pointer'
            });
        } else {
            card.css({
                'border-color': '#e2e8f0',
                'opacity': '0.75'
            });
            header.css({
                'background': '#f8f9fa',
                'border-bottom': '1px solid #e2e8f0'
            });
            statusLabel.text('Inactive').css('color', '#6c757d');

            // Disable Add Slot button
            addSlotBtn.prop('disabled', true).css({
                'opacity': '0.5',
                'pointer-events': 'none',
                'cursor': 'not-allowed'
            });

            // Make columns disabled & readonly & buttons disabled inside machine card body
            cardBody.css({
                'opacity': '0.6',
                'pointer-events': 'none'
            });
            cardBody.find('input:not([type="hidden"])').prop('disabled', true).prop('readonly', true).css({
                'background': '#f1f5f9',
                'cursor': 'not-allowed'
            });
            cardBody.find('button').prop('disabled', true).css({
                'opacity': '0.5',
                'pointer-events': 'none',
                'cursor': 'not-allowed'
            });
        }
    }
</script>