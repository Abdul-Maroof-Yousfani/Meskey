<div class="card mt-4 border-0 shadow-sm">
    <div class="card-header border-bottom pb-2 px-0">
        <h5 class="mb-0">QC Relief Parameters for {{ $product->name }}</h5>
    </div>
    <div class="card-body px-0 py-3">
        <form id="reliefParametersForm" class="needs-validation" novalidate>
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">

            @if ($parameters->where('type', 'slab')->isNotEmpty())
                <div class="mb-5">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="50%">Parameter</th>
                                    <th width="50%">Relief Percentage</th>
                                    {{-- <th width="20%">Status</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($parameters->where('type', 'slab') as $param)
                                    <tr>
                                        <td class="align-middle">{{ $param['name'] }}</td>
                                        <td>
                                            <div class="input-group mb-0 py-1">
                                                <input type="number" step="0.01" min="0" max="100"
                                                    name="parameters[{{ $loop->index + $parameters->where('type', 'compulsory')->count() }}][relief_percentage]"
                                                    value="{{ $param['relief_percentage'] }}"
                                                    class="form-control border-right-0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text bg-transparent">%</span>
                                                </div>
                                            </div>
                                            <input type="hidden"
                                                name="parameters[{{ $loop->index + $parameters->where('type', 'compulsory')->count() }}][name]"
                                                value="{{ $param['name'] }}">
                                            <input type="hidden"
                                                name="parameters[{{ $loop->index + $parameters->where('type', 'compulsory')->count() }}][type]"
                                                value="slab">
                                            <input type="hidden"
                                                name="parameters[{{ $loop->index + $parameters->where('type', 'compulsory')->count() }}][slab_type_id]"
                                                value="{{ $param['slab_type_id'] }}">
                                        </td>
                                        <td class="align-middle d-none">
                                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                <label
                                                    class="btn btn-sm btn-outline-success {{ $param['is_active'] ? 'active' : '' }}">
                                                    <input type="radio"
                                                        name="parameters[{{ $loop->index + $parameters->where('type', 'compulsory')->count() }}][is_active]"
                                                        value="1" {{ $param['is_active'] ? 'checked' : '' }}>
                                                    Active
                                                </label>
                                                <label
                                                    class="btn btn-sm btn-outline-danger {{ !$param['is_active'] ? 'active' : '' }}">
                                                    <input type="radio"
                                                        name="parameters[{{ $loop->index + $parameters->where('type', 'compulsory')->count() }}][is_active]"
                                                        value="0" {{ !$param['is_active'] ? 'checked' : '' }}>
                                                    Inactive
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($parameters->where('type', 'compulsory')->isNotEmpty())
                <div class="mb-5 d-none">
                    <h6 class="text-uppercase text-muted mb-3 font-weight-bold">Compulsory Parameters</h6>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th width="40%">Parameter</th>
                                    <th width="40%">Relief Percentage</th>
                                    {{-- <th width="20%">Status</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($parameters->where('type', 'compulsory') as $param)
                                    <tr>
                                        <td class="align-middle">{{ $param['name'] }}</td>
                                        <td>
                                            <div class="input-group mb-0 py-1">
                                                <input type="number" step="0.01" min="0" max="100"
                                                    name="parameters[{{ $loop->index }}][relief_percentage]"
                                                    value="{{ $param['relief_percentage'] }}"
                                                    class="form-control border-right-0">
                                                <div class="input-group-append">
                                                    <span class="input-group-text bg-transparent">%</span>
                                                </div>
                                            </div>
                                            <input type="hidden" name="parameters[{{ $loop->index }}][name]"
                                                value="{{ $param['name'] }}">
                                            <input type="hidden" name="parameters[{{ $loop->index }}][type]"
                                                value="compulsory">
                                        </td>
                                        <td class="align-middle d-none">
                                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                                <label
                                                    class="btn btn-sm btn-outline-success {{ $param['is_active'] ? 'active' : '' }}">
                                                    <input type="radio"
                                                        name="parameters[{{ $loop->index }}][is_active]"
                                                        value="1" {{ $param['is_active'] ? 'checked' : '' }}>
                                                    Active
                                                </label>
                                                <label
                                                    class="btn btn-sm btn-outline-danger {{ !$param['is_active'] ? 'active' : '' }}">
                                                    <input type="radio"
                                                        name="parameters[{{ $loop->index }}][is_active]"
                                                        value="0" {{ !$param['is_active'] ? 'checked' : '' }}>
                                                    Inactive
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($parameters->isEmpty())
                <div class="alert alert-warning mb-4">
                    No parameters found for this product
                </div>
            @endif

            <div class="text-right mt-4">
                <button type="submit" class="btn btn-primary">Save All Parameters</button>
            </div>
        </form>
    </div>
</div>
