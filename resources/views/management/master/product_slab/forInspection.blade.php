<div class="row">
    <div class="col-12">
        <h6 class="header-heading-sepration">
            QC Checklist
        </h6>
    </div>
</div>

<div class="striped-rows">
    @if (count($slabs) != 0)
        @foreach ($slabs as $slab)
            <div class="form-group row slab-row" data-max-range="{{ $slab->max_range }}">
                <input type="hidden" name="product_slab_type_id[]" value="{{ $slab->slabType->id }}">
                <label class="col-md-3 label-control font-weight-bold" for="slab-input-{{ $loop->index }}">
                    {{ $slab->slabType->name }}
                </label>
                <div class="col-md-9">
                    <div class="input-group">
                        <input type="number" id="slab-input-{{ $loop->index }}" class="form-control slab-input"
                            data-max-range="{{ $slab->max_range }}" name="checklist_value[]" placeholder="%"
                            min="0" step="0.01" value="{{ $isInner ? $slab->checklist_value : 0 }}">
                        <div class="input-group-append">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="alert alert-warning">
            No Slabs Found
        </div>
    @endif

    @if (count($compulsoryParams) != 0)
        @foreach ($compulsoryParams as $param)
            <div class="form-group row">
                <input type="hidden" name="arrival_compulsory_qc_param_id[]" value="{{ $param->id }}">
                <label class="col-md-3 label-control font-weight-bold" for="qc-param-{{ $loop->index }}">
                    {{ $param->name }}
                </label>
                <div class="col-md-9">
                    @if ($param->type == 'dropdown')
                        <select name="compulsory_checklist_value[]" id="qc-param-{{ $loop->index }}"
                            class="form-control">
                            <option value="">Select Option</option>
                            @foreach (json_decode($param->options, true) ?? [] as $key => $option)
                                <option value="{{ $option }}" @selected($param->checklist_value == $option || $key == 0)>
                                    {{ $option }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" id="qc-param-{{ $loop->index }}" class="form-control"
                            value="{{ $param->checklist_value }}" name="compulsory_checklist_value[]"
                            placeholder="Enter value">
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

<script>
    var slabInputs = document.querySelectorAll('.slab-input');

    slabInputs.forEach(input => {
        validateSlabInput(input);

        input.addEventListener('input', function() {
            validateSlabInput(this);
        });

        input.addEventListener('blur', function() {
            validateSlabInput(this);
        });
    });
</script>
