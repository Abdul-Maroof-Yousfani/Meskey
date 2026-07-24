<form method="POST" action="{{route('milling-rate.update', $millingRate->id)}}" id="ajaxSubmit" enctype="multipart/form-data">
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.milling-rates') }}" />

    <div class="row form-mar">
        
        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="form-group">
                <label>Location:</label>
                <select name="location_id" id="location_id" class="form-control select2" onchange="getSubLocations(this.value)">
                    <option value="">Select Location</option>
                    @foreach($companyLocations as $loc)
                        <option value="{{ $loc->id }}" {{$millingRate->location_id == $loc->id ? 'selected' : ''}}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="form-group">
                <label>Sub Location:</label>
                <select name="sublocation_id" id="sublocation_id" class="form-control select2" onchange="getPlants(this.value)">
                    <option value="">Select Sub Location</option>
                    @foreach($arrivalLocations as $arr)
                        <option value="{{ $arr->id }}" {{$millingRate->sublocation_id == $arr->id ? 'selected' : ''}}>{{ $arr->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="form-group">
                <label>Plant:</label>
                <select name="plant_id" id="plant_id" class="form-control select2">
                    <option value="">Select Plant</option>
                    @foreach($plants as $plant)
                        <option value="{{ $plant->id }}" {{$millingRate->plant_id == $plant->id ? 'selected' : ''}}>{{ $plant->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Title:</label>
                <input type="text" name="title" value="{{$millingRate->title}}" placeholder="Title" class="form-control" autocomplete="off" />
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control" rows="3">{{$millingRate->description}}</textarea>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <input type="hidden" name="status" value="0">
                <div class="custom-control custom-switch custom-control-inline">
                    <input type="checkbox" name="status" value="1" class="custom-control-input" id="statusSwitchEdit" {{$millingRate->status == 1 ? 'checked' : ''}}>
                    <label class="custom-control-label" for="statusSwitchEdit">Active</label>
                </div>
            </div>
        </div>
        
        <div class="col-xs-12 col-sm-12 col-md-12 mt-2">
            <div class="border rounded p-3">
                <div class="row align-items-center mb-1">
                    <div class="col-md-5">
                        <label class="font-weight-bold text-uppercase mb-0">Variable Name</label>
                    </div>
                    <div class="col-md-7">
                        <label class="font-weight-bold text-uppercase mb-0">Value</label>
                    </div>
                </div>
                <hr class="mt-1 mb-3">
                @php
                    $existingVariables = $millingRate->variables->keyBy('id');
                @endphp
                @foreach($variables as $variable)
                @php
                    $isChecked = $existingVariables->has($variable->id);
                    $value = $isChecked ? $existingVariables[$variable->id]->pivot->value : '';
                @endphp
                <div class="row align-items-center mb-3">
                    <div class="col-md-5">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input variable-toggle" data-target="var_input_{{ $variable->id }}" id="var_switch_edit_{{ $variable->id }}" {{ $isChecked ? 'checked' : '' }}>
                            <label class="custom-control-label pt-1 cursor-pointer" for="var_switch_edit_{{ $variable->id }}">{{ $variable->title }}</label>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div id="var_input_{{ $variable->id }}" style="{{ $isChecked ? '' : 'display: none;' }}">
                            <input type="number" step="0.01" min="0" name="variables[{{ $variable->id }}]" class="form-control" placeholder="Enter value" value="{{ $value }}" {{ $isChecked ? 'required' : '' }}>
                        </div>
                    </div>
                </div>
                @endforeach
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
    $(document).ready(function() {
        $('.select2').select2();

        $('.variable-toggle').change(function() {
            var targetId = $(this).data('target');
            if($(this).is(':checked')) {
                $('#' + targetId).show().find('input').attr('required', true);
            } else {
                $('#' + targetId).hide().find('input').val('').removeAttr('required');
            }
        });
    });

    function getSubLocations(locationId) {
        $('#sublocation_id').empty().append('<option value="">Select Sub Location</option>');
        $('#plant_id').empty().append('<option value="">Select Plant</option>');
        
        if(locationId) {
            $.ajax({
                url: `{{ url('master/milling-rate/get-sub-locations') }}/${locationId}`,
                type: 'GET',
                success: function(res) {
                    var html = '<option value="">Select Sub Location</option>';
                    $.each(res, function(i, v) {
                        html += `<option value="${v.id}">${v.name}</option>`;
                    });
                    $('#sublocation_id').html(html);
                }
            });
        }
    }

    function getPlants(subLocationId) {
        $('#plant_id').empty().append('<option value="">Select Plant</option>');
        
        if(subLocationId) {
            $.ajax({
                url: `{{ url('master/milling-rate/get-plants') }}/${subLocationId}`,
                type: 'GET',
                success: function(res) {
                    var html = '<option value="">Select Plant</option>';
                    $.each(res, function(i, v) {
                        html += `<option value="${v.id}">${v.name}</option>`;
                    });
                    $('#plant_id').html(html);
                }
            });
        }
    }
</script>
