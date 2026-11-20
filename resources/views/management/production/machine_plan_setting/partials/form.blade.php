{{--
    Machine Plan Setting form (create + edit), same look as Manage Logs:
    one tab per machine (ON/OFF switch, time slot, inner breakdowns) and the Overall Plant Breakdown.
    Create: several job orders -> one plan per job order with the same machines, times and breakdowns.
    Edit: one job order.
    Expects: $companyLocations, $plants, $breakdownTypes; optional $plan (edit), $selectedLocationId
--}}
@php
    $plan = $plan ?? null;
    $isEdit = (bool) $plan;
    $locationId = $isEdit ? ($selectedLocationId ?? null) : optional($companyLocations->first())->id;
    $isLocked = $isEdit && $plan->isLocked();
    $plantOptions = $plants->map(fn ($p) => [
        'id' => $p->id,
        'location_id' => $p->company_location_id,
        'text' => trim(($p->arrivalLocation->name ?? '') . ' - ' . $p->name, ' -'),
    ])->values();
@endphp

<form action="{{ $isEdit ? route('machine-plan-setting.update', $plan->id) : route('machine-plan-setting.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" id="listRefresh" value="{{ route('get.machine-plan-setting') }}" />

    <div class="mpl">
        @if($isLocked)
            <div class="mpl-locked"><i class="ft-lock mr-1"></i> Production Voucher <strong>{{ $plan->productionVoucher->prod_no ?? '' }}</strong> of this plan is completed. Its machine times can only be changed from that voucher.</div>
        @endif

        <!-- Plan details -->
        <div class="mpl-section-title">
            <span><i class="ft-file-text mr-1"></i> Plan Details</span>
            <small>{{ $isEdit ? 'This plan belongs to one job order.' : 'Pick one or more job orders: one plan is saved for each.' }}</small>
        </div>
        <div class="mpl-card mpl-details">
            <div class="row">
                <div class="col-md-3">
                    <label class="mpl-field-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" id="date" class="form-control" value="{{ $isEdit ? $plan->date->format('Y-m-d') : date('Y-m-d') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="mpl-field-label">Location <span class="text-danger">*</span></label>
                    <select name="company_location_id" id="company_location_id" class="form-control" required>
                        <option value="">Select Location</option>
                        @foreach($companyLocations as $location)
                            <option value="{{ $location->id }}" @selected($locationId == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="mpl-field-label">{{ $isEdit ? 'Job Order' : 'Job Orders' }} <span class="text-danger">*</span></label>
                    <select name="{{ $isEdit ? 'job_order_id' : 'job_order_ids[]' }}" id="mps_job_orders" class="form-control" {{ $isEdit ? '' : 'multiple' }} required
                            data-selected='@json($isEdit ? [$plan->job_order_id] : [])'
                            data-placeholder="{{ $isEdit ? 'Select Job Order' : 'Select one or more Job Orders' }}">
                    </select>
                    @unless($isEdit)
                        <div class="mpl-hint" id="mps_job_order_hint">Job orders of the selected location.</div>
                    @endunless
                </div>
                <div class="col-md-3 mt-2">
                    <label class="mpl-field-label">Plant <span class="text-danger">*</span></label>
                    <select name="plant_id" id="plant_id" class="form-control" required data-selected="{{ $isEdit ? $plan->plant_id : '' }}">
                        <option value="">Select Plant</option>
                    </select>
                    <div class="mpl-hint" id="mps_shift_text"></div>
                </div>
                <div class="col-md-9 mt-2">
                    <label class="mpl-field-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" value="{{ $plan->remarks ?? '' }}" placeholder="Optional remarks">
                </div>
            </div>
        </div>

        <!-- Machines -->
        <div class="mpl-section-title mt-4">
            <span><i class="fa fa-cogs mr-1"></i> Machine Settings</span>
            <small>Switch on the machines that run, set their time slot and add inner breakdowns.</small>
        </div>
        <div id="mps_machines">
            <div class="mpl-empty-box"><i class="ft-cpu"></i> Select a plant to load its machines</div>
        </div>

        <!-- Overall plant breakdown -->
        <div class="mpl-section-title mt-4">
            <span><i class="ft-zap-off mr-1"></i> Overall Plant Breakdown</span>
            <small>Whole-plant stops on this date, shared by all job order plans of the plant on this date.</small>
        </div>
        <div class="mpl-plant">
            <div class="mpl-grid mpl-grid-plant mpl-grid-head">
                <span>Type</span><span>From</span><span>To</span><span>Duration</span><span>Remarks</span><span></span>
            </div>
            <div id="mpl_plant_rows"></div>
            <div class="mpl-none" id="mpl_plant_none">No plant breakdown on this date.</div>
            <button type="button" class="mpl-add-btn" id="mpl_add_plant"><i class="ft-plus"></i> Add Plant Breakdown</button>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            @unless($isLocked)
                <button type="submit" class="btn btn-primary submitbutton"><i class="ft-save mr-1"></i> {{ $isEdit ? 'Update Plan' : 'Save Plan' }}</button>
            @endunless
        </div>
    </div>
</form>

<style>
    .mpl { padding: 0 2px; }
    .mpl-locked { background: #fff4e5; border: 1px solid #f5d29a; color: #8a5a00; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; font-size: 13px; }
    .mpl-section-title { display: flex; flex-direction: column; margin-bottom: 10px; }
    .mpl-section-title span { font-weight: 700; font-size: 14px; color: #1f2d3d; }
    .mpl-section-title small { color: #8492a6; font-size: 12px; }
    .mpl-card, .mpl-plant { border: 1px solid #e5eaf1; border-radius: 10px; padding: 14px; background: #fff; }
    .mpl-field-label { display: block; margin: 0 0 4px; font-size: 10.5px; font-weight: 600; color: #7b8794; text-transform: uppercase; letter-spacing: .4px; }
    .mpl-details .form-control { height: 38px; }
    .mpl-hint { font-size: 12px; color: #8492a6; margin-top: 4px; }

    /* Tabs */
    .mpl-tabs { gap: 6px; border-bottom: 2px solid #e5eaf1; margin-bottom: 12px; flex-wrap: wrap; }
    .mpl-tabs .nav-link { border: 0; border-bottom: 2px solid transparent; margin-bottom: -2px; color: #64748b; font-weight: 600; padding: 8px 14px; display: flex; align-items: center; gap: 6px; border-radius: 0; }
    .mpl-tabs .nav-link:hover { color: #3466c2; }
    .mpl-tabs .nav-link.active { color: #3466c2; border-bottom-color: #3466c2; background: transparent; }
    .mpl-dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; display: inline-block; }
    .mpl-dot.is-on { background: #22c55e; box-shadow: 0 0 0 3px rgba(34, 197, 94, .15); }
    .mpl-tab-count { background: #f59e0b; color: #fff; font-size: 11px; font-weight: 700; border-radius: 10px; min-width: 20px; height: 20px; padding: 0 6px; display: inline-flex; align-items: center; justify-content: center; }

    /* Machine switch bar */
    .mpl-machine-bar { display: flex; align-items: center; justify-content: space-between; gap: 10px; background: #f8fafc; border: 1px solid #e5eaf1; border-radius: 10px; padding: 10px 14px; margin-bottom: 10px; }
    .mpl-machine-bar.is-on { background: #eef6fd; border-color: #cfe3f7; }
    .mpl-machine-name { font-weight: 700; color: #1f2d3d; }
    .mpl-machine-state { font-size: 12px; color: #64748b; }
    .mpl-switch { display: flex; align-items: center; gap: 8px; margin: 0; cursor: pointer; font-weight: 600; font-size: 12px; color: #64748b; }
    .mpl-machine-bar.is-on .mpl-switch { color: #007bff; }
    .mpl-machine-body.is-off { opacity: .45; pointer-events: none; }

    /* Slot card */
    .mpl-slot { border: 1px solid #e5eaf1; border-radius: 10px; padding: 14px; margin-bottom: 12px; background: #fff; }
    .mpl-slot-head { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; padding-bottom: 12px; margin-bottom: 10px; border-bottom: 1px dashed #e5eaf1; }
    .mpl-slot-title { font-weight: 700; color: #1f2d3d; align-self: center; min-width: 80px; }
    .mpl-slot-time { display: flex; align-items: flex-end; gap: 8px; }
    .mpl-field label { display: block; margin: 0 0 3px; font-size: 10.5px; font-weight: 600; color: #7b8794; text-transform: uppercase; letter-spacing: .4px; }
    .mpl-field .form-control { height: 36px; width: 150px; }
    .mpl-arrow { color: #94a3b8; padding-bottom: 8px; }
    .mpl-chips { display: flex; gap: 6px; flex-wrap: wrap; margin-left: auto; align-self: center; align-items: center; }
    .mpl-chip { font-size: 12px; border-radius: 14px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
    .mpl-chip b { font-weight: 700; }
    .mpl-chip-total { background: #eef4ff; color: #3466c2; }
    .mpl-chip-down { background: #fff4e5; color: #b45309; }
    .mpl-chip-run { background: #e8f7ee; color: #1e7e44; }
    .mpl-slot-remove { border: 0; background: none; color: #dc3545; padding: 2px 6px; }

    /* Breakdown grids */
    .mpl-grid { display: grid; gap: 8px; align-items: center; }
    .mpl-grid-machine { grid-template-columns: 150px 150px 80px minmax(0, 1fr) 34px; }
    .mpl-grid-plant { grid-template-columns: minmax(130px, 200px) 150px 150px 80px minmax(0, 1fr) 34px; }
    .mpl-grid-head { font-size: 10.5px; font-weight: 600; color: #7b8794; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 4px; }
    .mpl-row { margin-bottom: 8px; }
    .mpl-row .form-control { height: 36px; }
    .mpl-dur { font-size: 12.5px; font-weight: 700; color: #b45309; }
    .mpl-remove { width: 34px; height: 34px; border-radius: 8px; border: 1px solid #fde2e2; background: #fff5f5; color: #dc3545; display: inline-flex; align-items: center; justify-content: center; }
    .mpl-remove:hover { background: #dc3545; color: #fff; }
    .mpl-none { color: #94a3b8; font-size: 12.5px; font-style: italic; padding: 4px 0 8px; }
    .mpl-add-btn { width: 100%; border: 1px dashed #b6c8e6; background: #f7faff; color: #3466c2; border-radius: 8px; padding: 7px; font-size: 13px; font-weight: 600; }
    .mpl-add-btn:hover { background: #eef4ff; border-color: #3466c2; }
    .mpl-empty-box { text-align: center; color: #94a3b8; border: 1px dashed #d5dde8; border-radius: 10px; padding: 20px; }
    .mpl .is-invalid { border-color: #dc3545 !important; }

    @media (max-width: 991px) {
        .mpl-grid-head { display: none !important; }
        .mpl-grid-machine, .mpl-grid-plant { grid-template-columns: 1fr 1fr; }
        .mpl-chips { margin-left: 0; }
    }

    .layout-dark .mpl-card, .layout-dark .mpl-slot, .layout-dark .mpl-plant { background: #1a1a1a; border-color: #404040; }
    .layout-dark .mpl-section-title span, .layout-dark .mpl-slot-title, .layout-dark .mpl-machine-name { color: #e2e8f0; }
    .layout-dark .mpl-tabs, .layout-dark .mpl-slot-head { border-color: #404040; }
    .layout-dark .mpl-machine-bar { background: #222; border-color: #404040; }
    .layout-dark .mpl-machine-bar.is-on { background: #13263a; border-color: #2c4a6b; }
    .layout-dark .mpl-add-btn { background: #13263a; border-color: #2c4a6b; color: #8ab4ff; }
    .layout-dark .mpl-remove { background: #2a1515; border-color: #5c2626; }
</style>

<script>
    (function () {
        var plants = @json($plantOptions);
        var breakdownTypes = @json($breakdownTypes->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values());
        var isEdit = {{ $isEdit ? 'true' : 'false' }};
        var planId = {{ $isEdit ? (int) $plan->id : 'null' }};
        var originalPlantId = {{ $isEdit ? (int) $plan->plant_id : 'null' }};
        var isLocked = {{ $isLocked ? 'true' : 'false' }};
        var $form = $('#ajaxSubmit');
        var $location = $('#company_location_id'), $plant = $('#plant_id'), $jo = $('#mps_job_orders'), $machines = $('#mps_machines');

        function esc(t) { return $('<div>').text(t == null ? '' : t).html(); }
        function toMin(t) { if (!t) return null; var p = t.split(':'); return +p[0] * 60 + +p[1]; }
        function minutes(start, end) {
            var s = toMin(start), e = toMin(end);
            if (s === null || e === null) return 0;
            return e - s < 0 ? e - s + 1440 : e - s;
        }
        function fmt(min) { return Math.floor(min / 60) + 'h ' + (min % 60) + 'm'; }

        // ---------- Location -> plants + job orders ----------
        function fillPlants(selectedId) {
            var locId = $location.val();
            $plant.empty().append('<option value="">' + (locId ? 'Select Plant' : 'Select Location First') + '</option>');
            plants.forEach(function (p) {
                if (String(p.location_id) === String(locId)) {
                    $plant.append($('<option>', { value: p.id, text: p.text, selected: String(p.id) === String(selectedId) }));
                }
            });
            if (!selectedId && $plant.find('option[value!=""]').length === 1) $plant.val($plant.find('option[value!=""]').val());
            $plant.trigger('change.select2');
        }

        function loadJobOrders() {
            var locId = $location.val();
            var keep = [].concat($jo.val() || []).filter(Boolean);
            if (!keep.length) keep = $jo.data('selected') || [];
            $jo.empty();
            if (!locId) { $jo.trigger('change'); return; }
            $.get('{{ route('machine-plan-setting.job-orders-by-location') }}', { company_location_id: locId }, function (res) {
                if (isEdit) $jo.append('<option value="">Select Job Order</option>');
                (res.jobOrders || []).forEach(function (j) {
                    $jo.append($('<option>', { value: j.id, text: j.text, selected: keep.map(String).indexOf(String(j.id)) !== -1 }));
                });
                $jo.trigger('change');
            });
        }

        function updateJobOrderHint() {
            if (isEdit) return;
            var count = ($jo.val() || []).length;
            $('#mps_job_order_hint').html(count > 1
                ? '<i class="ft-info text-primary"></i> <strong>' + count + ' plans</strong> will be saved, one for each job order, with the same machines, times and breakdowns.'
                : 'Job orders of the selected location.');
        }

        // ---------- Machines (one tab each) ----------
        function breakdownRow(machineId, slotIdx, bd) {
            bd = bd || {};
            return '<div class="mpl-grid mpl-grid-machine mpl-row">' +
                '<input type="time" class="form-control mpl-from" name="slot_breakdown_from[' + machineId + '][' + slotIdx + '][]" value="' + (bd.from || '') + '">' +
                '<input type="time" class="form-control mpl-to" name="slot_breakdown_to[' + machineId + '][' + slotIdx + '][]" value="' + (bd.to || '') + '">' +
                '<span class="mpl-dur"></span>' +
                '<input type="text" class="form-control mpl-remarks" name="slot_breakdown_remarks[' + machineId + '][' + slotIdx + '][]" value="' + esc(bd.remarks) + '" placeholder="Reason / remarks">' +
                '<button type="button" class="mpl-remove" title="Remove"><i class="ft-trash-2"></i></button>' +
                '</div>';
        }

        function slotHtml(machineId, slotIdx, slot, slotCount) {
            var rows = (slot.breakdowns || []).map(function (bd) { return breakdownRow(machineId, slotIdx, bd); }).join('');
            return '<div class="mpl-slot" data-machine="' + machineId + '">' +
                '<div class="mpl-slot-head">' +
                    '<div class="mpl-slot-title">Time Slot ' + (slotCount > 1 ? (slotIdx + 1) : '') + '</div>' +
                    '<div class="mpl-slot-time">' +
                        '<div class="mpl-field"><label>Start</label><input type="time" class="form-control mpl-slot-start" name="machine_start_time[' + machineId + '][]" value="' + (slot.start_time || '') + '"></div>' +
                        '<span class="mpl-arrow"><i class="ft-arrow-right"></i></span>' +
                        '<div class="mpl-field"><label>End</label><input type="time" class="form-control mpl-slot-end" name="machine_end_time[' + machineId + '][]" value="' + (slot.end_time || '') + '"></div>' +
                    '</div>' +
                    '<div class="mpl-chips">' +
                        '<span class="mpl-chip mpl-chip-total" title="Slot time"><i class="ft-clock"></i> <b class="mpl-total">0h 0m</b></span>' +
                        '<span class="mpl-chip mpl-chip-down" title="Breakdown time"><i class="ft-alert-triangle"></i> <b class="mpl-down">0h 0m</b></span>' +
                        '<span class="mpl-chip mpl-chip-run" title="Running time (slot minus breakdowns)"><i class="ft-play"></i> <b class="mpl-run">0h 0m</b></span>' +
                        (slotCount > 1 ? '<button type="button" class="mpl-slot-remove" title="Remove this slot"><i class="ft-x"></i></button>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="mpl-grid mpl-grid-machine mpl-grid-head"><span>From</span><span>To</span><span>Duration</span><span>Remarks</span><span></span></div>' +
                '<div class="mpl-rows">' + rows + '</div>' +
                '<div class="mpl-none">No inner breakdown in this slot.</div>' +
                '<button type="button" class="mpl-add-btn mpl-add"><i class="ft-plus"></i> Add Inner Breakdown</button>' +
            '</div>';
        }

        function renderMachines(list) {
            var tabs = '', panes = '';
            list.forEach(function (m, i) {
                var slots = (m.slots && m.slots.length) ? m.slots : [{}];
                tabs += '<li class="nav-item"><a class="nav-link' + (i === 0 ? ' active' : '') + '" data-toggle="tab" href="#mps_tab_' + m.id + '" role="tab">' +
                    '<span class="mpl-dot' + (m.is_enabled ? ' is-on' : '') + '"></span> ' + esc(m.name) +
                    ' <span class="mpl-tab-count d-none"></span></a></li>';
                panes += '<div class="tab-pane fade' + (i === 0 ? ' show active' : '') + '" id="mps_tab_' + m.id + '" role="tabpanel" data-machine="' + m.id + '">' +
                    '<input type="hidden" name="all_machine_ids[]" value="' + m.id + '">' +
                    '<div class="mpl-machine-bar' + (m.is_enabled ? ' is-on' : '') + '">' +
                        '<div><div class="mpl-machine-name"><i class="fa fa-cogs text-primary mr-1"></i>' + esc(m.name) + '</div>' +
                        '<div class="mpl-machine-state">Total: <b class="mpl-machine-total">0h 0m</b></div></div>' +
                        '<label class="mpl-switch" for="machine_' + m.id + '"><span class="mpl-switch-text">' + (m.is_enabled ? 'Running' : 'Off') + '</span>' +
                            '<span class="custom-control custom-switch mb-0"><input type="checkbox" class="custom-control-input mpl-toggle" id="machine_' + m.id + '" name="production_machine_id[]" value="' + m.id + '"' + (m.is_enabled ? ' checked' : '') + '>' +
                            '<span class="custom-control-label"></span></span></label>' +
                    '</div>' +
                    '<div class="mpl-machine-body">' + slots.map(function (s, si) { return slotHtml(m.id, si, s, slots.length); }).join('') + '</div>' +
                '</div>';
            });
            $machines.html('<ul class="nav mpl-tabs" role="tablist">' + tabs + '</ul><div class="tab-content">' + panes + '</div>');
            $machines.find('.tab-pane').each(function () { applyMachineState($(this)); refreshMachine($(this)); });
        }

        function loadMachines() {
            var plantId = $plant.val();
            if (!plantId) {
                $machines.html('<div class="mpl-empty-box"><i class="ft-cpu"></i> Select a plant to load its machines</div>');
                $('#mps_shift_text').text('');
                return;
            }
            $machines.html('<div class="mpl-empty-box"><i class="fa fa-spinner fa-spin"></i> Loading machines...</div>');
            var params = { plant_id: plantId };
            if (isEdit && String(plantId) === String(originalPlantId)) params.plan_id = planId;

            $.get('{{ route('getMachinesByPlant') }}', params, function (res) {
                $('#mps_shift_text').html(res.shift ? '<i class="ft-clock"></i> Plant shift: ' + res.shift.start_time + ' - ' + res.shift.end_time : '');
                if (!res.machines || !res.machines.length) {
                    $machines.html('<div class="mpl-empty-box">No active machines found for this plant</div>');
                    return;
                }
                renderMachines(res.machines);
                if (isLocked) lockForm();
            }).fail(function () { toastr.error('Error loading machines'); });
        }

        // OFF machine: its inputs are disabled, so only ON machines send times and breakdowns
        function applyMachineState($pane) {
            var on = $pane.find('.mpl-toggle').is(':checked');
            $pane.find('.mpl-machine-bar').toggleClass('is-on', on).find('.mpl-switch-text').text(on ? 'Running' : 'Off');
            $pane.find('.mpl-machine-body').toggleClass('is-off', !on).find('input, button').prop('disabled', !on);
            $('a[href="#' + $pane.attr('id') + '"] .mpl-dot').toggleClass('is-on', on);
        }

        function refreshSlot($slot) {
            var total = minutes($slot.find('.mpl-slot-start').val(), $slot.find('.mpl-slot-end').val()), down = 0;
            $slot.find('.mpl-row').each(function () {
                var m = minutes($(this).find('.mpl-from').val(), $(this).find('.mpl-to').val());
                $(this).find('.mpl-dur').text(m ? fmt(m) : '');
                down += m;
            });
            $slot.find('.mpl-total').text(fmt(total));
            $slot.find('.mpl-down').text(fmt(down));
            $slot.find('.mpl-run').text(fmt(Math.max(total - down, 0)));
            var has = $slot.find('.mpl-row').length > 0;
            $slot.find('.mpl-none').toggle(!has);
            $slot.find('.mpl-grid-head').toggle(has);
        }

        function refreshMachine($pane) {
            var total = 0, count = 0;
            $pane.find('.mpl-slot').each(function () {
                refreshSlot($(this));
                total += minutes($(this).find('.mpl-slot-start').val(), $(this).find('.mpl-slot-end').val());
                count += $(this).find('.mpl-row').length;
            });
            $pane.find('.mpl-machine-total').text(fmt(total));
            $('a[href="#' + $pane.attr('id') + '"] .mpl-tab-count').text(count).toggleClass('d-none', !count);
        }

        // Slot indexes go into the breakdown field names, so renumber after a slot is removed
        function reindexSlots($pane) {
            var machineId = $pane.data('machine');
            $pane.find('.mpl-slot').each(function (idx) {
                $(this).find('.mpl-from').attr('name', 'slot_breakdown_from[' + machineId + '][' + idx + '][]');
                $(this).find('.mpl-to').attr('name', 'slot_breakdown_to[' + machineId + '][' + idx + '][]');
                $(this).find('.mpl-remarks').attr('name', 'slot_breakdown_remarks[' + machineId + '][' + idx + '][]');
            });
        }

        // Inner breakdowns: inside the slot, To after From, no overlap (a slot may cross midnight)
        function validateSlot($slot, $changed) {
            var pS = toMin($slot.find('.mpl-slot-start').val()), pE = toMin($slot.find('.mpl-slot-end').val());
            if (pS !== null && pE !== null && pE < pS) pE += 1440;
            var taken = [];
            $slot.find('.mpl-row').each(function () {
                var $f = $(this).find('.mpl-from'), $t = $(this).find('.mpl-to');
                $f.removeClass('is-invalid'); $t.removeClass('is-invalid');
                var f = toMin($f.val()), t = toMin($t.val());
                if (f === null || t === null) return;
                if (pS !== null && pE > 1440) { if (f < pS) f += 1440; if (t < pS) t += 1440; }
                var msg = null;
                if (t <= f) msg = 'Breakdown To must be after From.';
                else if (pS !== null && pE !== null && (f < pS || t > pE)) msg = 'Breakdown (' + $f.val() + ' - ' + $t.val() + ') must be within the slot time.';
                else taken.forEach(function (r) { if (Math.max(f, r[0]) < Math.min(t, r[1])) msg = 'Breakdown (' + $f.val() + ' - ' + $t.val() + ') overlaps another breakdown.'; });
                if (msg) {
                    $f.addClass('is-invalid'); $t.addClass('is-invalid');
                    if ($changed && ($changed.is($f) || $changed.is($t))) toastr.warning(msg);
                } else {
                    taken.push([f, t]);
                }
            });
        }

        // ---------- Plant breakdown ----------
        var plantCounter = 0;
        function plantRow(item) {
            item = item || {};
            var opts = '<option value="">Select Type</option>' + breakdownTypes.map(function (t) {
                return '<option value="' + t.id + '"' + (String(item.breakdown_type_id) === String(t.id) ? ' selected' : '') + '>' + esc(t.name) + '</option>';
            }).join('');
            plantCounter++;
            return '<div class="mpl-grid mpl-grid-plant mpl-row">' +
                '<select class="form-control" name="breakdown_type_id[]">' + opts + '</select>' +
                '<input type="time" class="form-control mpl-from" name="from[]" value="' + (item.from ? item.from.substring(0, 5) : '') + '">' +
                '<input type="time" class="form-control mpl-to" name="to[]" value="' + (item.to ? item.to.substring(0, 5) : '') + '">' +
                '<span class="mpl-dur"></span>' +
                '<input type="text" class="form-control" name="breakdown_remarks[]" value="' + esc(item.remarks) + '" placeholder="Remarks">' +
                '<button type="button" class="mpl-remove" title="Remove"><i class="ft-trash-2"></i></button>' +
                '</div>';
        }

        function refreshPlant() {
            var $rows = $('#mpl_plant_rows .mpl-row');
            $rows.each(function () {
                var m = minutes($(this).find('.mpl-from').val(), $(this).find('.mpl-to').val());
                $(this).find('.mpl-dur').text(m ? fmt(m) : '');
            });
            $('#mpl_plant_none').toggle(!$rows.length);
            $('.mpl-plant .mpl-grid-head').toggle($rows.length > 0);
        }

        function validatePlant($changed) {
            var taken = [];
            $('#mpl_plant_rows .mpl-row').each(function () {
                var $f = $(this).find('.mpl-from'), $t = $(this).find('.mpl-to');
                $f.removeClass('is-invalid'); $t.removeClass('is-invalid');
                var f = toMin($f.val()), t = toMin($t.val());
                if (f === null || t === null) return;
                var msg = null;
                if (t <= f) msg = 'Plant breakdown To must be after From.';
                else taken.forEach(function (r) { if (Math.max(f, r[0]) < Math.min(t, r[1])) msg = 'Plant breakdown (' + $f.val() + ' - ' + $t.val() + ') overlaps another row.'; });
                if (msg) {
                    $f.addClass('is-invalid'); $t.addClass('is-invalid');
                    if ($changed && ($changed.is($f) || $changed.is($t))) toastr.warning(msg);
                } else {
                    taken.push([f, t]);
                }
            });
        }

        // The plant's breakdown for the day (shared by all plans of that plant + date)
        function loadPlantBreakdowns() {
            var date = $('#date').val(), plantId = $plant.val();
            var $rows = $('#mpl_plant_rows').empty();
            if (!date || !plantId) { refreshPlant(); return; }
            $.get('{{ route('getBreakdownsByPlantAndDate') }}', { date: date, plant_id: plantId }, function (res) {
                $rows.html((res.items || []).map(plantRow).join(''));
                refreshPlant();
                if (isLocked) lockForm();
            });
        }

        function lockForm() {
            $form.find('input:not([type="hidden"]), select').prop('disabled', true);
            $form.find('.mpl-add-btn, .mpl-remove, .mpl-slot-remove').remove();
        }

        // ---------- Events (namespaced: the drawer is opened many times) ----------
        $form.off('.mpf')
            .on('change.mpf', '.mpl-toggle', function () { applyMachineState($(this).closest('.tab-pane')); })
            .on('change.mpf input.mpf', '.mpl-slot .mpl-slot-start, .mpl-slot .mpl-slot-end, .mpl-slot .mpl-from, .mpl-slot .mpl-to', function (e) {
                var $slot = $(this).closest('.mpl-slot');
                refreshMachine($slot.closest('.tab-pane'));
                if (e.type === 'change') validateSlot($slot, $(this));
            })
            .on('click.mpf', '.mpl-slot .mpl-add', function () {
                var $slot = $(this).closest('.mpl-slot'), $pane = $slot.closest('.tab-pane');
                $slot.find('.mpl-rows').append(breakdownRow($pane.data('machine'), $pane.find('.mpl-slot').index($slot), {}));
                refreshMachine($pane);
                $slot.find('.mpl-rows .mpl-row:last .mpl-from').focus();
            })
            .on('click.mpf', '.mpl-slot .mpl-remove', function () {
                var $slot = $(this).closest('.mpl-slot');
                $(this).closest('.mpl-row').remove();
                validateSlot($slot);
                refreshMachine($slot.closest('.tab-pane'));
            })
            .on('click.mpf', '.mpl-slot-remove', function () {
                var $pane = $(this).closest('.tab-pane');
                $(this).closest('.mpl-slot').remove();
                reindexSlots($pane);
                refreshMachine($pane);
            })
            .on('click.mpf', '#mpl_add_plant', function () {
                $('#mpl_plant_rows').append(plantRow());
                refreshPlant();
            })
            .on('click.mpf', '#mpl_plant_rows .mpl-remove', function () {
                $(this).closest('.mpl-row').remove();
                validatePlant();
                refreshPlant();
            })
            .on('change.mpf input.mpf', '#mpl_plant_rows .mpl-from, #mpl_plant_rows .mpl-to', function (e) {
                refreshPlant();
                if (e.type === 'change') validatePlant($(this));
            });

        $location.off('.mpf').on('change.mpf', function () { fillPlants(null); loadJobOrders(); loadMachines(); loadPlantBreakdowns(); });
        $plant.off('.mpf').on('change.mpf', function () { loadMachines(); loadPlantBreakdowns(); });
        $('#date').off('.mpf').on('change.mpf', loadPlantBreakdowns);
        $jo.off('.mpf').on('change.mpf', updateJobOrderHint);

        // ---------- Init ----------
        $location.select2({ width: '100%' });
        $plant.select2({ width: '100%' });
        $jo.select2({ width: '100%', placeholder: $jo.data('placeholder'), closeOnSelect: isEdit });

        fillPlants($plant.data('selected'));
        loadJobOrders();
        loadMachines();
        loadPlantBreakdowns();
    })();
</script>
