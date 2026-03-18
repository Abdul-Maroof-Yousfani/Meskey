<form action="{{ route('plant-breakdown.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.plant-breakdown') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">Plant Breakdown Information</h6>
            <div class="row">

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date:</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Plant:</label>
                        <select name="plant_id" id="plant_id" class="form-control select2" required>
                            <option value="">Select Plant</option>
                            @foreach($plants as $plant)
                                <option value="{{ $plant->id }}">{{$plant->arrivalLocation->name}} - {{ $plant->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            
            </div>
        </div>

        <div class="col-md-12 mt-3">
            <h6 class="header-heading-sepration">Breakdown Items</h6>
            <div class="row">
                <div class="col-md-12">
                    <div id="breakdownItemsTable">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th class="col-3">Breakdown Type</th>
                                    <th class="col-1">From</th>
                                    <th class="col-1">To</th>
                                    <th class="col-1">Hours</th>
                                    <th class="col-5">Remarks</th>
                                    <th class="col-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="breakdown_type_id[]" class="form-control " required>
                                            <option value="">Select Breakdown Type</option>
                                            @foreach($breakdownTypes as $type)
                                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" name="from[]" class="form-control from-time" required>
                                    </td>
                                    <td>
                                        <input type="time" name="to[]" class="form-control to-time" required>
                                    </td>
                                    <td>
                                        <input type="number" name="hours[]" class="form-control hours-input" step="0.01"
                                            min="0" readonly>
                                    </td>
                                    <td>
                                        <textarea name="remarks[]" class="form-control" rows="1"></textarea>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary copythis"><i class="fa fa-plus"></i></button>
                                        <button type="button" class="btn btn-sm btn-danger removethis"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
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
            <button type="submit" class="btn btn-primary submitbutton">Save Plant Breakdown</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        // Initialize Select2 for all selects
        $('.select2').select2({
            dropdownParent: $('.select2').closest('.modal').length ? $('.select2').closest('.modal') : $(document.body)
        });

        // Calculate hours on page load for existing rows
        calculateHours();
    });

    // Function to calculate hours from from and to times
    function calculateHours() {
        $('tbody tr').each(function () {
            var $row = $(this);
            var fromTime = $row.find('.from-time').val();
            var toTime = $row.find('.to-time').val();
            var $hoursInput = $row.find('.hours-input');

            if (fromTime && toTime) {
                var from = new Date('2000-01-01T' + fromTime + ':00');
                var to = new Date('2000-01-01T' + toTime + ':00');

                // Handle case where to time is next day (less than from)
                if (to < from) {
                    to.setDate(to.getDate() + 1);
                }

                var diffMs = to - from;
                var diffHours = diffMs / (1000 * 60 * 60);

                $hoursInput.val(diffHours.toFixed(2));
            } else {
                $hoursInput.val('');
            }
        });
    }

    // Calculate hours when from or to time changes
    $(document).on('change', '.from-time, .to-time', function () {
        calculateHours();
    });

    $(document).on('click', '.copythis', function (e) {
        e.stopImmediatePropagation();
        var $row = $(this).closest('tr');
        var clone = $row.clone();

        // Clear values
        clone.find('input[type="time"]').val('');
        clone.find('input[type="number"]').val('');
        clone.find('textarea').val('');
        clone.find('select').val('').trigger('change');

        // Remove existing select2 and reinitialize
        clone.find('select').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });

        $row.closest('tbody').append(clone);

        // Reinitialize Select2 for cloned row
        clone.find('select.select2').select2({
            dropdownParent: clone.closest('.modal').length ? clone.closest('.modal') : $(document.body)
        });

        // Recalculate hours
        calculateHours();
    });

    $(document).on('click', '.removethis', function () {
        if ($(this).closest('tbody').find('tr').length > 1) {
            $(this).closest('tr').remove();
            calculateHours(); // Recalculate after removal
        } else {
            toastr.error('You cannot remove the last row');
            return false;
        }
    });
</script>