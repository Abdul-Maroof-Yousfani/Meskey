<style>
    html,
    body {
        overflow-x: hidden;
    }

    .amount-info-box {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .amount-info-box .form-group {
        margin-bottom: 10px;
    }

    .amount-info-box .form-group:last-child {
        margin-bottom: 0;
    }

    .amount-info-box .form-label {
        font-weight: 600;
        font-size: 13px;
    }
</style>

<form action="{{ route('sales.sales-inquiry.update', ['sales_inquiry' => $sales_inquiry->id]) }}" method="POST"
    id="ajaxSubmit" autocomplete="off">
    @csrf
    {{ method_field('PUT') }}

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.sales-inquiry.list') }}" />
    @php
        $selectedFactories = $sales_inquiry->factories?->pluck('arrival_location_id')->toArray() ?? [];
        if (empty($selectedFactories) && $sales_inquiry->arrival_location_id) {
            $selectedFactories = [$sales_inquiry->arrival_location_id];
        }
        $selectedSections = $sales_inquiry->sections?->pluck('arrival_sub_location_id')->toArray() ?? [];
        if (empty($selectedSections) && $sales_inquiry->arrival_sub_location_id) {
            $selectedSections = [$sales_inquiry->arrival_sub_location_id];
        }
        $oldFactories = old('arrival_location_id', $selectedFactories);
        $oldSections = old('arrival_sub_location_id', $selectedSections);
    @endphp
    <div class="row form-mar">
        <div class="col-md-12">
            <div class="row">
                <div class="col-12">
                    <h6 class="header-heading-sepration">General Information</h6>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Inquiry Number: <span class="text-danger">*</span></label>
                        <input type="text" name="reference_no" id="reference_no" value="{{ $sales_inquiry->inquiry_no }}"
                            class="form-control" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Inquiry Date: <span class="text-danger">*</span></label>
                        <input type="date" name="inquiry_date" onchange="getNumber(); validateExpiry()" id="inquiry_date"
                            value="{{ $sales_inquiry->date }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Contract Type: <span class="text-danger">*</span></label>
                        <select name="contract_type" id="contract_type" class="form-control select2">
                            <option value="">Select Contract Type</option>
                             <option value="x-mill" @selected($sales_inquiry->contract_type == 'x-mill')>X-Mill</option>
                             <option value="pohanch" @selected($sales_inquiry->contract_type == 'pohanch')>Pohanch</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Delivery Date: <span class="text-danger">*</span></label>
                        <input type="date" name="required_date" id="required_date" onchange="validateExpiry()"
                            value="{{ $sales_inquiry->required_date }}" class="form-control">
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Customer Details</h6>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Customer: <span class="text-danger">*</span></label>
                        <select name="customer" id="customer" class="form-control select2">
                            <option value="">Select Customer</option>
                            @foreach ($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" @selected($customer->id == $sales_inquiry->customer)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Contact Person:</label>
                        <input type="text" name="contact_person" id="contact_person"
                            value="{{ $sales_inquiry->contact_person }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="reference_number" id="reference_number"
                            value="{{ $sales_inquiry->reference_number }}" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Token Money:</label>
                        <input type="number" name="token_money" id="token_money" value="{{ $sales_inquiry->token_money }}" class="form-control" step="0.01" min="0">
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Location Details</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Locations: <span class="text-danger">*</span></label>
                        <select name="locations[]" id="locations" class="form-control select2" multiple>
                            @foreach (get_locations() as $location)
                                <option value="{{ $location->id }}" @selected(in_array($location->id, $sales_inquiry->locations->pluck('location_id')->toArray()))>{{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Factory:</label>
                        <select name="arrival_location_id[]" id="arrival_location_id" class="form-control select2" multiple>
                            @foreach ($arrivalLocations as $factory)
                                <option value="{{ $factory->id }}" data-company="{{ $factory->company_location_id }}" @selected(in_array($factory->id, $oldFactories))>{{ $factory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Section:</label>
                        <select name="arrival_sub_location_id[]" id="arrival_sub_location_id" class="form-control select2" multiple>
                            @foreach ($arrivalSubLocations as $section)
                                <option value="{{ $section->id }}" data-factory="{{ $section->arrival_location_id }}" @selected(in_array($section->id, $oldSections))>{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Remarks</h6>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <textarea name="remarks" id="remarks" class="form-control" rows="3">{{ $sales_inquiry->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        {{-- <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn">
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div> --}}

        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="salesInquiryTable" style="min-width: 2000px;">
                    <thead>
                        <tr>
                            <th class="required col-3">Item</th>
                            <th class="required col-1">Bag Type</th>
                            <th class="required col-1">Packing</th>
                            <th class="required col-1">No of Bags</th>
                            <th class="required col-1">Minimum Qty (kg)</th>
                            <th class="required col-1">Maximum Qty (kg)</th>
                            <th class="required col-1">Rate per Kg</th>
                            <th class="required col-1">Rate per Mond</th>
                            <th class="required col-1">Brands</th>
                            <th style="display: none;">Pack Size</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody id="salesInquiryBody">
                        @php
                            $i = 0;
                        @endphp
                        @foreach ($sales_inquiry->sales_inquiry_data as $index => $data)
                            <tr id="row_{{ $index }}">
                                <td>
                                    <select name="item_id[]" id="item_id_{{ $i }}"
                                        class="form-control select2">
                                        <option value="">Select Item</option>
                                        @foreach ($items ?? [] as $item)
                                            <option value="{{ $item->id }}" @selected($data->item_id == $item->id)>
                                                {{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="bag_type[]" id="bag_type_{{ $i }}" class="form-control select2">
                                        <option value="">Select Bag Type</option>
                                        @foreach ($bag_types ?? [] as $bag_type)
                                            <option value="{{ $bag_type->id }}" @selected($bag_type->id == $data->bag_type)>
                                                {{ $bag_type->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="bag_size[]" id="bag_size_{{ $i }}"
                                        class="form-control bag_size select2" onchange="calc(this)">
                                        <option value="">Select Packing</option>
                                        @foreach ($packings as $packing)
                                            <option value="{{ $packing }}"
                                                @selected($data->bag_size == $packing)>{{ $packing }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                <input type="text" name="no_of_bags[]" id="no_of_bags_{{ $i }}"
                                        value="{{ $data->no_of_bags }}" class="form-control no_of_bags"
                                        readonly>
                                </td>
                                <td>
                                    <input type="number" name="minimum_qty[]" id="minimum_qty_{{ $i }}"
                                        value="{{ round($data->minimum_qty) }}" class="form-control minimum_qty" step="0.01" min="0">
                                </td>
                                <td>
                                    <input type="number" name="qty[]" id="qty_{{ $i }}"
                                        value="{{ round($data->qty ?? ($data->bag_size * $data->no_of_bags) ) }}" class="form-control qty" step="0.01"
                                        min="0" onkeyup="calc(this)" onchange="calc(this)">
                                </td>
                                <td>
                                    <input onkeyup="calculateRates(this)" type="number" name="rate[]" id="rate_{{ $i }}"
                                        value="{{ $data->rate }}" class="form-control rate_per_kg" step="0.01"
                                        min="0">
                                </td>

                                <td>
                                    <input onkeyup="calculateRates(this)" type="number" name="rate_per_mond[]" id="rate_{{ $i }}"
                                        value="{{ $data->rate_per_mond }}" class="form-control rate_per_mond" step="0.01"
                                        min="0">
                                </td>

                                <td>
                                    <select name="brand_id[]" id="brand_id_{{ $i }}" class="form-control select2">
                                        <option value="">Select Brand</option>
                                        @foreach (getAllBrands() ?? [] as $brand)
                                            <option value="{{ $brand->id }}" @selected($data->brand_id == $brand->id)>
                                                {{ $brand->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="display: none;">
                                    <input type="text" name="pack_size[]" value="{{ $data->pack_size }}"
                                        id="pack_size_{{ $i }}" value="0" class="form-control" step="0.01" min="0"
                                        >
                                </td>

                                <td>
                                    <input type="text" name="desc[]" id="desc_{{ $i }}"
                                        value="{{ $data->description }}" class="form-control">
                                </td>
                            </tr>
                            @php
                                $i++;
                            @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <input type="hidden" id="rowCount" value="0">

    @php
        $siModule = $sales_inquiry->getApprovalModule();
        $siApprovalLogs = $siModule ? \App\Models\ApprovalsModule\ApprovalLog::where('record_id', $sales_inquiry->id)->where('module_id', $siModule->id)->with(['user', 'role'])->orderBy('created_at', 'desc')->get() : collect();
    @endphp

    @if ($siApprovalLogs->isNotEmpty())
        <style>
            .current-status-tag {
                display: inline-flex;
                align-items: center;
                gap: 3px;
                font-size: 9px;
                font-weight: 700;
                color: #047857;
                background-color: #ecfdf5;
                border: 1px solid #a7f3d0;
                padding: 1px 7px;
                border-radius: 10px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                line-height: 1.4;
            }
            .current-status-dot {
                width: 5px;
                height: 5px;
                background-color: #10b981;
                border-radius: 50%;
                display: inline-block;
            }
        </style>
        <div class="approval-table-wrapper mx-2" style="margin-top: 15px; margin-bottom: 25px;">
            <div class="card border" style="box-shadow: none; margin-bottom: 0 !important;">
                <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-dark fw-bold" style="font-size: 14px;">
                        Approval History & Comments
                    </h6>
                    <span class="badge badge-info">{{ $siApprovalLogs->count() }} {{ \Illuminate\Support\Str::plural('Action', $siApprovalLogs->count()) }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0" style="font-size: 13px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">#</th>
                                    <th style="min-width: 160px; width: 22%;">User</th>
                                    <th style="min-width: 150px; width: 18%;" class="text-center">Action</th>
                                    <th style="min-width: 160px; width: 20%;">Date & Time</th>
                                    <th style="min-width: 300px; width: 40%;">Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($siApprovalLogs as $index => $log)
                                    @php
                                        $badgeClass = match($log->action) {
                                            'approved' => 'badge-success',
                                            'rejected' => 'badge-danger',
                                            'reverted' => 'badge-warning',
                                            'partial_approved' => 'badge-info',
                                            default => 'badge-secondary'
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-center align-middle">{{ $index + 1 }}</td>
                                        <td class="align-middle">
                                            <strong>{{ $log->user->name ?? 'N/A' }}</strong>
                                            @if ($log->user_id === auth()->id())
                                                <span class="badge badge-primary ms-1" style="font-size: 10px;">You</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge {{ $badgeClass }} text-uppercase px-2 py-1" style="font-size: 11px;">
                                                {{ str_replace('_', ' ', $log->action) }}
                                            </span>
                                            @if ($loop->first)
                                                <div class="mt-1">
                                                    <span class="current-status-tag">
                                                        <span class="current-status-dot"></span> Current
                                                    </span>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="align-middle">
                                            {{ $log->created_at ? $log->created_at->format('d M, Y h:i A') : 'N/A' }}
                                        </td>
                                        <td class="align-middle">
                                            @if (!empty(trim($log->comments ?? '')))
                                                <span class="text-dark">{{ $log->comments }}</span>
                                            @else
                                                <span class="text-muted fst-italic">No comments</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
                class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>

<script>
    salesInquiryRowIndex = {{ $i }};

    function calculateForRatePerKg(mond) {
        return mond / 40;
    }

    function calculateForRatePerMond(kg) {
        return kg * 40;
    }

    function calculateRates(el) {
        if(!$(el).val()) {
            return;
        }

        if($(el).hasClass("rate_per_kg")) {
            $(el).closest("tr").find(".rate_per_mond").val(calculateForRatePerMond($(el).val()));
        } else {
            $(el).closest("tr").find(".rate_per_kg").val(calculateForRatePerKg($(el).val()));
        }
    }

    $(document).ready(function() {
        $('.select2').select2();

        const factories = @json($arrivalLocations);
        const sections = @json($arrivalSubLocations);
        const initialFactories = @json($oldFactories ?? []);
        const initialSections = @json($oldSections ?? []);

        function populateFactories() {
            const selectedLocations = $('#locations').val() || [];
            const currentValues = $('#arrival_location_id').val() || initialFactories;
            $('#arrival_location_id').empty();

            factories
                .filter(f => selectedLocations.length === 0 || selectedLocations.includes(String(f.company_location_id)))
                .forEach(f => {
                    $('#arrival_location_id').append(`<option value="${f.id}" data-company="${f.company_location_id}">${f.name} (${f.company_location.name})</option>`);
                });

            $('#arrival_location_id').val(currentValues).trigger('change.select2');
        }

        function populateSections() {
            const factoryIds = $('#arrival_location_id').val() || initialFactories;
            const currentSections = $('#arrival_sub_location_id').val() || initialSections;
            $('#arrival_sub_location_id').empty();

            sections
                .filter(s => factoryIds.length === 0 || factoryIds.includes(String(s.arrival_location_id)))
                .forEach(s => {
                    $('#arrival_sub_location_id').append(`<option value="${s.id}" data-factory="${s.arrival_location_id}">${s.name} (${s.arrival_location.name})</option>`);
                });

            $('#arrival_sub_location_id').val(currentSections).trigger('change.select2');
        }

        $('#locations').on('change', function() {
            populateFactories();
            populateSections();
        });

        let isInitialLoad = true;

        $('#customer').on('change', function() {
            const customerId = $(this).val();
            let selectedLocations = $('#locations').val() || [];
            
            $('#locations, #arrival_location_id, #arrival_sub_location_id').prop('disabled', true).trigger('change.select2');

            if (!isInitialLoad) {
                $('#locations').empty().trigger('change.select2');
                $('#arrival_location_id').empty().trigger('change.select2');
                $('#arrival_sub_location_id').empty().trigger('change.select2');
                selectedLocations = [];
            }

            if (customerId) {
                if (!isInitialLoad) {
                    Swal.fire({
                        title: 'Fetching Locations...',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading()
                        }
                    });
                }
                $.ajax({
                    url: "{{ route('sales.get-customer-locations') }}",
                    method: "GET",
                    data: { customer_id: customerId },
                    success: function(res) {
                        $('#locations').empty();
                        res.forEach(loc => {
                            const isSelected = selectedLocations.includes(String(loc.id));
                            $('#locations').append(`<option value="${loc.id}" ${isSelected ? 'selected' : ''}>${loc.name}</option>`);
                        });
                        $('#locations').trigger('change.select2');
                        populateFactories();
                        populateSections();
                        isInitialLoad = false;
                        if (Swal.isVisible()) {
                            Swal.close();
                        }
                    },
                    complete: function() {
                        $('#locations, #arrival_location_id, #arrival_sub_location_id').prop('disabled', false).trigger('change.select2');
                        if (Swal.isVisible()) {
                            Swal.close();
                        }
                    }
                });
            } else {
                $('#locations').empty().trigger('change.select2');
                $('#arrival_location_id').empty().trigger('change.select2');
                $('#arrival_sub_location_id').empty().trigger('change.select2');
                $('#locations, #arrival_location_id, #arrival_sub_location_id').prop('disabled', false).trigger('change.select2');
                if (!isInitialLoad) {
                    isInitialLoad = false;
                }
            }
        });

        $('#arrival_location_id').on('change', function() {
            populateSections();
        });

        populateFactories();
        populateSections();
        $('#customer').trigger('change');
        validateExpiry();
    });

    function calc(el) {
        const element = $(el).closest("tr");
        const bag_size = parseFloat($(element).find(".bag_size").val());
        const qty = parseFloat($(element).find(".qty").val());
        const no_of_bags = $(element).find(".no_of_bags");

        if (isNaN(bag_size) || isNaN(qty)) {
            no_of_bags.val('');
            return;
        }

        // No of bags = bag size * quantity (per requirement)
        const result = (qty / bag_size).toFixed();
        no_of_bags.val(result);
    }

    function validateExpiry() {
        const inquiryDate = $('#inquiry_date').val();
        const requiredDate = $('#required_date').val();

        if (inquiryDate && requiredDate) {
            const reqYear = parseInt(requiredDate.split('-')[0]);
            if (reqYear < 1000) return; // Wait until a full year is entered

            if (inquiryDate > requiredDate) {
                $('#required_date').val('');
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date!',
                    text: 'Delivery date cannot be earlier than inquiry date.',
                    confirmButtonText: 'OK'
                });
            }
        }
    }

    function addRow() {
        let index = salesInquiryRowIndex++;
        let row = `
        <tr id="row_${index}">
            <td>
                <select name="item_id[]" id="item_id_${index}" class="form-control select2">
                    <option value="">Select Item</option>
                    @foreach ($items ?? [] as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="bag_type[]" id="bag_type_${index}" class="form-control select2">
                    <option value="">Select Bag Type</option>
                    @foreach ($bag_types ?? [] as $bag_type)
                        <option value="{{ $bag_type->id }}">{{ $bag_type->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="bag_size[]" id="bag_size_${index}" class="form-control bag_size select2" onchange="calc(this)">
                    <option value="">Select Packing</option>
                    @foreach ($packings as $packing)
                        <option value="{{ $packing }}">{{ $packing }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="text" name="no_of_bags[]" id="no_of_bags_${index}" class="form-control no_of_bags" readonly>
            </td>
            <td>
                <input type="number" name="minimum_qty[]" id="minimum_qty_${index}" class="form-control minimum_qty" step="0.01" min="0">
            </td>
            <td>
                <input type="number" name="qty[]" id="qty_${index}" class="form-control qty" step="0.01" min="0" onkeyup="calc(this)" onchange="calc(this)">
            </td>
            <td>
                <input onkeyup="calculateRates(this)" type="number" name="rate[]" id="rate_${index}" class="form-control rate_per_kg" step="0.01" min="0">
            </td>
            <td>
                <input onkeyup="calculateRates(this)" type="number" name="rate_per_mond[]" id="rate_per_mond_${index}" class="form-control rate_per_mond" step="0.01" min="0">
            </td>
            <td>
                <select name="brand_id[]" id="brand_id_${index}" class="form-control select2">
                    <option value="">Select Brand</option>
                    @foreach (getAllBrands() ?? [] as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
            </td>
            <td style="display: none;">
                <input type="text" name="pack_size[]" id="pack_size_${index}" value="0" class="form-control" step="0.01" min="0">
            </td>
            <td>
                <input type="text" name="desc[]" id="desc_${index}" class="form-control">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="removeRow(${index})" style="width:60px;">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
        $('#salesInquiryBody').append(row);
        $(`#item_id_${index}`).select2();
        $(`#bag_type_${index}`).select2();
        $(`#bag_size_${index}`).select2();
        $(`#brand_id_${index}`).select2();
    }

    function removeRow(index) {
        $('#row_' + index).remove();
    }

    function getNumber() {
        $.ajax({
            url: "{{ route('sales.get.sales-number') }}",
            method: "GET",
            data: {
                contract_date: $("#inquiry_date").val()
            },
            dataType: "json",
            success: function(res) {
                $("#reference_no").val(res.inquiry_no)
            },
            error: function(error) {
                // Handle errors here
                $('.loader-container').hide();
                console.error("Error:", error);
            }
        });
    }
</script>
