@foreach ($items as $idx => $item)
    {{-- @dd($item->quantity) --}}
    @php
        $balance = receipt_voucher_balance($item->reference_id, $item->reference_type);
        if(!$balance) continue;
    @endphp
    <tr class="reference-main-row" id="reference-row-{{ $idx }}">
        <td class="text-center">
            <input type="checkbox" class="row-select" data-row="{{ $idx }}">
            <input type="hidden" name="items[{{ $idx }}][reference_id]" value="{{ $item->reference_id }}">
            <input type="hidden" name="items[{{ $idx }}][reference_type]" value="{{ $item->reference_type }}">
            <input type="hidden" class="hidden-amount" name="items[{{ $idx }}][amount]"
                value="{{ $balance }}">
        </td>
        <td>{{ $item->reference_type == 'sale_order' ? 'Sale Order' : 'Sale Invoice' }}</td>
        <td>{{ $item->number }}</td>
        <td>{{ $item->date }}</td>
        <td>{{ $item->customer_name }}</td>
        <td>
            <input type="number" step="0.01" class="form-control amount-input" name="items[{{ $idx }}][amount_display]"
                value="{{ $balance }}" data-balance="{{ $balance }}" readonly>
            Balance: {{ $balance }}
        </td>
        <td>
            <select class="form-control tax-select" name="items[{{ $idx }}][tax_id]">
                <option value="">No Tax</option>
                @foreach($taxes as $tax)
                    <option value="{{ $tax->id }}" data-percent="{{ $tax->percentage }}">{{ $tax->name }} ({{ $tax->percentage }}%)</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="0.01" readonly class="form-control tax-amount"
                name="items[{{ $idx }}][tax_amount]" value="0.00">
        </td>
        <td>
            <input type="number" step="0.01" readonly class="form-control net-amount"
                name="items[{{ $idx }}][net_amount]" value="{{  round($item->amount, 2) }}">
        </td>
        <td>
            <input type="text" class="form-control line-desc" name="items[{{ $idx }}][line_desc]"
                placeholder="Line description">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-xs btn-outline-info toggle-bank-subrow" data-row-idx="{{ $idx }}" title="Toggle Bank Details">
                <i class="fa fa-chevron-down"></i> Banks
            </button>
        </td>
    </tr>
    <tr class="bank-details-subrow" id="bank-subrow-{{ $idx }}" style="display: none; background-color: #f9fbfd;">
        <td></td>
        <td colspan="10">
            <div class="card my-2 border-info shadow-sm" style="border: 1px solid #17a2b8 !important;">
                <div class="card-header py-2 d-flex justify-content-between align-items-center" style="background-color: #eef7fc; border-bottom: 1px solid #17a2b8;">
                    <h6 class="mb-0 text-info font-weight-bold" style="font-size: 0.9rem;">
                        <i class="fa fa-university mr-1"></i> Bank/Account Details for {{ $item->number }}
                    </h6>
                    <div>
                        <button type="button" class="btn btn-xs btn-success add-nested-bank-btn" data-row-idx="{{ $idx }}" style="padding: .2rem .4rem; font-size: .75rem;">
                            <i class="fa fa-plus"></i> Add Account
                        </button>
                        <button type="button" class="btn btn-xs btn-primary add-nested-advance-btn ml-1" data-row-idx="{{ $idx }}" style="padding: .2rem .4rem; font-size: .75rem;">
                            <i class="fa fa-plus"></i> Add Advance
                        </button>
                    </div>
                </div>
                <div class="card-body p-2" style="background-color: #ffffff;">
                    <table class="table table-bordered table-sm mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th>Account / Advance</th>
                                <th width="20%">Amount</th>
                                <th width="25%">Cheque No</th>
                                <th width="8%">Action</th>
                            </tr>
                        </thead>
                        <tbody class="nested-bank-data" id="nested-bank-data-{{ $idx }}">
                        </tbody>
                    </table>
                </div>
            </div>
        </td>
    </tr>
@endforeach

<script>
    
    $(document).ready(function() {

        

    // Function to recalc tax and net for a row
    function recalcRow(row) {
        const amountInput = row.find('.amount-input');
        const taxSelect = row.find('.tax-select');
        const taxAmountInput = row.find('.tax-amount');
        const netAmountInput = row.find('.net-amount');

        const amount = parseFloat(amountInput.val()) || 0;
        const taxPercent = parseFloat(taxSelect.find('option:selected').data('percent')) || 0;
        const taxAmount = amount * taxPercent / 100;
        const netAmount = amount + taxAmount;

        taxAmountInput.val(taxAmount.toFixed(2));
        netAmountInput.val(netAmount.toFixed(2));
    }

    // Bind events to each row
    $('#referencesTable').on('input', '.amount-input', function() {
        const row = $(this).closest('tr');
        recalcRow(row);
        updateSelectedDocsList();
        updateTotal();
    });

    $('#referencesTable').on('change', '.tax-select', function() {
        const row = $(this).closest('tr');
        recalcRow(row);
        updateSelectedDocsList();
        updateTotal();
    });

    // Optional: recalc total of all selected rows
    function updateTotal() {
        let total = 0;
        $('#referencesTable tbody tr').each(function() {
            const checkbox = $(this).find('.row-select');
            if (checkbox.length && checkbox.is(':checked')) {
                const net = parseFloat($(this).find('.net-amount').val()) || 0;
                total += net;
            }
        });
        $('#totalAmount').text(total.toFixed(2)); // You can create an element with id="totalAmount" to display
    }

    // Trigger initial calculation for existing rows
    $('#referencesTable tbody tr').each(function() {
        recalcRow($(this));
    });
});

</script>

