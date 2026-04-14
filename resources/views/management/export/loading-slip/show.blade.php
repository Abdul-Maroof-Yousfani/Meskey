<div class="modal-body">
    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Ticket:</label>
                <input type="text" value="{{ $loadingSlip->loadingProgramItem->transaction_number ?? 'N/A' }} -- {{ $loadingSlip->loadingProgramItem->truck_number ?? 'N/A' }}"
                    disabled class="form-control" autocomplete="off" readonly />
            </div>
        </div>
    </div>

    @if(count($Orders) > 0)
        <ul class="nav nav-tabs nav-justified" id="orderTabs" role="tablist">
            @foreach($Orders as $index => $order)
                <li class="nav-item">
                    <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="order-tab-{{ $index }}" data-toggle="tab" href="#order-content-{{ $index }}" role="tab" aria-controls="order-content-{{ $index }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                        {{ $order['type'] }}: {{ $order['number'] }}
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="tab-content pt-1" id="orderTabsContent">
            @foreach($Orders as $index => $order)
                <div class="tab-pane fade show {{ $index === 0 ? 'active' : '' }}" id="order-content-{{ $index }}" role="tabpanel" aria-labelledby="order-tab-{{ $index }}">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>Customer:</label><input type="text" value="{{ $order['customer'] }}" class="form-control" readonly disabled /></div></div>
                        <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>Commodity:</label><input type="text" value="{{ $order['commodity'] }}" class="form-control" readonly disabled /></div></div>
                        <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>EO Qty (MT):</label><input type="number" value="{{ $order['so_qty'] }}" class="form-control" readonly disabled /></div></div>
                        <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>DO Qty (MT):</label><input type="number" value="{{ $order['do_qty'] }}" class="form-control" readonly disabled /></div></div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-4"><div class="form-group"><label>Factory:</label><select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">@foreach($order['factory_names'] as $name)<option selected>{{ $name }}</option>@endforeach</select></div></div>
                        <div class="col-xs-12 col-sm-6 col-md-4"><div class="form-group"><label>Gala:</label><select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">@foreach($order['gala_names'] as $name)<option selected>{{ $name }}</option>@endforeach</select></div></div>
                        <div class="col-xs-12 col-sm-6 col-md-4"><div class="form-group"><label>Bag Size:</label><input type="number" value="{{ $order['bag_size'] }}" class="form-control" readonly disabled /></div></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="col-12 text-center">No order data found.</div>
    @endif

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>No. of Bags:</label><input type="text" value="{{ $loadingSlip->no_of_bags ?? 'N/A' }}" disabled class="form-control" autocomplete="off" readonly /></div></div>
        <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>Kilogram:</label><input type="text" value="{{ $loadingSlip->kilogram ?? 'N/A' }}" disabled class="form-control" autocomplete="off" readonly /></div></div>
        <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>Metric Tons:</label><input type="text" value="{{ number_format(($loadingSlip->kilogram ?? 0) / 1000, 2) }}" disabled class="form-control" autocomplete="off" readonly /></div></div>
        <div class="col-xs-12 col-sm-6 col-md-3"><div class="form-group"><label>Labour</label><select class='form-control select2' disabled><option value='paid' @selected($loadingSlip->labour == 'paid')>Paid</option><option value='not_paid' @selected($loadingSlip->labour == 'not_paid')>Not Paid</option></select></div></div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea class="form-control" readonly>{{ $loadingSlip->remarks }}</textarea>
            </div>
        </div>
    </div>
</div>
<script>
    $(".select2").select2();
</script>
