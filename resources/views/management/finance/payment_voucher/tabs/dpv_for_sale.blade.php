<form id="ajaxSubmitSale" action="{{ route('direct.payment-voucher-sale.store') }}" method="POST">
    @csrf

    <!-- Customer Bank Account Hidden Values -->
    <input type="hidden" name="bank_account_number" id="sale_bank_account_number">
    <input type="hidden" name="bank_account_type" id="sale_bank_account_type">

    <!-- Voucher Top Details -->
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="sale_voucher_type">Voucher Type <span class="text-danger">*</span></label>
                <select name="voucher_type" id="sale_voucher_type" class="form-control select2" required>
                    <option value="">Select Type</option>
                    <option value="bank_payment_voucher">Bank Payment Voucher</option>
                    <option value="cash_payment_voucher">Cash Payment Voucher</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="sale_pv_date">Date <span class="text-danger">*</span></label>
                <input type="date" name="pv_date" id="sale_pv_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="sale_unique_no">PV Number</label>
                <input type="text" name="unique_no" id="sale_unique_no" class="form-control" readonly placeholder="Auto-generated on type & date">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="sale_account_id">Payment From (Cash/Bank) <span class="text-danger">*</span></label>
                <select name="account_id" id="sale_account_id" class="form-control select2" required>
                    <option value="">Select Account</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="sale_customer_account_id">Account (Payment To) <span class="text-danger">*</span></label>
                <select name="customer_account_id" id="sale_customer_account_id" class="form-control select2" required>
                    <option value="">Select Account</option>
                    @foreach ($customerAccounts ?? [] as $custAcc)
                        @php
                            $typeLabel = $custAcc->type_label ?? (!empty($custAcc->table_name) ? ucfirst(rtrim($custAcc->table_name, 's')) : '');
                        @endphp
                        <option value="{{ $custAcc->id }}" data-table-name="{{ strtolower($custAcc->table_name ?? '') }}">
                            {{ $custAcc->name }} ({{ $custAcc->unique_no }}){{ $typeLabel ? " - [{$typeLabel}]" : "" }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="sale_ref_bill_no">Ref Bill / Cheque Ref</label>
                <input type="text" name="ref_bill_no" id="sale_ref_bill_no" class="form-control" placeholder="Optional">
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="sale_bill_date">Bill Date</label>
                <input type="date" name="bill_date" id="sale_bill_date" class="form-control">
            </div>
        </div>
    </div>

    <!-- Bank fields for Cheque -->
    <div id="sale_bankFields" style="display: none;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sale_cheque_no">Cheque No <span class="text-danger">*</span></label>
                    <input type="text" name="cheque_no" id="sale_cheque_no" class="form-control" placeholder="Cheque number">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sale_cheque_date">Cheque Date <span class="text-danger">*</span></label>
                    <input type="date" name="cheque_date" id="sale_cheque_date" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <!-- Table of Line Items (Separate fields for SO, DO, and Excess Advance) -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="font-weight-bold mb-0">Sales Adjustment Details (Excess Amount / Delivery Order)</label>
                <span class="badge badge-light" id="sale_customer_selected_badge" style="display: none;"></span>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="saleVoucherEntriesTable" style="min-width: 1200px;">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 12%;">Adjustment Type <span class="text-danger">*</span></th>
                            <th style="width: 16%;">Sales Order</th>
                            <th style="width: 16%;">Delivery Order</th>
                            <th style="width: 16%;">Excess Advance</th>
                            <th style="width: 9%;" class="text-right">Available Bal</th>
                            <th style="width: 9%;" class="text-right">Amount <span class="text-danger">*</span></th>
                            <th style="width: 7%;">Tax</th>
                            <th style="width: 6%;" class="text-right">Tax Amt</th>
                            <th style="width: 8%;" class="text-right">Net Amount</th>
                            <th style="width: 10%;">Description</th>
                            <th style="width: 4%;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="saleVoucherEntriesBody">
                        <tr>
                            <td colspan="11" class="text-center text-muted py-3" id="saleEmptyRow">
                                Please select a Customer Account first to add adjustment items.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="8" class="text-right"><strong>Total Net Amount:</strong></td>
                            <td class="text-right"><strong id="saleTotalNetAmount" class="text-primary font-medium-2">0.00</strong></td>
                            <td colspan="2"></td>
                        </tr>
                        <tr>
                            <td colspan="11">
                                <button type="button" class="btn btn-sm btn-primary" id="saleAddRowBtn">
                                    <i class="ft-plus"></i> Add Line Item
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Customer Bank Account Selection at the end (Matches create.blade.php) -->
    <div class="row sale-bank-account-section mt-3">
        <div class="col-md-12">
            <div class="form-group">
                <label for="sale_bank_account_id"><strong>Select Customer Bank Account (Transfer To)</strong></label>
                <select name="bank_account_id" id="sale_bank_account_id" class="form-control select2" style="width: 100%">
                    <option value="">Select Bank Account</option>
                </select>
                <small class="text-muted">Select the customer's bank account where the refunded amount is transferred.</small>
            </div>
        </div>
    </div>

    <!-- Remarks -->
    <div class="row mt-2">
        <div class="col-md-12">
            <div class="form-group">
                <label for="sale_remarks">Remarks</label>
                <textarea name="remarks" id="sale_remarks" rows="2" class="form-control" placeholder="Optional notes for this voucher"></textarea>
            </div>
        </div>
    </div>

    <div class="form-group text-right mt-4">
        <button type="submit" class="btn btn-primary btn-lg" id="saleSubmitBtn">
            <i class="ft-check mr-1"></i> Create DPV For Sale
        </button>
    </div>
</form>

<style>
.sale-bank-account-section .bank-account-option {
    padding: 5px;
}
.sale-bank-account-section .bank-account-option strong {
    display: block;
    margin-bottom: 2px;
}
.sale-bank-account-section .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #f8f9fa;
    color: #333;
}
.sale-bank-account-section .select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #e9ecef;
}
.sale-bank-account-section .select2-container--default .select2-results__option[aria-selected=true] .bank-account-option {
    opacity: 0.7;
}
</style>
