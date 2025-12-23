@foreach ($items as $idx => $item)
    {{-- @dd($item->quantity) --}}
    @php
        $balance = receipt_voucher_balance($item->reference_id, $item->reference_type);
        if($balance < 0) continue;
    @endphp
    <tr>
        <td class="text-center">
            <input type="checkbox" class="row-select" data-row="{{ $idx }}">
            <input type="hidden" name="items[{{ $idx }}][reference_id]" value="{{ $item->reference_id }}">
            <input type="hidden" name="items[{{ $idx }}][reference_type]" value="{{ $item->reference_type }}">
            <input type="hidden" class="hidden-amount" name="items[{{ $idx }}][amount]"
                value="{{ $item->quantity }}">
        </td>
        <td>{{ $item->reference_type == 'sale_order' ? 'Sale Order' : 'Sale Invoice' }}</td>
        <td>{{ $item->number }}</td>
        <td>{{ $item->date }}</td>
        <td>{{ $item->customer_name }}</td>
        <td>
            <input type="number" step="0.01" class="form-control amount-input" name="items[{{ $idx }}][amount_display]"
                value="{{ $balance }}" max="{{ $balance }}">
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

