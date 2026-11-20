{{-- Manage Logs: one tab per running machine; slot times and breakdowns can be corrected here --}}
@php
    $byMachine = $plan->items->groupBy('production_machine_id');
    $onMachines = $byMachine->filter(fn ($slots) => $slots->first()->is_enabled)->values();
    $offMachines = $byMachine->reject(fn ($slots) => $slots->first()->is_enabled);
    $hm = fn ($t) => $t ? substr($t, 0, 5) : '';
    $plantItems = $plantBreakdown?->items ?? collect();
@endphp

<form action="{{ route('machine-plan-setting.save-logs', $plan->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.machine-plan-setting') }}" />

    <div class="mpl">
        <!-- Plan summary -->
        <div class="mpl-summary">
            <div class="mpl-stat">
                <span class="mpl-stat-icon"><i class="ft-file-text"></i></span>
                <div>
                    <div class="mpl-label">Job Order</div>
                    <div class="mpl-value">{{ $plan->jobOrder->job_order_no ?? '--' }}</div>
                    <div class="mpl-sub">{{ $plan->jobOrder->product->name ?? '' }}</div>
                </div>
            </div>
            <div class="mpl-stat">
                <span class="mpl-stat-icon"><i class="ft-calendar"></i></span>
                <div>
                    <div class="mpl-label">Date</div>
                    <div class="mpl-value">{{ $plan->date->format('d-m-Y') }}</div>
                    <div class="mpl-sub">{{ $plan->date->format('l') }}</div>
                </div>
            </div>
            <div class="mpl-stat">
                <span class="mpl-stat-icon"><i class="ft-cpu"></i></span>
                <div>
                    <div class="mpl-label">Plant</div>
                    <div class="mpl-value">{{ $plan->plant->name ?? '--' }}</div>
                    <div class="mpl-sub">{{ $plan->companyLocation->name ?? '' }}{{ $plan->plant?->arrivalLocation ? ' / ' . $plan->plant->arrivalLocation->name : '' }}</div>
                </div>
            </div>
            <div class="mpl-stat">
                <span class="mpl-stat-icon"><i class="ft-clipboard"></i></span>
                <div>
                    <div class="mpl-label">Voucher</div>
                    <div class="mpl-value">{{ $plan->productionVoucher->prod_no ?? 'Not linked' }}</div>
                    <div class="mpl-sub">{{ $plan->user->name ?? '' }}</div>
                </div>
            </div>
            @if($plan->remarks)
                <div class="mpl-summary-remarks"><i class="ft-message-square mr-1"></i>{{ $plan->remarks }}</div>
            @endif
        </div>

        <!-- Machines: one tab each -->
        <div class="mpl-section-title">
            <span><i class="fa fa-cogs mr-1"></i> Machine Logs</span>
            <small>Correct the slot time if needed, and add breakdowns inside the slot.</small>
        </div>

        @if($onMachines->isEmpty())
            <div class="mpl-empty-box"><i class="ft-power"></i> No machine is switched on in this plan.</div>
        @else
            <ul class="nav mpl-tabs" role="tablist">
                @foreach($onMachines as $slots)
                    @php $count = $slots->sum(fn ($s) => $s->breakdowns->count()); @endphp
                    <li class="nav-item">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" data-toggle="tab" href="#mpl_tab_{{ $slots->first()->production_machine_id }}" role="tab">
                            <i class="fa fa-cog"></i> {{ $slots->first()->machine->name ?? '--' }}
                            <span class="mpl-tab-count {{ $count ? '' : 'd-none' }}">{{ $count }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach($onMachines as $slots)
                    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="mpl_tab_{{ $slots->first()->production_machine_id }}" role="tabpanel">
                        @foreach($slots as $slot)
                            <div class="mpl-slot" data-item="{{ $slot->id }}">
                                <div class="mpl-slot-head">
                                    <div class="mpl-slot-title">Time Slot {{ $slots->count() > 1 ? $loop->iteration : '' }}</div>
                                    <div class="mpl-slot-time">
                                        <div class="mpl-field">
                                            <label>Start</label>
                                            <input type="time" class="form-control mpl-slot-start" name="slots[{{ $slot->id }}][start_time]" value="{{ $hm($slot->start_time) }}" required>
                                        </div>
                                        <span class="mpl-arrow"><i class="ft-arrow-right"></i></span>
                                        <div class="mpl-field">
                                            <label>End</label>
                                            <input type="time" class="form-control mpl-slot-end" name="slots[{{ $slot->id }}][end_time]" value="{{ $hm($slot->end_time) }}" required>
                                        </div>
                                    </div>
                                    <div class="mpl-chips">
                                        <span class="mpl-chip mpl-chip-total" title="Slot time"><i class="ft-clock"></i> <b class="mpl-total">0h 0m</b></span>
                                        <span class="mpl-chip mpl-chip-down" title="Breakdown time"><i class="ft-alert-triangle"></i> <b class="mpl-down">0h 0m</b></span>
                                        <span class="mpl-chip mpl-chip-run" title="Running time (slot minus breakdowns)"><i class="ft-play"></i> <b class="mpl-run">0h 0m</b></span>
                                    </div>
                                </div>

                                <div class="mpl-grid mpl-grid-machine mpl-grid-head">
                                    <span>From</span><span>To</span><span>Duration</span><span>Remarks</span><span></span>
                                </div>
                                <div class="mpl-rows">
                                    @foreach($slot->breakdowns->sortBy('from')->values() as $i => $bd)
                                        <div class="mpl-grid mpl-grid-machine mpl-row">
                                            <input type="time" class="form-control mpl-from" name="breakdowns[{{ $slot->id }}][{{ $i }}][from]" value="{{ $hm($bd->from) }}">
                                            <input type="time" class="form-control mpl-to" name="breakdowns[{{ $slot->id }}][{{ $i }}][to]" value="{{ $hm($bd->to) }}">
                                            <span class="mpl-dur"></span>
                                            <input type="text" class="form-control" name="breakdowns[{{ $slot->id }}][{{ $i }}][remarks]" value="{{ $bd->remarks }}" placeholder="Reason / remarks">
                                            <button type="button" class="mpl-remove" title="Remove"><i class="ft-trash-2"></i></button>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="mpl-none {{ $slot->breakdowns->isEmpty() ? '' : 'd-none' }}">No breakdown in this slot.</div>
                                <button type="button" class="mpl-add-btn mpl-add"><i class="ft-plus"></i> Add Breakdown</button>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif

        @if($offMachines->isNotEmpty())
            <div class="mpl-off"><i class="ft-power"></i> Off in this plan: {{ $offMachines->map(fn ($s) => $s->first()->machine->name ?? '--')->implode(', ') }}</div>
        @endif

        <!-- Plant breakdown (whole day) -->
        <div class="mpl-section-title mt-4">
            <span><i class="ft-zap-off mr-1"></i> Plant Breakdown</span>
            <small>Whole-plant stops on {{ $plan->date->format('d-m-Y') }}, shared by all job order plans of {{ $plan->plant->name ?? 'this plant' }} on this date.</small>
        </div>
        <div class="mpl-plant">
            <div class="mpl-grid mpl-grid-plant mpl-grid-head">
                <span>Type</span><span>From</span><span>To</span><span>Duration</span><span>Remarks</span><span></span>
            </div>
            <div id="mpl_plant_rows">
                @foreach($plantItems as $i => $bd)
                    <div class="mpl-grid mpl-grid-plant mpl-row">
                        <select class="form-control" name="plant_breakdowns[{{ $i }}][breakdown_type_id]">
                            <option value="">Select Type</option>
                            @foreach($breakdownTypes as $type)
                                <option value="{{ $type->id }}" @selected($bd->breakdown_type_id == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <input type="time" class="form-control mpl-from" name="plant_breakdowns[{{ $i }}][from]" value="{{ $hm($bd->from) }}">
                        <input type="time" class="form-control mpl-to" name="plant_breakdowns[{{ $i }}][to]" value="{{ $hm($bd->to) }}">
                        <span class="mpl-dur"></span>
                        <input type="text" class="form-control" name="plant_breakdowns[{{ $i }}][remarks]" value="{{ $bd->remarks }}" placeholder="Remarks">
                        <button type="button" class="mpl-remove" title="Remove"><i class="ft-trash-2"></i></button>
                    </div>
                @endforeach
            </div>
            <div class="mpl-none {{ $plantItems->isEmpty() ? '' : 'd-none' }}" id="mpl_plant_none">No plant breakdown on this date.</div>
            <button type="button" class="mpl-add-btn" id="mpl_add_plant"><i class="ft-plus"></i> Add Plant Breakdown</button>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12 text-right">
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton"><i class="ft-save mr-1"></i> Save Logs</button>
        </div>
    </div>
</form>

<template id="mpl_plant_template">
    <div class="mpl-grid mpl-grid-plant mpl-row">
        <select class="form-control" name="plant_breakdowns[__i__][breakdown_type_id]">
            <option value="">Select Type</option>
            @foreach($breakdownTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
            @endforeach
        </select>
        <input type="time" class="form-control mpl-from" name="plant_breakdowns[__i__][from]">
        <input type="time" class="form-control mpl-to" name="plant_breakdowns[__i__][to]">
        <span class="mpl-dur"></span>
        <input type="text" class="form-control" name="plant_breakdowns[__i__][remarks]" placeholder="Remarks">
        <button type="button" class="mpl-remove" title="Remove"><i class="ft-trash-2"></i></button>
    </div>
</template>

<style>
    .mpl { padding: 0 2px; }

    /* Summary */
    .mpl-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-bottom: 18px; }
    .mpl-stat { display: flex; gap: 10px; align-items: flex-start; background: #fff; border: 1px solid #e5eaf1; border-radius: 10px; padding: 12px; }
    .mpl-stat-icon { width: 34px; height: 34px; border-radius: 8px; background: #eef4ff; color: #3466c2; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
    .mpl-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: .5px; color: #7b8794; font-weight: 600; }
    .mpl-value { font-weight: 700; color: #1f2d3d; font-size: 13.5px; word-break: break-word; }
    .mpl-sub { font-size: 12px; color: #94a3b8; }
    .mpl-summary-remarks { grid-column: 1 / -1; background: #fffbea; border: 1px solid #f5e3a3; color: #7a5d00; border-radius: 8px; padding: 8px 12px; font-size: 13px; }

    /* Section titles */
    .mpl-section-title { display: flex; flex-direction: column; margin-bottom: 10px; }
    .mpl-section-title span { font-weight: 700; font-size: 14px; color: #1f2d3d; }
    .mpl-section-title small { color: #8492a6; font-size: 12px; }

    /* Tabs */
    .mpl-tabs { gap: 6px; border-bottom: 2px solid #e5eaf1; margin-bottom: 12px; flex-wrap: wrap; }
    .mpl-tabs .nav-link { border: 0; border-bottom: 2px solid transparent; margin-bottom: -2px; color: #64748b; font-weight: 600; padding: 8px 14px; display: flex; align-items: center; gap: 6px; border-radius: 0; }
    .mpl-tabs .nav-link:hover { color: #3466c2; }
    .mpl-tabs .nav-link.active { color: #3466c2; border-bottom-color: #3466c2; background: transparent; }
    .mpl-tab-count { background: #f59e0b; color: #fff; font-size: 11px; font-weight: 700; border-radius: 10px; min-width: 20px; height: 20px; padding: 0 6px; display: inline-flex; align-items: center; justify-content: center; }

    /* Slot card */
    .mpl-slot { border: 1px solid #e5eaf1; border-radius: 10px; padding: 14px; margin-bottom: 12px; background: #fff; }
    .mpl-slot-head { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 14px; padding-bottom: 12px; margin-bottom: 10px; border-bottom: 1px dashed #e5eaf1; }
    .mpl-slot-title { font-weight: 700; color: #1f2d3d; align-self: center; min-width: 80px; }
    .mpl-slot-time { display: flex; align-items: flex-end; gap: 8px; }
    .mpl-field label { display: block; margin: 0 0 3px; font-size: 10.5px; font-weight: 600; color: #7b8794; text-transform: uppercase; letter-spacing: .4px; }
    .mpl-field .form-control { height: 36px; width: 150px; }
    .mpl-arrow { color: #94a3b8; padding-bottom: 8px; }
    .mpl-chips { display: flex; gap: 6px; flex-wrap: wrap; margin-left: auto; align-self: center; }
    .mpl-chip { font-size: 12px; border-radius: 14px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; }
    .mpl-chip b { font-weight: 700; }
    .mpl-chip-total { background: #eef4ff; color: #3466c2; }
    .mpl-chip-down { background: #fff4e5; color: #b45309; }
    .mpl-chip-run { background: #e8f7ee; color: #1e7e44; }

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

    .mpl-plant { border: 1px solid #e5eaf1; border-radius: 10px; padding: 14px; background: #fff; }
    .mpl-off { color: #94a3b8; font-size: 12.5px; margin-top: 2px; }
    .mpl-empty-box { text-align: center; color: #94a3b8; border: 1px dashed #d5dde8; border-radius: 10px; padding: 20px; }

    /* Narrow drawers: stack the breakdown columns */
    @media (max-width: 991px) {
        .mpl-grid-head { display: none; }
        .mpl-grid-machine, .mpl-grid-plant { grid-template-columns: 1fr 1fr; }
        .mpl-chips { margin-left: 0; }
    }

    /* Dark mode */
    .layout-dark .mpl-stat, .layout-dark .mpl-slot, .layout-dark .mpl-plant { background: #1a1a1a; border-color: #404040; }
    .layout-dark .mpl-value, .layout-dark .mpl-section-title span, .layout-dark .mpl-slot-title { color: #e2e8f0; }
    .layout-dark .mpl-stat-icon { background: #13263a; color: #8ab4ff; }
    .layout-dark .mpl-tabs { border-bottom-color: #404040; }
    .layout-dark .mpl-slot-head { border-bottom-color: #404040; }
    .layout-dark .mpl-add-btn { background: #13263a; border-color: #2c4a6b; color: #8ab4ff; }
    .layout-dark .mpl-remove { background: #2a1515; border-color: #5c2626; }
</style>

<script>
    (function () {
        var $form = $('#ajaxSubmit');
        var counter = 1000; // new rows get indexes that never clash with saved ones

        function minutes(start, end) {
            if (!start || !end) return 0;
            var s = start.split(':'), e = end.split(':');
            var diff = (e[0] * 60 + +e[1]) - (s[0] * 60 + +s[1]);
            return diff < 0 ? diff + 1440 : diff;
        }
        function fmt(min) { return Math.floor(min / 60) + 'h ' + (min % 60) + 'm'; }

        function refreshRow($row) {
            var min = minutes($row.find('.mpl-from').val(), $row.find('.mpl-to').val());
            $row.find('.mpl-dur').text(min ? fmt(min) : '');
        }
        // Slot chips: slot time, breakdown time, running time
        function refreshSlot($slot) {
            var total = minutes($slot.find('.mpl-slot-start').val(), $slot.find('.mpl-slot-end').val());
            var down = 0;
            $slot.find('.mpl-row').each(function () { down += minutes($(this).find('.mpl-from').val(), $(this).find('.mpl-to').val()); });
            $slot.find('.mpl-total').text(fmt(total));
            $slot.find('.mpl-down').text(fmt(down));
            $slot.find('.mpl-run').text(fmt(Math.max(total - down, 0)));
        }
        function toggleEmpty($container) {
            var $rows = $container.find('.mpl-rows, #mpl_plant_rows').first();
            var has = $rows.children().length > 0;
            $container.find('.mpl-none').toggleClass('d-none', has);
            $container.find('.mpl-grid-head').toggle(has);
        }
        function refreshTabCount($pane) {
            if (!$pane.length) return;
            var count = $pane.find('.mpl-row').length;
            $('a[href="#' + $pane.attr('id') + '"] .mpl-tab-count').text(count).toggleClass('d-none', !count);
        }

        function machineRow(itemId, i) {
            var n = 'breakdowns[' + itemId + '][' + i + ']';
            return '<div class="mpl-grid mpl-grid-machine mpl-row">' +
                '<input type="time" class="form-control mpl-from" name="' + n + '[from]">' +
                '<input type="time" class="form-control mpl-to" name="' + n + '[to]">' +
                '<span class="mpl-dur"></span>' +
                '<input type="text" class="form-control" name="' + n + '[remarks]" placeholder="Reason / remarks">' +
                '<button type="button" class="mpl-remove" title="Remove"><i class="ft-trash-2"></i></button>' +
                '</div>';
        }

        $form.off('.mpl')
            .on('click.mpl', '.mpl-add', function () {
                var $slot = $(this).closest('.mpl-slot');
                $slot.find('.mpl-rows').append(machineRow($slot.data('item'), counter++));
                toggleEmpty($slot);
                refreshTabCount($slot.closest('.tab-pane'));
                $slot.find('.mpl-rows .mpl-row:last .mpl-from').focus();
            })
            .on('click.mpl', '#mpl_add_plant', function () {
                $('#mpl_plant_rows').append($('#mpl_plant_template').html().replace(/__i__/g, counter++));
                toggleEmpty($('.mpl-plant'));
            })
            .on('click.mpl', '.mpl-remove', function () {
                var $row = $(this).closest('.mpl-row');
                var $box = $row.closest('.mpl-slot, .mpl-plant');
                $row.remove();
                toggleEmpty($box);
                if ($box.hasClass('mpl-slot')) {
                    refreshSlot($box);
                    refreshTabCount($box.closest('.tab-pane'));
                }
            })
            .on('change.mpl input.mpl', '.mpl-from, .mpl-to', function () {
                refreshRow($(this).closest('.mpl-row'));
                var $slot = $(this).closest('.mpl-slot');
                if ($slot.length) refreshSlot($slot);
            })
            .on('change.mpl input.mpl', '.mpl-slot-start, .mpl-slot-end', function () { refreshSlot($(this).closest('.mpl-slot')); });

        $form.find('.mpl-row').each(function () { refreshRow($(this)); });
        $form.find('.mpl-slot').each(function () { refreshSlot($(this)); toggleEmpty($(this)); });
        toggleEmpty($('.mpl-plant'));
    })();
</script>
