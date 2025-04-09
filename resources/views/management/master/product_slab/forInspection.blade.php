<div class="row">
    <div class="col-12">
        <h6 class="header-heading-sepration">
            QC Checklist
        </h6>
    </div>
</div>

<div class="striped-rows">
    {{-- Slabs --}}
    @if (count($slabs) != 0)
        @foreach ($slabs as $slab)
            <div class="form-group row">
                <input type="hidden" name="product_slab_type_id[]" value="{{ $slab->slabType->id }}">
                <label class="col-md-3 label-control font-weight-bold" for="slab-input-{{ $loop->index }}">
                    {{ $slab->slabType->name }}
                </label>
                <div class="col-md-9">
                    <input type="text" id="slab-input-{{ $loop->index }}" class="form-control" name="checklist_value[]" placeholder="%">
                </div>
            </div>
        @endforeach 
    @else
        <div class="alert alert-warning">
            No Slabs Found
        </div>
    @endif

    {{-- Compulsory QC Params --}}
    @if (count($compulsoryParams) != 0)
        @foreach ($compulsoryParams as $param)
            <div class="form-group row">
                <input type="hidden" name="arrival_compulsory_qc_param_id[]" value="{{ $param->id }}">
                <label class="col-md-3 label-control font-weight-bold" for="qc-param-{{ $loop->index }}">
                    {{ $param->name }}
                </label>
                <div class="col-md-9">
                    @if ($param->type == 'dropdown')
                        <select name="compulsory_checklist_value[]" id="qc-param-{{ $loop->index }}" class="form-control">
                            <option value="">Select Option</option>
                            @foreach (json_decode($param->options, true) ?? [] as $key => $option)
                                <option value="{{ $option }}" {{$key == 0 ? 'selected' : ''}}>{{ $option }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" id="qc-param-{{ $loop->index }}" class="form-control" name="compulsory_checklist_value[]" placeholder="Enter value">
                    @endif
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-warning">
            No QC Parameters Found
        </div>
    @endif
</div>
