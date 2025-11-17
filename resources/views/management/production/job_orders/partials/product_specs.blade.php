@if(count($specs) > 0)
<div class="specifications-table">
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="">
                <tr>
                    <th width="40%">Specification Name</th>
                    <th width="30%">Value</th>
                    <th width="30%">UOM</th>
                </tr>
            </thead>
            <tbody>
                @foreach($specs as $index => $spec)
                <tr>
                    <td>
                        <strong>{{ $spec['spec_name'] }}</strong>
                        <input type="hidden" name="specifications[{{ $index }}][product_slab_id]" value="{{ $spec['id'] }}">
                        <input type="hidden" name="specifications[{{ $index }}][spec_name]" value="{{ $spec['spec_name'] }}">
                        <input type="hidden" name="specifications[{{ $index }}][uom]" value="{{ $spec['uom'] }}">
                    </td>
                    <td>
                        <input type="text" 
                               name="specifications[{{ $index }}][spec_value]" 
                               value="{{ $spec['spec_value'] ?? 0 }}" 
                               class="form-control form-control-sm spec-value-input"
                               placeholder="Enter value">
                    </td>
                    <td>
                        {{ $spec['uom'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
.specifications-table {
    margin-top: 15px;
}
.specifications-table .table {
    font-size: 14px;
}
/* .specifications-table .table th {
    background-color: #343a40;
    color: white;
    font-weight: 600;
} */
.specifications-table .table td {
    vertical-align: middle;
    padding: 10px 8px;
}
.spec-value-input {
    min-width: 120px;
    text-align: center;
    font-weight: 500;
}
</style>
@else
<div class="alert bg-light-warning mb-2 alert-light-warning" role="alert">
    <i class="ft-info mr-1"></i>
    <strong>No specifications found!</strong> No product specifications available for the selected product.
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif