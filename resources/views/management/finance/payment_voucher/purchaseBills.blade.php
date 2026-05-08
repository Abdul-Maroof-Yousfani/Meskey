@foreach($purchase_bills as $purchaseBill)
@if($purchaseBill->amount <= 0) @continue @endif
<tr>
    <td>
        <input type="checkbox" class="form-control item-checkbox"  data-amount="{{ $purchaseBill->amount }}" value="{{ $purchaseBill->id }}" data-transaction="{{ $purchaseBill->bill_no }}" name="bill[{{ $purchaseBill->id }}]" style="width: 12px; height: 12px; margin: 0 auto;" />
    </td>
    <td>
        <input type="text" class="form-control" name="bill_no[{{ $purchaseBill->id }}]" value="{{ $purchaseBill->bill_no }}" readonly/>
    </td>
    <td>
        <input type="text" class="form-control" name="grn_no[{{ $purchaseBill->id }}]" value="{{ $purchaseBill->grn->reference_no }}" readonly />
    </td>
    <td>
        <input type="text" class="form-control" name="supplier[{{ $purchaseBill->id }}]" value="{{ $purchaseBill->supplier->name }}" readonly />
    </td>
    <td>
        <input type="hidden" name="purchase_bill_id[{{ $purchaseBill->id }}]" value="{{ $purchaseBill->id }}" />
        <input type="text" class="form-control amount-field" name="amounts[{{ $purchaseBill->id }}]" value="{{ $purchaseBill->amount }}" />
        @if($purchaseBill->total_bill > 0)
            <p class="mt-2">Total Amount: {{ $purchaseBill->total_bill }}</p>
        @endif
        
        @if($purchaseBill->debit_amount > 0)
            <p>Debit Note Reduction: {{ $purchaseBill->debit_amount }}</p>
        @endif
        
        @if($purchaseBill->return_amount > 0)
            <p>Purchase Return Reduction: {{ $purchaseBill->return_amount }}</p>
        @endif

        @if($purchaseBill->spent_bill > 0)
            <p>Used Amount: {{ $purchaseBill->spent_bill }}</p>
        @endif
    </td>
</tr>
@endforeach