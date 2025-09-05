@foreach ($dataItems ?? [] as $key => $data)
    @php
        $quotedData = $data?->approved_purchase_quotation;
        $hasApprovedQuotation = !empty($quotedData);

        $quotedRate = $quotedData->rate ?? '';
        $quotedQty = $quotedData->qty ?? 0;
        $quotedTotal = $quotedRate && $quotedQty ? $quotedRate * $quotedQty : '';
        $quotedSupplierId = $quotedData->supplier_id ?? '';
        $quotedSupplierName = $quotedData->supplier->name ?? '';
        $currentRate = $quotedRate;
        $currentQty = $data->qty ?? $quotedQty;
        $currentTotal = $currentRate && $currentQty ? $currentRate * $currentQty : '';
        $currentSupplierId = $quotedSupplierId;
        $currentSupplierName = $quotedSupplierName;

        $totalOrdered = $data->purchase_order_data->sum('qty') ?? 0;
        $remainingQty = $data->qty - $totalOrdered;
    @endphp

    <tr id="row_{{ $key }}">
        <td style="width: 5%">
            <input type="checkbox" name="use_quotation[]" id="use_quotation_{{ $key }}" value="{{ $key }}"
                class="quotation-checkbox" {{ $hasApprovedQuotation ? 'checked' : '' }}
                {{ !$hasApprovedQuotation ? 'disabled' : '' }} onchange="toggleQuotationFields({{ $key }})">

            <input type="hidden" id="quoted_supplier_id_{{ $key }}" value="{{ $quotedSupplierId }}">
            <input type="hidden" id="quoted_supplier_name_{{ $key }}" value="{{ $quotedSupplierName }}">
            <input type="hidden" id="quoted_qty_{{ $key }}" value="{{ $quotedQty }}">
            <input type="hidden" id="quoted_rate_{{ $key }}" value="{{ $quotedRate }}">
            <input type="hidden" id="quoted_total_{{ $key }}" value="{{ $quotedTotal }}">

            @if ($hasApprovedQuotation)
                <input type="hidden" name="quotation_ids[]" value="{{ $quotedData->id }}">
            @else
                <input type="hidden" name="quotation_ids[]" value="">
            @endif
        </td>

        <td style="width: 20%">
            <select id="category_id_{{ $key }}" onchange="filter_items(this.value,{{ $key }})"
                class="form-control item-select select2" data-index="{{ $key }}" disabled>
                <option value="">Select Category</option>
                @foreach ($categories ?? [] as $category)
                    <option {{ $category->id == $data->category_id ? 'selected' : '' }} value="{{ $category->id }}">
                        {{ $category->name }}</option>
                @endforeach
            </select>
            <input type="hidden" name="category_id[]" value="{{ $data->category_id }}">
            <input type="hidden" name="purchase_request_data_id[]" value="{{ $data->id }}">
            <input type="hidden" name="purchase_quotation_data_id[]" value="{{ $quotedData->id ?? '' }}">
        </td>

        <td style="width: 20%">
            <select id="item_id_{{ $key }}" onchange="get_uom({{ $key }})"
                class="form-control item-select select2" data-index="{{ $key }}" disabled>
                @foreach (get_product_by_category($data->category_id) as $item)
                    <option data-uom="{{ $item->unitOfMeasure->name ?? '' }}" value="{{ $item->id }}"
                        {{ $item->id == $data->item_id ? 'selected' : '' }}>
                        {{ $item->name }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="item_id[]" value="{{ $data->item_id }}">
        </td>

        <td style="width: 15%">
            <input type="text" name="uom[]" value="{{ get_uom($data->item_id) }}" id="uom_{{ $key }}"
                class="form-control uom" readonly>
        </td>

        <td style="width: 15%" id="vendor_dropdown_{{ $key }}">
            <select name="supplier_id_dropdown[]" id="supplier_id_{{ $key }}"
                class="form-control item-select select2" data-index="{{ $key }}"
                {{ $hasApprovedQuotation ? 'disabled' : '' }} onchange="updateVendorInput({{ $key }})">
                <option value="">Select Vendor</option>
                @foreach (get_supplier() as $supplier)
                    <option value="{{ $supplier->id }}" {{ $supplier->id == $currentSupplierId ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="supplier_id[]" id="supplier_id_hidden_{{ $key }}"
                value="{{ $currentSupplierId }}">
        </td>

        <td style="width: 15%; display: none;" id="vendor_input_{{ $key }}">
            <input type="text" name="supplier_input[]" id="supplier_input_{{ $key }}"
                value="{{ $currentSupplierName }}" class="form-control" readonly>
        </td>

        <td style="width: 10%">
            <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                onblur="calc({{ $key }})" name="qty[]" value="{{ $remainingQty }}"
                id="qty_{{ $key }}" class="form-control" step="0.01" min="0"
                max="{{ $remainingQty }}" {{ $hasApprovedQuotation ? 'readonly' : '' }}>

            <div class="d-flex align-items-center">
                Total Qty: {{ $data->qty }}
                <input style="width: 50px" value="" class="form-control d-none" disabled>
            </div>
            <div class="d-flex align-items-center">
                Ordered Qty: {{ $totalOrdered }}
                <input style="width: 50px" value="{{ $totalOrdered }}" class="form-control d-none" disabled>
            </div>
        </td>

        <td style="width: 20%">
            <input style="width: 100px" type="number" onkeyup="calc({{ $key }})"
                onblur="calc({{ $key }})" name="rate[]" value="{{ $currentRate }}"
                id="rate_{{ $key }}" class="form-control" step="0.01" min="0"
                {{ $hasApprovedQuotation ? 'readonly' : '' }}>
        </td>

        <td style="width: 20%">
            <input style="width: 100px" type="number" readonly name="total[]" value="{{ $currentTotal }}"
                id="total_{{ $key }}" class="form-control" step="0.01" min="0">
        </td>

        <td style="width: 25%">
            <input style="width: 100px" type="text" name="remarks[]" value="{{ $data->remarks }}"
                id="remark_{{ $key }}" class="form-control">
        </td>

        <td>
            <button type="button" class="btn btn-danger btn-sm removeRowBtn" onclick="remove({{ $key }})"
                data-id="{{ $key }}">Remove</button>
        </td>
    </tr>
@endforeach

<script>
    function toggleQuotationFields(key) {
        const checkbox = document.getElementById('use_quotation_' + key);
        const isChecked = checkbox.checked;

        const supplierDropdown = document.getElementById('supplier_id_' + key);
        const supplierHidden = document.getElementById('supplier_id_hidden_' + key);
        const supplierInput = document.getElementById('supplier_input_' + key);
        const qtyInput = document.getElementById('qty_' + key);
        const rateInput = document.getElementById('rate_' + key);
        const totalInput = document.getElementById('total_' + key);
        const vendorDropdownContainer = document.getElementById('vendor_dropdown_' + key);
        const vendorInputContainer = document.getElementById('vendor_input_' + key);

        const quotedSupplierId = document.getElementById('quoted_supplier_id_' + key).value;
        const quotedSupplierName = document.getElementById('quoted_supplier_name_' + key).value;
        const quotedQty = document.getElementById('quoted_qty_' + key).value;
        const quotedRate = document.getElementById('quoted_rate_' + key).value;
        const quotedTotal = document.getElementById('quoted_total_' + key).value;

        if (isChecked) {
            supplierDropdown.value = quotedSupplierId;
            supplierHidden.value = quotedSupplierId;
            qtyInput.value = quotedQty;
            rateInput.value = quotedRate;
            totalInput.value = quotedTotal;

            supplierDropdown.disabled = true;
            qtyInput.readOnly = true;
            rateInput.readOnly = true;

            vendorDropdownContainer.style.display = 'none';
            vendorInputContainer.style.display = 'table-cell';

            supplierInput.value = quotedSupplierName;

        } else {
            supplierDropdown.disabled = false;
            qtyInput.readOnly = false;
            rateInput.readOnly = false;

            vendorDropdownContainer.style.display = 'table-cell';
            vendorInputContainer.style.display = 'none';

            supplierHidden.value = supplierDropdown.value;
        }

        if (typeof $(supplierDropdown).select2 !== 'undefined') {
            $(supplierDropdown).select2();
        }
    }

    function updateVendorInput(key) {
        const supplierDropdown = document.getElementById('supplier_id_' + key);
        const supplierHidden = document.getElementById('supplier_id_hidden_' + key);
        const supplierInput = document.getElementById('supplier_input_' + key);

        const selectedOption = supplierDropdown.options[supplierDropdown.selectedIndex];
        const supplierName = selectedOption?.text || '';
        const supplierId = supplierDropdown.value;

        supplierHidden.value = supplierId;
        supplierInput.value = supplierName;
    }

    document.addEventListener('DOMContentLoaded', function() {
        @foreach ($dataItems ?? [] as $key => $data)
            @if (!empty($data?->approved_purchase_quotation))
                document.getElementById('vendor_dropdown_{{ $key }}').style.display = 'none';
                document.getElementById('vendor_input_{{ $key }}').style.display = 'table-cell';

                const quotedSupplierName = document.getElementById('quoted_supplier_name_{{ $key }}')
                    .value;
                document.getElementById('supplier_input_{{ $key }}').value = quotedSupplierName;
            @else
                document.getElementById('supplier_id_{{ $key }}').disabled = false;
            @endif

            const supplierDropdown = document.getElementById('supplier_id_{{ $key }}');
            const supplierHidden = document.getElementById('supplier_id_hidden_{{ $key }}');
            supplierHidden.value = supplierDropdown.value;
        @endforeach
    });
</script>
