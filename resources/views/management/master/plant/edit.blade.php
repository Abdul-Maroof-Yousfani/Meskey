<form method="POST" action="{{route('plant.update', $plant->id)}}" id="ajaxSubmit" enctype="multipart/form-data">
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.plant') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Company Location:</label>
                <select id="company_location_id" name="company_location_id" class="form-control select2" required>
                    <option value="">Select Location</option>
                    @foreach ($companyLocations as $companyLocation)
                        <option value="{{ $companyLocation->id }}" {{ $plant->company_location_id == $companyLocation->id ? 'selected' : '' }}>
                            {{ $companyLocation->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Arrival Location:</label>
                <select id="arrival_location_id" name="arrival_location_id" class="form-control select2" required>
                    <option value="">Select Location</option>
                    @foreach ($arrivalLocations as $arrivalLocation)
                        <option value="{{ $arrivalLocation->id }}" data-company-location="{{ $arrivalLocation->company_location_id }}" {{ $plant->arrival_location_id == $arrivalLocation->id ? 'selected' : '' }}>
                            {{ $arrivalLocation->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" value="{{ $plant->name }}" placeholder="Name" class="form-control"
                    autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description"
                    class="form-control">{{ $plant->description }}</textarea>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Production Labour charges:</label>
                <input type="number" step="0.01" min="0" name="production_labour_charges_per_kg"
                    value="{{ $plant->production_labour_charges_per_kg }}" placeholder="Production Labour charges"
                    class="form-control" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{ $plant->status == 'active' ? 'selected' : '' }} value="active">Active</option>
                    <option {{ $plant->status == 'inactive' ? 'selected' : '' }} value="inactive">Inactive
                    </option>
                </select>
            </div>
        </div>

        <!-- Auto Analysis Request Settings -->
        <div class="col-12 mt-2">
            <div class="card border p-2 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="font-weight-bold text-dark mb-0">
                        <i class="ft-activity text-primary mr-1"></i> Auto Analysis Request Configuration
                    </label>
                    <div>
                        <select name="auto_analysis_enabled" class="form-control form-control-sm" style="width: auto; display: inline-block;">
                            <option value="1" {{ $plant->auto_analysis_enabled ? 'selected' : '' }}>Auto Generation: Enabled</option>
                            <option value="0" {{ !$plant->auto_analysis_enabled ? 'selected' : '' }}>Auto Generation: Disabled</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label class="font-small-2 font-weight-bold text-primary">Input Analysis Interval (Minutes):</label>
                        <div class="input-group input-group-sm">
                            <input type="number" min="1" max="1440" name="input_min_interval" value="{{ $plant->getInputMinInterval() }}" class="form-control text-center" placeholder="Min (20)">
                            <div class="input-group-prepend input-group-append"><span class="input-group-text">to</span></div>
                            <input type="number" min="1" max="1440" name="input_max_interval" value="{{ $plant->getInputMaxInterval() }}" class="form-control text-center" placeholder="Max (60)">
                            <div class="input-group-append"><span class="input-group-text">mins</span></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="font-small-2 font-weight-bold text-success">Output Analysis Interval (Minutes):</label>
                        <div class="input-group input-group-sm">
                            <input type="number" min="1" max="1440" name="output_min_interval" value="{{ $plant->getOutputMinInterval() }}" class="form-control text-center" placeholder="Min (20)">
                            <div class="input-group-prepend input-group-append"><span class="input-group-text">to</span></div>
                            <input type="number" min="1" max="1440" name="output_max_interval" value="{{ $plant->getOutputMaxInterval() }}" class="form-control text-center" placeholder="Max (60)">
                            <div class="input-group-append"><span class="input-group-text">mins</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        const allArrivalLocations = @json($arrivalLocations);
        const initialArrivalId = "{{ $plant->arrival_location_id }}";

        function updateArrivalLocations(selectedArrivalId = null) {
            const companyLocationId = $('#company_location_id').val();
            const $arrivalSelect = $('#arrival_location_id');
            const currentVal = selectedArrivalId !== null ? selectedArrivalId : $arrivalSelect.val();

            $arrivalSelect.empty().append('<option value="">Select Location</option>');

            let filtered = allArrivalLocations;
            if (companyLocationId) {
                filtered = allArrivalLocations.filter(a => String(a.company_location_id) === String(companyLocationId));
            }

            filtered.forEach(function (loc) {
                $arrivalSelect.append(`<option value="${loc.id}" data-company-location="${loc.company_location_id}">${loc.name}</option>`);
            });

            if (currentVal && filtered.some(a => String(a.id) === String(currentVal))) {
                $arrivalSelect.val(currentVal);
            } else if (filtered.length === 1 && companyLocationId) {
                $arrivalSelect.val(filtered[0].id);
            } else {
                $arrivalSelect.val('');
            }

            $arrivalSelect.trigger('change.select2');
        }

        $('.select2').select2();

        updateArrivalLocations(initialArrivalId);

        $('#company_location_id').on('change', function () {
            updateArrivalLocations();
        });

        $('#arrival_location_id').on('change', function () {
            const arrivalId = $(this).val();
            if (arrivalId && !$('#company_location_id').val()) {
                const found = allArrivalLocations.find(a => String(a.id) === String(arrivalId));
                if (found && found.company_location_id) {
                    $('#company_location_id').val(found.company_location_id).trigger('change.select2');
                }
            }
        });
    });
</script>