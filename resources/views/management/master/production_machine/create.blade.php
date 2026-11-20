<form action="{{ route('production-machine.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.production-machine') }}" />
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Company Location:</label>
                <select id="company_location_id" name="company_location_id" class="form-control select2">
                    <option value="">Select Location</option>
                    @foreach ($companyLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Arrival Location:</label>
                <select id="arrival_location_id" name="arrival_location_id" class="form-control select2">
                    <option value="">Select Arrival Location</option>
                    {{-- @foreach ($arrivalLocations as $arrival_location)
                    <option value="{{ $arrival_location->id }}">{{ $arrival_location->name }}</option>
                    @endforeach --}}
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Plant:</label>
                <select id="plant_id" name="plant_id" class="form-control select2">
                    <option value="">Select Plant</option>
                    
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" placeholder="Name" class="form-control" />
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control"></textarea>
            </div>
        </div>

        <!-- Required: always on in a Machine Plan; otherwise it comes switched off -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <input type="hidden" name="is_required" value="0">
                <div class="lock-arrival-wrapper is-required-card ">
                    <div class="lock-arrival-header">
                        <label class="lock-arrival-label" for="is_required_switch">
                            <i class="ft-lock lock-icon"></i>
                            <span class="is-required-state">Is Required</span>
                        </label>
                        <div class="custom-control custom-switch lock-arrival-switch">
                            <input type="checkbox" class="custom-control-input" id="is_required_switch" name="is_required" value="1" >
                            <label class="custom-control-label" for="is_required_switch"></label>
                        </div>
                    </div>
                    <p class="lock-arrival-description is-required-help" data-for="1">
                        <i class="fas fa-circle-info"></i>
                        This machine will come <strong>ON</strong> (selected) by default in Machine Plan Setting. It can still be switched off there.
                    </p>
                    <p class="lock-arrival-description is-required-help" data-for="0">
                        <i class="fas fa-circle-info"></i>
                        This machine will come <strong>OFF</strong> (deselected) in Machine Plan Setting. Turn it on there when it is needed.
                    </p>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
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

<style>
    .is-required-card .is-required-help { display: none; }
</style>

<script>
    // Show the message of the selected Is Required option
    (function () {
        function showRequiredHelp() {
            var on = $('#is_required_switch').is(':checked');
            var val = on ? '1' : '0';
            $('.is-required-state').text(on ? 'Required Machine' : 'Is Required');
            $('.is-required-card').toggleClass('active', on);
            $('.is-required-help').hide().filter('[data-for="' + val + '"]').show();
        }
        $(document).off('change.isRequired').on('change.isRequired', '#is_required_switch', showRequiredHelp);
        showRequiredHelp();
    })();
</script>
<script>
    $(document).ready(function () {
        $('.select2').select2();
    });
</script>