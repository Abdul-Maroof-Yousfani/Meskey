<div class="col-12 mt-2">
    <h6 class="header-heading-sepration">
        Production Phases / Processes Configuration
    </h6>
    <div class="d-flex justify-content-between align-items-center mb-1">
        <div>
            <small class="text-muted">
                Select which production phases are active for this company location.
            </small>
        </div>
    </div>

    @php
        $allPhases = $production_phases ?? \App\Models\Master\ProductionPhase::active()->get();
        $selectedIds = isset($company_location) ? (array)($company_location->production_phases ?? []) : [];
    @endphp

    <div class="table-responsive">
        <table class="table table-bordered table-striped text-center align-middle" id="phases-config-table">
            <thead class="bg-light">
                <tr>
                    <th style="width: 10%;">Enable</th>
                    <th style="width: 30%;">Phase Name</th>
                    <th style="width: 20%;">Category</th>
                    <th style="width: 30%;">Description / Scope</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($allPhases as $phase)
                    @php
                        $isChecked = in_array((int)$phase->id, array_map('intval', (array)$selectedIds), true);
                    @endphp
                    <tr>
                        <td class="text-center">
                            <div class="custom-control custom-switch custom-control-inline">
                                <input type="checkbox" class="custom-control-input phase-enabled-toggle" 
                                       id="phase_switch_{{ $phase->id }}" 
                                       name="production_phases[]" 
                                       value="{{ $phase->id }}" 
                                       {{ $isChecked ? 'checked' : '' }}>
                                <label class="custom-control-label" for="phase_switch_{{ $phase->id }}"></label>
                            </div>
                        </td>
                        <td class="text-left font-weight-bold">
                            {{ $phase->name }}
                        </td>
                        <td class="text-left">
                            <span class="badge badge-light-primary">
                                {{ $phase->category ?? 'General' }}
                            </span>
                        </td>
                        <td class="text-left text-muted font-small-3">
                            {{ $phase->description ?? '--' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-muted py-3">No production phases configured in master.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
