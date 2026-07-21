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

<!-- <form action="" method="POST" id="ajaxSubmit" autocomplete="off"> -->
    <!-- @csrf -->

    <input type="hidden" id="listRefresh" value="{{ route('sales.get.delivery-order.list') }}" />

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="row">
                <div class="col-12">
                    <h6 class="header-heading-sepration">General Information</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Do No:</label>
                        <input type="text" name="reference_no" id="reference_no" class="form-control"
                            value="{{ $delivery_order->reference_no }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">So No:</label>
                        <input type="text" class="form-control"
                            value="{{ $sale_order_of_delivery_order->reference_no }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Do Date:</label>
                        <input type="date" name="dispatch_date" id="dispatch_date" class="form-control"
                            value="{{ $delivery_order->dispatch_date }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Contract Type:</label>
                        <select name="sauda_type" id="sauda_type" class="form-control select2" disabled>
                            <option value="">Select Contract Type</option>
                            <option value="pohanch" @selected($delivery_order->sauda_type == 'pohanch')>Pohanch</option>
                            <option value="x-mill" @selected($delivery_order->sauda_type == 'x-mill')>X-mill</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Delivery Date:</label>
                        <input type="date" name="delivery_date" id="delivery_date" class="form-control"
                            value="{{ $delivery_order->delivery_date ?? $delivery_order->salesOrder->delivery_date }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Reference Number:</label>
                        <input type="text" name="ref_no" id="ref_no" class="form-control"
                            value="{{ $delivery_order->ref_no }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Withhold %:</label>
                        <input type="text" name="so_withhold_percentage" id="so_withhold_percentage" class="form-control"
                            value="{{ $delivery_order->so_withhold_percentage }}" readonly>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Amount to be Held:</label>
                        <input type="text" name="so_held_amount" id="so_held_amount" class="form-control"
                            value="{{ $delivery_order->so_held_amount }}" readonly>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Customer & Order Details</h6>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Customer:</label>
                        <select name="customer_id" id="customer_id" class="form-control select2" disabled>
                            <option value="">Select Customer</option>
                            @foreach ($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" @selected($delivery_order->customer_id == $customer->id)>{{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Sale Orders:</label>
                        <select name="sale_order_id" id="sale_order" class="form-control select2" disabled>
                            <option value="">Select SO</option>
                            @foreach ($sales_orders as $sale_order)
                                <option value="{{ $sale_order->id }}" @selected($delivery_order->so_id == $sale_order->id)>
                                    {{ $sale_order->reference_no }}</option>
                            @endforeach
                            @if($sale_order_of_delivery_order && !$sales_orders->contains('id', $sale_order_of_delivery_order->id))
                                <option value="{{ $sale_order_of_delivery_order->id }}" selected>
                                    {{ $sale_order_of_delivery_order->reference_no }}</option>
                            @endif
                        </select>
                    </div>
                </div>

                @if ($sale_order_of_delivery_order->pay_type_id == 10)
                    <div class="col-12 mt-3 advanced">
                        <h6 class="header-heading-sepration">Payment Details</h6>
                    </div>
                    <div class="col-md-3 advanced">
                        <div class="form-group">
                            <label class="form-label">Receipt Vouchers:</label>
                            <select name="receipt_vouchers[]" id="receipt_vouchers" class="form-control select2" disabled
                                multiple>
                                <option value="">Select Receipt Vouchers</option>
                                @foreach ($receipt_vouchers as $item)
                                    <option value="{{ $item->unified_id }}" selected>
                                        {{ $item->unified_text }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 advanced">
                        <div class="form-group">
                            <label class="form-label">Advance Amount:</label>
                            <input type="number" step="any" name="advance_amount" id="advance_amount" class="form-control"
                                value="{{ $delivery_order->advance_amount > 0 ? $delivery_order->advance_amount : '' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 advanced">
                        <div class="form-group">
                            <label class="form-label">Withhold Amount:</label>
                            <input type="number" step="any" name="withhold_amount" id="withhold_amount" class="form-control"
                                value="{{ $delivery_order->withhold_amount }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 advanced">
                        <div class="form-group">
                            <label class="form-label">Withhold for RV:</label>
                            <select name="withhold_for_rv" id="withhold_for_rv" class="form-control select2" disabled>
                                <option value="">Select Receipt Voucher</option>
                                @foreach ($receipt_vouchers as $item)
                                    <option value="{{ $item->unified_id }}" @selected($item->pivot->withhold_amount > 0)>
                                        {{ $item->unified_text }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-12 mt-2"></div>
                    <div class="col-md-3 advanced">
                        <div class="form-group">
                            <label class="form-label">Journal Vouchers:</label>
                            <select name="journal_vouchers[]" id="journal_vouchers" class="form-control select2" disabled multiple>
                                <option value="">Select Journal Vouchers</option>
                                @foreach ($journal_vouchers as $item)
                                    <option value="{{ $item['id'] }}" selected>
                                        {{ $item['text'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 advanced">
                        <div class="form-group">
                            <label class="form-label">JV Amount:</label>
                            <input type="number" step="any" name="jv_amount" id="jv_amount" class="form-control"
                                value="{{ $delivery_order->jv_amount > 0 ? $delivery_order->jv_amount : '' }}" readonly>
                        </div>
                    </div>
                @endif

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Location Details</h6>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Locations:</label>
                        <select name="location_id" id="locations" class="form-control select2" disabled>
                            <option value="">Select Locations</option>
                            <option value="{{ $delivery_order->location_id }}" selected>
                                {{ get_location_name_by_id($delivery_order->location_id) }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Factory:</label>
                        <select name="arrival_id[]" id="arrivals" class="form-control select2" disabled multiple>
                            <option value="">Select Factory </option>
                            @php
                                $selectedArrivalIds = $delivery_order->arrival_location_id ? explode(',', $delivery_order->arrival_location_id) : [];
                            @endphp
                            @foreach (get_arrivals_by($delivery_order->location_id) as $location)
                                <option value="{{ $location->id }}" @selected(in_array($location->id, $selectedArrivalIds))>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Section:</label>
                        <select name="storage_id[]" id="storages" class="form-control select2" disabled multiple>
                            <option value="">Select Section</option>
                            @php
                                $selectedSubArrivalIds = $delivery_order->sub_arrival_location_id ? explode(',', $delivery_order->sub_arrival_location_id) : [];
                                $arrivalIds = $delivery_order->arrival_location_id ? explode(',', $delivery_order->arrival_location_id) : [$delivery_order->arrival_location_id];
                            @endphp
                            @foreach (get_sub_arrivals_by_multiple($arrivalIds) as $location)
                                <option value="{{ $location->id }}" @selected(in_array($location->id, $selectedSubArrivalIds))>
                                    {{ $location->name }} ({{ $location->arrivalLocation->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <h6 class="header-heading-sepration">Remarks</h6>
                </div>
                <div class="col-12">
                    <div class="form-group">
                        <textarea name="remarks" id="remarks" class="form-control" rows="3" readonly>{{ $delivery_order->remarks ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <div class="col-md-3 offset-md-9 text-end mb-3">
            <div class="form-group">
                <label for="do_status" class="form-label">DO Status:</label>
                <select class="form-control" disabled>
                    <option value="active" {{ $delivery_order->do_status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="closed" {{ $delivery_order->do_status == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-md-12">
            <div class="table-responsive" style="overflow-x: auto; white-space: nowrap;">
                <table class="table table-bordered" id="salesInquiryTable" style="min-width:2000px;">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Bag Type</th>
                            <th>Packing</th>
                            <th>No of Bags</th>
                            <th>Quantity (Kg)</th>
                            <th>Rate per Kg</th>
                            <th>Rate per Mond</th>
                            <th>Amount</th>
                            <th>Brand</th>
                            <th>Desc</th>
                            <th style="display: none">Pack Size</th>
                            <th style="display: none">Action</th> <!-- Hidden Action column to match Create structure but keep consistent -->
                        </tr>
                    </thead>
                    <tbody id="soTableBody">
                        @foreach ($delivery_order->delivery_order_data as $index => $data)
                            <tr id="row_{{ $index }}">
                                <td>
                                    <input type="text" class="form-control"
                                        value="{{ getItem($data->item_id)?->name }}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control"
                                        value="{{ bag_type_name($data->bag_type) }}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="{{ $data->bag_size }}"
                                        readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="{{ $data->no_of_bags }}"
                                        readonly>
                                </td>
                                <td>
                                    <input type="number" class="form-control" value="{{ round($data->qty) }}" readonly>
                                    <span style="font-size: 14px;;">Used Quantity:
                                        {{ round(delivery_order_qty_used($data->so_data_id))  }}</span>
                                    <br />
                                    <span style="font-size: 14px;">Balance:
                                        {{ round(delivery_order_qty_balance($data->so_data_id))  }}</span>
                                </td>
                                <td>
                                    <input type="number" class="form-control" value="{{ $data->rate }}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control"
                                        value="{{ $data->salesOrderData->rate_per_mond }}" readonly>
                                </td>
                                <td>
                                    <input type="number" class="form-control"
                                        value="{{ round($data->rate * ($data->qty ?? 0))  }}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control"
                                        value="{{ getBrandById($data->brand_id)?->name ?? 'N/A' }}" readonly>
                                </td>
                                <td>
                                    <input type="text" class="form-control" value="{{ $data->description }}"
                                        readonly>
                                </td>
                                <td style="display: none">
                                    <input type="text" class="form-control" value="{{ $data->pack_size ?? 0 }}"
                                        readonly>
                                </td>
                                <td style="display: none">
                                    <button type="button" disabled class="btn btn-danger btn-sm">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
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
            <!-- Save button removed for view -->
        </div>
    </div>
<!-- </form> -->

<div class="row">
    <div class="col-12">
        <x-approval-status :model="$delivery_order" :list-refresh="route('sales.get.delivery-order.list')" />
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
