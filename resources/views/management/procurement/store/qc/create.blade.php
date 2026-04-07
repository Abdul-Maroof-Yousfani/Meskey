@php
    $previousUrl = url()->previous();
    $refreshRoute = str_contains($previousUrl, 'procurement/store/qc') 
        ? route('store.qc.getList') 
        : route('store.get.purchase-order-receiving');
@endphp
<form action="{{ route('store.qc.store') }}" id="ajaxSubmit">
    <input type="hidden" id="listRefresh" value="{{ $refreshRoute }}">
    <input type="hidden" name="purchase_receiving_data_id" value="{{ $id }}">
<div style="padding-left: 10px; padding-right: 10px;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Date:</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" id="date" class="form-control" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">GRN:</label>
                    <input type="text" name="grn" id="grn" value="{{ $grn }}" readonly class="form-control" readonly>
                </div>
            </div>
        </div>
        <div class="row" style="margin-top: 10px;">
            <div class="col-md-12">
                <table class="table table-bordered" id="purchaseRequestTable">
                    <thead>
                        <tr>
                            <th>Item</th>
                            @if($purchaseOrderReceivingData->category_id == 38)
                            <th>Size</th>
                            <th>Brand</th>
                            <th>Job Order</th>
                            @endif
                            @canApprove('qc')
                            <th>PO No</th>
                            <th>Rate</th>
                            @endcanApprove
                            <th>DC No</th>
                            <th>Required Weight Per Bag (grams)</th>
                            <th>Average Weight of 1 Bag (grams)</th>
                            <th>Total Bags</th>
                            <th>Total Weight Required (grams)</th>
                            <th>Sample Average Weight (grams)</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseOrderBody">
                        <tr>
                            <td>
                                <input type="text" name="item" id="item" value="{{ getItem($purchaseOrderReceivingData->item_id)->name }}" readonly
                                    class="form-control">
                            </td>
                            @if($purchaseOrderReceivingData->category_id == 38)
                            <td>
                                <input type="text" name="size" id="size" value="{{ $purchaseOrderReceivingData?->purchase_order_data?->size ?? null }}" readonly
                                    class="form-control">
                            </td>
                            <td>
                                <input type="text" name="brand" id="brand" value="{{ $purchaseOrderReceivingData?->purchase_order_data?->brand ?? null }}" readonly
                                    class="form-control">
                            </td>

                            @php
                                // dd($purchaseOrderReceivingData->purchase_order_data);
                                $data = $purchaseOrderReceivingData->purchase_order_data->purchase_request_data->JobOrder;
                                $string = "";
                                foreach($data as $datum) {
                                    $string .= $datum->job_order_data->job_order_no . ", ";
                                }

                            @endphp
                            <td>
                                <select style="width: 190px;" name="job_order" id="job_order" class="form-control select2" multiple disabled>
                                    <option value="">Select Job Order</option>
                                    @foreach($data as $datum)
                                        <option value="{{ $datum->id }}" selected>{{ $datum->job_order_data->job_order_no }}</option>
                                    @endforeach
                                </select>
                            </td>
                            @endif

                             @canApprove('qc')
                            <td>
                                <input type="text" value="{{ $purchaseOrderReceivingData?->purchase_order_receiving?->purchase_order?->purchase_order_no }}" readonly class="form-control">
                            </td>
                            <td>
                                <input type="text" value="{{ $purchaseOrderReceivingData?->purchase_order_data?->rate }}" readonly class="form-control">
                            </td>
                            @endcanApprove

                            <td>
                                <input type="text" name="dc_no" id="dc_no" value="{{ $purchaseOrderReceivingData->purchase_order_receiving->dc_no }}" readonly
                                    class="form-control">
                            </td>

                            <td>
                                <input type="text" name="required_weight_per_bag" value="{{ $purchaseOrderReceivingData->category_id == 38 ? ($purchaseOrderReceivingData?->purchase_order_data?->min_weight ?? null) : 0 }}" id="required_weight_per_bag" readonly class="form-control">
                            </td>

                            <td>
                                <input type="text" name="average_weight_of_one_bag" onkeyup="calculate_total_recieved_weight(this)" id="average_weight_of_1_bag"
                                     class="form-control" placeholder="Average Weight of One Bag" value="{{ (round($purchaseOrderReceivingData->receive_weight / $purchaseOrderReceivingData->qty, 5)) }}" readonly>
                            </td>

                            <td>
                                <input type="text" name="total_bags" id="total_bags" value="{{ $purchaseOrderReceivingData->qty }}" readonly
                                    class="form-control">
                            </td>

                            <td>
                                <input type="text" name="total_weight_required" value="{{ (($purchaseOrderReceivingData->qty ?? 0) * ($purchaseOrderReceivingData?->purchase_order_data?->min_weight ?? 0)) }}" id="total_weight_required" value="Total Weight Required"
                                    readonly class="form-control">
                            </td>

                            <td>
                                <input type="text" name="sample_average_weight" id="total_weight_received"
                                    class="form-control" value="{{ (round($purchaseOrderReceivingData?->receive_weight / ($purchaseOrderReceivingData?->qty ?: 1), 2)) }}" readonly>
                            </td>

                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <p style="margin-top: 20px; font-size: 20px;">Weight of randomly-selected bags sets</p>
        <div class="row" style="margin-top: 10px;">
            <div class="col-md-6" style="padding: 0px; padding-left: 10px;">
                <table class="table table-bordered" id="purchaseRequestTable">
                    <thead>
                        <tr>
                            <th>S.#</th>
                            <th>Net Weight (grams)</th>
                            <th>Number of bags</th>
                            <th>Average weight of 1 bag (grams)</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseOrderBody">
                        @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td style="width: 100px;">
                                    <input type="text" name="item" style="text-align: center" id="item"
                                        value="{{ $i + 1 }}" readonly class="form-control">
                                </td>
                                <td>
                                    <input type="text" name="net_weight[]" onkeyup="calculateTotalWeight(this)" id="net_weight" placeholder="Net Weight"
                                        class="form-control" required>
                                </td>
                                <td>
                                    <input type="text" name="bag_weight[]" onkeyup="calculateTotalWeight(this)" id="bag_weight" placeholder="Number of bags"
                                        class="form-control" required>
                                </td>

                                <td>
                                    <input type="text" name="total_weight[]" id="total_weight" placeholder="Average weight of one bag"
                                        class="form-control" readonly>
                                </td>
                            </tr>
                        @endfor

                    </tbody>
                </table>
            </div>
            <div class="col-md-6" style="padding: 0px; padding-right: 10px;">
                <table class="table table-bordered" id="purchaseRequestTable">
                    <thead>
                        <tr>
                            <th>S.#</th>
                            <th>Net Weight (grams)</th>
                            <th>Number of bags</th>
                            <th>Average weight of 1 bag (grams)</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseOrderBody">
                        @for ($i = 5; $i < 10; $i++)
                            <tr>
                                <td style="width: 100px; text-align: center;">
                                    <input type="text" name="item" style="text-align: center" id="item"
                                        value="{{ $i + 1 }}" readonly class="form-control">
                                </td>
                                <td>
                                    <input type="text" name="net_weight[]" onkeyup="calculateTotalWeight(this)" id="net_weight" placeholder="Net Weight"
                                        class="form-control" required>
                                </td>
                                <td>
                                    <input type="text" name="bag_weight[]" onkeyup="calculateTotalWeight(this)" id="bag_weight" placeholder="Number of bags"
                                        class="form-control" required>
                                </td>


                                <td>
                                    <input type="text" name="total_weight[]" id="total_weight" placeholder="Average weight of one bag"
                                        class="form-control" readonly>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>


        <p style="margin-top: 20px; font-size: 20px;">Additional Data</p>
        <div class="row" style="margin-top: 10px;">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Size:</label>
                    <input type="text" name="size" id="size" value="" class="form-control">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Phy/Che/Bio:</label>
                    <input type="text" name="bio" id="bio" value="" class="form-control">
                </div>
            </div>
            <div class="col-md-4">

                <label class="form-label">Smell:</label>
                <select  name="smell" class="taxes form-group form-control select2">
                    <option value="">Select Smell</option>
                    <option value="2">Smell 1</option>
                    <option value="3">Smell 1</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top: 10px;">
        <div class="col-md-4">

            <label class="form-label">Printing:</label>
            <br>
            <label>
                <input type="radio" name="printing" value="1"> Ok
            </label>
            <br>
            <label>
                <input type="radio" name="printing" value="0"> Not Ok
            </label>
        </div>
        <div class="col-md-4">

            <label class="form-label">Bottom Stitching:</label>
            <br>
            <label>
                <input type="radio" name="bottom_stitching" value="1"> Ok
            </label>
            <br>
            <label>
                <input type="radio" name="bottom_stitching" value="0"> Not Ok
            </label>
        </div>
        <div class="col-md-4">

            <label class="form-label">Ready to Pack:</label>
            <br>
            <label>
                <input type="radio" name="ready_to_pack" value="1"> Yes
            </label>
            <br>
            <label>
                <input type="radio" name="ready_to_pack" value="0"> No
            </label>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" style="margin-top: 10px; margin-bottom: 10px;">
            <label for="remarks">Remarks:</label>
            <textarea id="remarks" class="form-control" name="remarks" rows="4" cols="50" placeholder=""></textarea>
        </div>
    </div>
    @if(auth()->user()->can('approve') || $purchaseOrderReceivingData->purchase_order_receiving->created_by == auth()->user()->id)
    <div class="row" style="margin-bottom: 30px;">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Accepted Qty:</label>
                <input type="text" name="accepted_quantity" id="accepted_quantity" onkeyup="calculateQcQty('accepted')" value="" class="form-control" >
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Rejected Qty:</label>
                <input type="text" name="rejected_quantity" id="rejected_quantity" onkeyup="calculateQcQty('rejected')" value="" class="form-control" >
            </div>
        </div>
        @canApprove('qc')
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Deduction Per Bag:</label>
                <input type="text" name="deduction_per_bag" id="deduction_per_bag" value="{{ $purchaseOrderReceivingData?->qc?->deduction_per_bag ?? 0 }}"
                    class="form-control">
            </div>
        </div>
        @endcanApprove
    </div>
    @endif
    {{-- @can("approve")

    @endcan --}}
    <div class="row bottom-button-bar" style="padding-bottom: 20px;">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>
<script>
    function calculateTotalWeight(element) {
        const el = $(element);
        
        const net_weight = el.closest("tr").find("#net_weight");
        const bag_weight = el.closest("tr").find("#bag_weight");

        if(!net_weight.val() || !bag_weight.val()) return;

        const total_weight = el.closest("tr").find("#total_weight");

        const result =  (parseFloat(net_weight.val()) / parseFloat(bag_weight.val())).toFixed(2);

        total_weight.val(result);

        updateOverallWeights();
    }

    function updateOverallWeights() {
        let total = 0;
        let count = 0;
        $('input[name="total_weight[]"]').each(function() {
            let val = parseFloat($(this).val());
            if (!isNaN(val) && val > 0) {
                total += val;
                count++;
            }
        });
        
        let avg = count > 0 ? (total / count).toFixed(2) : 0;
        // $('#average_weight_of_1_bag').val(avg);
        
        // Show average of 'average weight of 1 bag (grams)' in place of 'total weight received (kg)'
        $('#total_weight_received').val(avg);
    }

    function calculate_total_recieved_weight(el) {
        const average_weight = $(el).val();
        const total_bags = $("#total_bags").val();
        const total_weight_received = $("#total_weight_received");
        const result = (parseFloat(average_weight) * parseFloat(total_bags)) / 1000;

        if(isNaN(result)) {
            total_weight_received.val("");
        } else{
            total_weight_received.val(result.toFixed(2));
        }
    }
    
    function calculateQcQty(type) {
        let totalBags = parseFloat($('#total_bags').val()) || 0;
        let acceptedQty = $('#accepted_quantity');
        let rejectedQty = $('#rejected_quantity');

        if (type === 'accepted') {
            let accepted = parseFloat(acceptedQty.val()) || 0;
            if (accepted > totalBags) {
                acceptedQty.val(totalBags);
                accepted = totalBags;
            }
            rejectedQty.val(totalBags - accepted);
        } else {
            let rejected = parseFloat(rejectedQty.val()) || 0;
            if (rejected > totalBags) {
                rejectedQty.val(totalBags);
                rejected = totalBags;
            }
            acceptedQty.val(totalBags - rejected);
        }
    }
    $(".select2").select2();
</script>
