<style>
    html, body {
        overflow-x: hidden;
    }
</style>

<form action="{{ route('sales.pre-sale-inspection.update', $pre_sale_inspection->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.pre-sale-inspection.list') }}" />

    @php
        $selectedLocations = [];
        if (is_array($pre_sale_inspection->locations) && count($pre_sale_inspection->locations) > 0) {
            $selectedLocations = array_map('strval', $pre_sale_inspection->locations);
        } elseif ($pre_sale_inspection->locationModels && $pre_sale_inspection->locationModels->count() > 0) {
            $selectedLocations = $pre_sale_inspection->locationModels->pluck('location_id')->map(fn($v) => (string)$v)->toArray();
        }

        $selectedFactories = [];
        if (is_array($pre_sale_inspection->factories) && count($pre_sale_inspection->factories) > 0) {
            $selectedFactories = array_map('strval', $pre_sale_inspection->factories);
        } elseif ($pre_sale_inspection->factoryModels && $pre_sale_inspection->factoryModels->count() > 0) {
            $selectedFactories = $pre_sale_inspection->factoryModels->pluck('arrival_location_id')->map(fn($v) => (string)$v)->toArray();
        }
        if (empty($selectedFactories) && $pre_sale_inspection->arrival_location_id) {
            $selectedFactories = [(string)$pre_sale_inspection->arrival_location_id];
        }

        $selectedSections = [];
        if (is_array($pre_sale_inspection->sections) && count($pre_sale_inspection->sections) > 0) {
            $selectedSections = array_map('strval', $pre_sale_inspection->sections);
        } elseif ($pre_sale_inspection->sectionModels && $pre_sale_inspection->sectionModels->count() > 0) {
            $selectedSections = $pre_sale_inspection->sectionModels->pluck('arrival_sub_location_id')->map(fn($v) => (string)$v)->toArray();
        }
        if (empty($selectedSections) && $pre_sale_inspection->arrival_sub_location_id) {
            $selectedSections = [(string)$pre_sale_inspection->arrival_sub_location_id];
        }
    @endphp

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="row">
                {{-- General Information --}}
                <div class="col-12">
                    <h6 class="header-heading-sepration">General Information</h6>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Inspection Number: <span class="text-danger">*</span></label>
                        <input type="text" name="inspection_no" id="inspection_no" class="form-control font-weight-bold"
                            value="{{ $pre_sale_inspection->inspection_no }}" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Inspection Date: <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="inspection_date"
                            class="form-control" value="{{ $pre_sale_inspection->date ? $pre_sale_inspection->date->format('Y-m-d') : date('Y-m-d') }}">
                    </div>
                </div>

                {{-- Party Details --}}
                <div class="col-12 mt-2">
                    <h6 class="header-heading-sepration">Party Details</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Party Name: <span class="text-danger">*</span></label>
                        <input type="text" name="party_name" id="party_name" class="form-control"
                            value="{{ $pre_sale_inspection->party_name }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Party Contact No:</label>
                        <input type="text" name="party_contact_no" id="party_contact_no" class="form-control"
                            value="{{ $pre_sale_inspection->party_contact_no }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Reference:</label>
                        <input type="text" name="reference" id="reference" class="form-control"
                            value="{{ $pre_sale_inspection->reference }}" placeholder="Enter Reference">
                    </div>
                </div>

                {{-- Item Details --}}
                <div class="col-12 mt-2">
                    <h6 class="header-heading-sepration d-flex justify-content-between align-items-center">
                        Item Details
                        <button type="button" class="btn btn-sm btn-primary" onclick="addPsiItemRow()">
                            <i class="ft-plus"></i> Add Item
                        </button>
                    </h6>
                    <div class="dropdown-divider mb-2"></div>
                </div>

                <div class="col-12 mb-3">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" id="psiItemsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 55%;">Item (Product) <span class="text-danger">*</span></th>
                                    <th style="width: 33%;">Weight (kg) <span class="text-danger">*</span></th>
                                    <th style="width: 12%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="psiItemsBody">
                                @forelse ($pre_sale_inspection->items as $rowItem)
                                    <tr class="psi-item-row">
                                        <td>
                                            <select name="item_id[]" class="form-control select2 psi-item-select" required>
                                                <option value="">Select Item (Product)</option>
                                                @foreach ($items ?? [] as $item)
                                                    <option value="{{ $item->id }}" @selected($rowItem->item_id == $item->id)>
                                                        {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="any" min="0" name="weight[]" class="form-control psi-item-weight"
                                                value="{{ $rowItem->weight }}" placeholder="Enter Weight" required>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-item" onclick="removePsiItemRow(this)" title="Remove">
                                                <i class="ft-trash-2"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="psi-item-row">
                                        <td>
                                            <select name="item_id[]" class="form-control select2 psi-item-select" required>
                                                <option value="">Select Item (Product)</option>
                                                @foreach ($items ?? [] as $item)
                                                    <option value="{{ $item->id }}" @selected($pre_sale_inspection->item_id == $item->id)>
                                                        {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="any" min="0" name="weight[]" class="form-control psi-item-weight"
                                                value="0" placeholder="Enter Weight" required>
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-danger btn-remove-item" onclick="removePsiItemRow(this)" title="Remove">
                                                <i class="ft-trash-2"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td class="text-right">Total Weight:</td>
                                    <td>
                                        <span id="psi_total_weight" class="text-primary font-medium-1">0.00</span> kg
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Location Details --}}
                <div class="col-12 mt-2">
                    <h6 class="header-heading-sepration">Location Details</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Locations (Multi): <span class="text-danger">*</span></label>
                        <select name="locations[]" id="psi_locations" class="form-control select2" multiple required>
                            @foreach (get_locations() as $loc)
                                <option value="{{ $loc->id }}" @selected(in_array((string)$loc->id, $selectedLocations))>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Factory (Multi):</label>
                        <select name="arrival_location_id[]" id="psi_factories" class="form-control select2" multiple>
                            @foreach ($arrivalLocations as $factory)
                                @if (in_array((string)$factory->company_location_id, $selectedLocations))
                                    <option value="{{ $factory->id }}" @selected(in_array((string)$factory->id, $selectedFactories))>
                                        {{ $factory->name }} ({{ $factory->companyLocation ? $factory->companyLocation->name : '' }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Section (Multi):</label>
                        <select name="arrival_sub_location_id[]" id="psi_sections" class="form-control select2" multiple>
                            @foreach ($arrivalSubLocations as $section)
                                @if (in_array((string)$section->arrival_location_id, $selectedFactories))
                                    <option value="{{ $section->id }}" @selected(in_array((string)$section->id, $selectedSections))>
                                        {{ $section->name }} ({{ $section->arrivalLocation ? $section->arrivalLocation->name : '' }})
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Remarks --}}
                <div class="col-12 mt-2">
                    <h6 class="header-heading-sepration">Remarks</h6>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <textarea name="remarks" id="remarks" class="form-control" rows="3">{{ $pre_sale_inspection->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="psiItemRowTemplate">
        <tr class="psi-item-row">
            <td>
                <select name="item_id[]" class="form-control psi-item-select" required>
                    <option value="">Select Item (Product)</option>
                    @foreach ($items ?? [] as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" step="any" min="0" name="weight[]" class="form-control psi-item-weight" placeholder="Enter Weight" required>
            </td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-sm btn-danger btn-remove-item" onclick="removePsiItemRow(this)" title="Remove">
                    <i class="ft-trash-2"></i>
                </button>
            </td>
        </tr>
    </template>

    <div class="row bottom-button-bar mt-3">
        <div class="col-12 text-right">
            <button type="button" class="btn btn-danger modal-sidebar-close closebutton mr-2">Close</button>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2();

        const allFactories = @json($arrivalLocations);
        const allSections = @json($arrivalSubLocations);
        const selectedFactoriesInit = (@json($selectedFactories) || []).map(String);
        const selectedSectionsInit = (@json($selectedSections) || []).map(String);
        let isInitialLoad = true;

        function populateFactories() {
            const selectedLocations = ($('#psi_locations').val() || []).map(String);
            let currentFactories = ($('#psi_factories').val() || []).map(String);
            if (isInitialLoad && currentFactories.length === 0) {
                currentFactories = selectedFactoriesInit;
            }

            $('#psi_factories').empty();

            if (selectedLocations.length === 0) {
                $('#psi_factories').val([]).trigger('change.select2');
                return;
            }

            const validFactories = [];
            allFactories
                .filter(f => selectedLocations.includes(String(f.company_location_id)))
                .forEach(f => {
                    const isSelected = currentFactories.includes(String(f.id));
                    $('#psi_factories').append(`<option value="${f.id}" ${isSelected ? 'selected' : ''}>${f.name} (${f.company_location ? f.company_location.name : ''})</option>`);
                    if (isSelected) {
                        validFactories.push(String(f.id));
                    }
                });

            $('#psi_factories').val(validFactories).trigger('change.select2');
        }

        function populateSections() {
            const factoryIds = ($('#psi_factories').val() || []).map(String);
            let currentSections = ($('#psi_sections').val() || []).map(String);
            if (isInitialLoad && currentSections.length === 0) {
                currentSections = selectedSectionsInit;
            }

            $('#psi_sections').empty();

            if (factoryIds.length === 0) {
                $('#psi_sections').val([]).trigger('change.select2');
                return;
            }

            const validSections = [];
            allSections
                .filter(s => factoryIds.includes(String(s.arrival_location_id)))
                .forEach(s => {
                    const isSelected = currentSections.includes(String(s.id));
                    $('#psi_sections').append(`<option value="${s.id}" ${isSelected ? 'selected' : ''}>${s.name} (${s.arrival_location ? s.arrival_location.name : ''})</option>`);
                    if (isSelected) {
                        validSections.push(String(s.id));
                    }
                });

            $('#psi_sections').val(validSections).trigger('change.select2');
        }

        $('#psi_locations').on('change', function () {
            isInitialLoad = false;
            populateFactories();
            populateSections();
        });

        $('#psi_factories').on('change', function () {
            isInitialLoad = false;
            populateSections();
        });

        populateFactories();
        populateSections();
        isInitialLoad = false;
        updatePsiTotalWeight();
    });

    function addPsiItemRow() {
        const template = document.getElementById('psiItemRowTemplate');
        const clone = template.content.cloneNode(true);
        $('#psiItemsBody').append(clone);
        const $newRow = $('#psiItemsBody tr.psi-item-row:last');
        $newRow.find('.psi-item-select').select2();
        updatePsiTotalWeight();
    }

    function removePsiItemRow(btn) {
        if ($('#psiItemsBody tr.psi-item-row').length > 1) {
            $(btn).closest('tr').remove();
            updatePsiTotalWeight();
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.warning('At least one item row is required.');
            } else {
                alert('At least one item row is required.');
            }
        }
    }

    function updatePsiTotalWeight() {
        let total = 0;
        $('.psi-item-weight').each(function () {
            const val = parseFloat($(this).val());
            if (!isNaN(val)) {
                total += val;
            }
        });
        $('#psi_total_weight').text(total.toFixed(2));
    }

    $(document).on('input change', '.psi-item-weight', function () {
        updatePsiTotalWeight();
    });
</script>
