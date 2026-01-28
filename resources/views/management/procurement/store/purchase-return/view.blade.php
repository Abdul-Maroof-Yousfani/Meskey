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

<form action="{{ route('store.purchase-return.store') }}" method="POST" id="ajaxSubmit2" autocomplete="off">
    @csrf

    <input type="hidden" id="listRefresh" value="{{ route('store.get.purchase-return') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <!-- Row 1: Supplier, Purchase Bill, PR No -->
            <div class="row" style="margin-top: 10px">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Supplier:</label>
                        <select name="supplier_id" id="supplier_id" class="form-control select2" disabled>
                            <option value="">Select Supplier</option>
                            @foreach (\App\Models\Master\Supplier::all() ?? [] as $supplier)
                                <option value="{{ $supplier->id }}" @selected($purchaseReturn->supplier_id == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Purchase Bill:</label>
                        <select name="purchase_bill_id" id="purchase_bill_id" class="form-control select2" disabled multiple>
                            <option value="">Select Purchase Bill</option>
                            @foreach ($purchaseReturn->purchaseBills as $bill)
                                <option value="{{ $bill->id }}" selected>{{ $bill->bill_no }} - {{ $bill->supplier->name ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">PR No:</label>
                        <input type="text" name="pr_no" id="pr_no" class="form-control"
                            value="{{ $purchaseReturn->pr_no }}" readonly>
                    </div>
                </div>
            </div>

            <!-- Row 2: Date, Reference Number, Company Location -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Date:</label>
                        <input type="date" name="date" id="date"
                            value="{{ $purchaseReturn->date }}" class="form-control" readonly>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="reference_no" value="{{ $purchaseReturn->reference_no }}"
                            id="reference_no" class="form-control" placeholder="Enter reference number" readonly>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Company Location:</label>
                        <select name="company_location_id" id="company_location_id" class="form-control select2" disabled>
                            <option value="">Select Company Location</option>
                            @foreach (get_locations() ?? [] as $location)
                                <option value="{{ $location->id }}" @selected($purchaseReturn->company_location_id == $location->id)>{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Row 3: Remarks -->
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label">Remarks:</label>
                        <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="Enter remarks" readonly>{{ $purchaseReturn->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-12 text-right mb-2">
            <button type="button" style="float: right" class="btn btn-sm btn-primary" onclick="addRow()"
                id="addRowBtn" disabled>
                <i class="fa fa-plus"></i>&nbsp; Add New Item
            </button>
        </div>

        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="purchaseBillTable" style="min-width:2000px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Gross Amount</th>
                            <th>Disc %</th>
                            <th>Disc Amount</th>
                            <th>Deduction</th>
                            <th>Amount</th>
                            <th>GST %</th>
                            <th>GST Amount</th>
                            <th>Net Amount</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="pbTableBody">
                        @php
                            $rowIndex = 0;
                        @endphp
                        @foreach ($purchaseReturn->purchase_return_data as $data)
                            @php
                                $purchase_return_data = $data;
                                $data = $data->purchase_bill_data;
                                $packing = $data->packing ?? 0;
                                $quantity = $purchase_return_data->quantity;
                                $rate = $data->rate ?? 0;
                                $grossAmount = $purchase_return_data->gross_amount;
                                $taxPercent = $data->tax_percent ?? 0;
                                $discountPercent = $purchase_return_data->discount_percent ?? 0;
                                $discountAmount = ($discountPercent / 100) * $grossAmount;
                                $deduction = $data->deduction ?? 0; // Additional deduction if any
                                $amount_after_deduction = $grossAmount - $discountAmount - $deduction;
                                $taxAmount = ($taxPercent / 100) * $amount_after_deduction;
                                $amount = $amount_after_deduction + $taxAmount; // Amount after discount and deduction
                                $description = $purchase_return_data->description ?? '';
                            @endphp
                            <tr id="row_{{ $rowIndex }}">
                                <td style="min-width: 200px;">
                                    <select name="item_id[]" id="item_id_{{ $rowIndex }}"
                                        class="form-control select2" disabled>
                                        <option value="">Select Item</option>
                                        @foreach (\App\Models\Product::all() ?? [] as $item)
                                            <option value="{{ $item->id }}"
                                                {{ $data->item_id == $item->id ? 'selected' : '' }}>
                                                {{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td style="min-width: 80px; text-align: center;">
                                    <input type="number" name="quantity[]" id="quantity_{{ $rowIndex }}"
                                        class="form-control quantity" step="0.01" min="0"
                                        value="{{ $quantity }}" readonly style="text-align: center;">
                                </td>
                                <td style="min-width: 80px; text-align: center;">
                                    <input readonly type="number" name="rate[]" id="rate_{{ $rowIndex }}"
                                        class="form-control rate" step="0.01" min="0" value="{{ $rate }}" style="text-align: center;">
                                </td>
                                <td style="min-width: 110px; text-align: center;">
                                    <input readonly type="number" name="gross_amount[]"
                                        id="gross_amount_{{ $rowIndex }}" class="form-control gross_amount"
                                        readonly value="{{ $grossAmount }}" style="text-align: center;">
                                </td>
                                <td style="min-width: 80px; text-align: center;">
                                    <input readonly type="number" name="discount_percent[]"
                                        id="discount_percent_{{ $rowIndex }}" class="form-control discount_percent"
                                        step="0.01" min="0" value="{{ $discountPercent }}" style="text-align: center;">
                                </td>
                                <td style="min-width: 110px; text-align: center;">
                                    <input readonly type="number" name="discount_amount[]"
                                        id="discount_amount_{{ $rowIndex }}" class="form-control discount_amount" readonly
                                        value="{{ $discountAmount }}" style="text-align: center;">
                                </td>
                                <td style="min-width: 80px; text-align: center;">
                                    <input readonly type="number" name="deduction[]" id="deduction_{{ $rowIndex }}" class="form-control deduction" step="0.01" min="0" value="{{ $deduction }}" style="text-align: center;">
                                </td>
                                <td style="min-width: 110px; text-align: center;">
                                    <input readonly type="number" name="amount_after_deduction[]"
                                        id="amount_after_deduction_{{ $rowIndex }}" class="form-control amount_after_deduction" readonly
                                        value="{{ $amount_after_deduction }}" style="text-align: center;">
                                </td>
                                <td style="min-width: 80px; text-align: center;">
                                    <input readonly type="number" name="tax_percent[]"
                                        id="tax_percent_{{ $rowIndex }}" class="form-control tax_percent"
                                        step="0.01" min="0" value="{{ $taxPercent }}" style="text-align: center;">
                                </td>
                                <td style="min-width: 110px; text-align: center;">
                                    <input readonly type="number" name="tax_amount[]"
                                        id="tax_amount_{{ $rowIndex }}" class="form-control tax_amount" readonly
                                        value="{{ $taxAmount }}" style="text-align: center;">
                                </td>
                                <td style="min-width: 110px; text-align: center;">
                                    <input readonly type="number" name="amount[]"
                                        id="amount_{{ $rowIndex }}" class="form-control amount" readonly
                                        value="{{ $amount }}" style="text-align: center;">
                                </td>
                    
                                <td style="min-width: 150px;">
                                    <input readonly type="text" name="description[]"
                                        id="description_{{ $rowIndex }}" class="form-control description"
                                        value="{{ $description }}">
                                </td>
                                <td style="min-width: 80px;">
                                    <button type="button" class="btn btn-danger btn-sm removeRowBtn"
                                        onclick="removeRow({{ $rowIndex }})" style="width:60px;" disabled>
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @php $rowIndex++; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <input type="hidden" id="rowCount" value="0">

    <div class="row bottom-button-bar">
        <div class="col-12 text-end">
            <a type="button"
            class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton me-2">Close</a>
            <button type="submit" class="btn btn-primary submitbutton" disabled>Save</button>
        </div>
    </div>
</form>
<x-approval-status :model="$purchaseReturn" />

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });

    function round(num, decimals = 2) {
        return Number(Math.round(num + "e" + decimals) + "e-" + decimals);
    }
</script>

