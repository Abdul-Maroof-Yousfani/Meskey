<style>
    html, body {
        overflow-x: hidden;
    }
</style>

<form action="{{ route('sales.pre-sale-inspection.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.pre-sale-inspection.list') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="row">
                {{-- General Information --}}
                <div class="col-12">
                    <h6 class="header-heading-sepration">General Information</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Location: <span class="text-danger">*</span></label>
                        <select name="location_id" id="psi_location_id" class="form-control select2" required>
                            <option value="">Select Location</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Inspection Number: <span class="text-danger">*</span></label>
                        <input type="text" name="inspection_no" id="inspection_no" class="form-control font-weight-bold" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Inspection Date: <span class="text-danger">*</span></label>
                        <input type="date" name="date" id="inspection_date" onchange="getInspectionNumber()"
                            class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                {{-- Party Details --}}
                <div class="col-12 mt-2">
                    <h6 class="header-heading-sepration">Party Details</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Party Name: <span class="text-danger">*</span></label>
                        <input type="text" name="party_name" id="party_name" class="form-control" placeholder="Enter Party Name" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Party Contact No:</label>
                        <input type="text" name="party_contact_no" id="party_contact_no" class="form-control" placeholder="Enter Contact No">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Reference:</label>
                        <input type="text" name="reference" id="reference" class="form-control" placeholder="Enter Reference">
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
                                    <th style="width: 28%;">Item (Product) <span class="text-danger">*</span></th>
                                    <th style="width: 25%;">Factory</th>
                                    <th style="width: 25%;">Section</th>
                                    <th style="width: 14%;">Weight (kg) <span class="text-danger">*</span></th>
                                    <th style="width: 8%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="psiItemsBody">
                                <tr class="psi-item-row">
                                    <td>
                                        <select name="item_id[]" class="form-control select2 psi-item-select" required>
                                            <option value="">Select Item (Product)</option>
                                            @foreach ($items ?? [] as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="arrival_location_id[]" class="form-control select2 psi-factory-select">
                                            <option value="">Select Factory</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="arrival_sub_location_id[]" class="form-control select2 psi-section-select">
                                            <option value="">Select Section</option>
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
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="3" class="text-right">Total Weight:</td>
                                    <td>
                                        <span id="psi_total_weight" class="text-primary font-medium-1">0.00</span> kg
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                {{-- Remarks --}}
                <div class="col-12 mt-2">
                    <h6 class="header-heading-sepration">Remarks</h6>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter inspection remarks or notes..."></textarea>
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
                <select name="arrival_location_id[]" class="form-control psi-factory-select">
                    <option value="">Select Factory</option>
                </select>
            </td>
            <td>
                <select name="arrival_sub_location_id[]" class="form-control psi-section-select">
                    <option value="">Select Section</option>
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
            <button type="button" class="btn btn-primary submitbutton" id="btnPsiSubmit" onclick="submitPsiCreateForm()">Save</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function () {
        $('.select2').select2();

        const allFactories = @json($arrivalLocations);
        const allSections = @json($arrivalSubLocations);

        window.psiAllFactories = allFactories;
        window.psiAllSections = allSections;

        function populateRowFactories($row, selectedFactoryId = null) {
            const locationId = $('#psi_location_id').val();
            const $factorySelect = $row.find('.psi-factory-select');
            const prevVal = selectedFactoryId !== null ? selectedFactoryId : $factorySelect.val();

            $factorySelect.empty().append('<option value="">Select Factory</option>');

            if (!locationId) {
                $factorySelect.val('').trigger('change.select2');
                populateRowSections($row);
                return;
            }

            const filtered = allFactories.filter(f => String(f.company_location_id) === String(locationId));
            filtered.forEach(f => {
                $factorySelect.append(`<option value="${f.id}">${f.name}</option>`);
            });

            if (prevVal && filtered.some(f => String(f.id) === String(prevVal))) {
                $factorySelect.val(prevVal);
            } else {
                $factorySelect.val('');
            }
            $factorySelect.trigger('change.select2');
            populateRowSections($row);
        }

        function populateRowSections($row, selectedSectionId = null) {
            const factoryId = $row.find('.psi-factory-select').val();
            const $sectionSelect = $row.find('.psi-section-select');
            const prevVal = selectedSectionId !== null ? selectedSectionId : $sectionSelect.val();

            $sectionSelect.empty().append('<option value="">Select Section</option>');

            if (!factoryId) {
                $sectionSelect.val('').trigger('change.select2');
                return;
            }

            const filtered = allSections.filter(s => String(s.arrival_location_id) === String(factoryId));
            filtered.forEach(s => {
                $sectionSelect.append(`<option value="${s.id}">${s.name}</option>`);
            });

            if (prevVal && filtered.some(s => String(s.id) === String(prevVal))) {
                $sectionSelect.val(prevVal);
            } else {
                $sectionSelect.val('');
            }
            $sectionSelect.trigger('change.select2');
        }

        window.populateRowFactories = populateRowFactories;
        window.populateRowSections = populateRowSections;

        $('#psi_location_id').on('change', function () {
            $('#psiItemsBody tr.psi-item-row').each(function () {
                populateRowFactories($(this));
            });
        });

        $(document).on('change', '.psi-factory-select', function () {
            const $row = $(this).closest('tr');
            populateRowSections($row);
        });

        getInspectionNumber();
    });

    function addPsiItemRow() {
        const template = document.getElementById('psiItemRowTemplate');
        const clone = template.content.cloneNode(true);
        $('#psiItemsBody').append(clone);
        const $newRow = $('#psiItemsBody tr.psi-item-row:last');
        if (typeof window.populateRowFactories === 'function') {
            window.populateRowFactories($newRow);
        }
        $newRow.find('select').select2();
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

    function getInspectionNumber(callback = null) {
        $.ajax({
            url: "{{ route('sales.get.pre-sale-inspection-number') }}",
            method: "GET",
            data: {
                inspection_date: $("#inspection_date").val()
            },
            dataType: "json",
            success: function (res) {
                if (res && res.inspection_no) {
                    $("#inspection_no").val(res.inspection_no);
                }
                if (typeof callback === 'function') {
                    callback(res ? res.inspection_no : null);
                }
            },
            error: function (error) {
                console.error("Error generating Inspection number:", error);
                if (typeof callback === 'function') {
                    callback(null);
                }
            }
        });
    }

    function submitPsiCreateForm() {
        const form = document.getElementById('ajaxSubmit');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const $btn = $('#btnPsiSubmit');
        $btn.prop('disabled', true);

        // Always refresh the inspection number from the server right before submit to ensure uniqueness
        getInspectionNumber(function () {
            $btn.prop('disabled', false);
            $('#ajaxSubmit').trigger('submit');
        });
    }

    // Auto-refresh inspection number when the tab or window regains focus
    $(window).on('focus', function () {
        if ($('#inspection_no').length) {
            getInspectionNumber();
        }
    });
</script>
