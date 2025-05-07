@if (count($slabs))
    <div class="row form-mar">
        <div class="col-md-12">
            <h6 class="header-heading-sepration">
                Slab Type Ranges:
            </h6>
        </div>
        @foreach ($slabs as $slab)
            <div class="col-md-4">
                <div class="form-group">
                    <label>{{ $slab['slab_type_name'] }}</label>
                    <div class="input-group">
                        <input type="text" name="slabs[{{ $slab['id'] }}][from]" value="{{ $slab['from'] }}"
                            class="form-control" readonly>
                        <span class="input-group-text">to</span>
                        <input type="text" name="slabs[{{ $slab['id'] }}][to]" value="{{ $slab['to'] }}"
                            class="form-control">
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
