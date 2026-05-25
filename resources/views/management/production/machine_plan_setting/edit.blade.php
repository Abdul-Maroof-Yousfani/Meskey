<form action="{{ route('machine-plan-setting.update', $machinePlanSetting->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.machine-plan-setting') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Machine Plan Setting Information</h6>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ $machinePlanSetting->date->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Plant:</label>
                        <select name="plant_id" id="plant_id" class="form-control select2" required>
                            <option value="">Select Plant</option>
                            @foreach($plants as $plant)
                                <option value="{{ $plant->id }}" {{ $machinePlanSetting->plant_id == $plant->id ? 'selected' : '' }}>
                                    {{ $plant->arrivalLocation->name ?? '' }} - {{ $plant->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Production Voucher:</label>
                        <select name="production_voucher_id" class="form-control select2">
                            <option value="">Select Production Voucher (Optional)</option>
                            @foreach($productionVouchers as $voucher)
                                <option value="{{ $voucher->id }}" {{ $machinePlanSetting->production_voucher_id == $voucher->id ? 'selected' : '' }}>
                                    {{ $voucher->prod_no }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Remarks:</label>
                        <textarea name="remarks" class="form-control" rows="2">{{ $machinePlanSetting->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 mt-3">
            <h6 class="header-heading-sepration">Machine Settings</h6>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="machinesTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 30%">Machine Name</th>
                                    <th style="width: 15%">Status</th>
                                    <th style="width: 25%">Hours</th>
                                    <th style="width: 25%">Remarks</th>
                                </tr>
                            </thead>
                            <tbody id="machinesList">
                                @php
                                    $machineItems = $machinePlanSetting->items->keyBy('production_machine_id');
                                @endphp
                                @foreach($machines as $index => $machine)
                                    @php
                                        $item = $machineItems->get($machine->id);
                                        $isEnabled = $item ? $item->is_enabled : false;
                                        $hours = $item ? $item->hours : '';
                                        $remarks = $item ? $item->remarks : '';
                                    @endphp
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $machine->name }}
                                            <input type="hidden" name="machines[{{ $index }}][production_machine_id]" value="{{ $machine->id }}">
                                        </td>
                                        <td class="text-center">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" 
                                                       class="custom-control-input machine-toggle" 
                                                       id="machine_{{ $machine->id }}" 
                                                       name="machines[{{ $index }}][is_enabled]"
                                                       value="1"
                                                       data-index="{{ $index }}"
                                                       {{ $isEnabled ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="machine_{{ $machine->id }}">
                                                    <span class="status-badge badge {{ $isEnabled ? 'badge-success' : 'badge-secondary' }}">
                                                        {{ $isEnabled ? 'On' : 'Off' }}
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" 
                                                   name="machines[{{ $index }}][hours]" 
                                                   class="form-control hours-input" 
                                                   step="0.5" 
                                                   min="0" 
                                                   max="24"
                                                   placeholder="Hours"
                                                   value="{{ $hours }}"
                                                   {{ !$isEnabled ? 'disabled' : '' }}>
                                        </td>
                                        <td>
                                            <textarea name="machines[{{ $index }}][remarks]" 
                                                      class="form-control" 
                                                      rows="1" 
                                                      placeholder="Remarks"
                                                      {{ !$isEnabled ? 'disabled' : '' }}>{{ $remarks }}</textarea>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar text-right">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Update Machine Plan Setting</button>
        </div>
    </div>
</form>


<script>
    $(document).ready(function() {
        $('.select2').select2({
            dropdownParent: $('.select2').closest('.modal').length ? $('.select2').closest('.modal') : $(document.body)
        });

        initializeToggles();
    });

    function initializeToggles() {
        $('.machine-toggle').each(function() {
            $(this).off('change').on('change', function() {
                var isChecked = $(this).is(':checked');
                var $row = $(this).closest('tr');
                var $hoursInput = $row.find('.hours-input');
                var $remarksTextarea = $row.find('textarea');
                var $statusLabel = $(this).siblings('label').find('.status-badge');
                
                if (isChecked) {
                    $hoursInput.prop('disabled', false);
                    $remarksTextarea.prop('disabled', false);
                    $statusLabel.removeClass('badge-secondary').addClass('badge-success').text('On');
                } else {
                    $hoursInput.prop('disabled', true).val('');
                    $remarksTextarea.prop('disabled', true).val('');
                    $statusLabel.removeClass('badge-success').addClass('badge-secondary').text('Off');
                }
            });
        });
    }
</script>
