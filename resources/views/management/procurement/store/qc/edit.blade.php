@php
    $previousUrl = url()->previous();
    $refreshRoute = str_contains($previousUrl, 'procurement/store/qc') 
        ? route('store.qc.getList') 
        : route('store.purchase-qc.getList');
@endphp
<form action="{{ route('store.qc.update') }}" id="ajaxSubmit">
    <input type="hidden" id="listRefresh" value="{{ $refreshRoute }}">
    <input type="hidden" name="purchase_receiving_data_id" value="{{ $id }}">
<div style="padding-left: 10px; padding-right: 10px;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Date:</label>
                    <input type="date" name="date" value="{{ $purchaseOrderReceivingData->qc->date }}" id="date" class="form-control" readonly>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">GRN:</label>
                    <input type="text" name="grn" id="grn" value="{{ $grn }}" readonly class="form-control">
                </div>
            </div>
        </div>
        @canApprove('qc')
        <div class="row" style="margin-top: 10px; margin-bottom: 20px;">
            <div class="col-md-12">
                <table class="table table-bordered">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th>Qty</th>
                            <th>Supplier Name</th>
                            <th>PO Number</th>
                            <th>Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $purchaseOrderReceivingData->qty }}</td>
                            <td>{{ $purchaseOrderReceivingData->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $purchaseOrderReceivingData?->purchase_order_receiving?->purchase_order?->purchase_order_no ?? 'N/A' }}</td>
                            <td>{{ $purchaseOrderReceivingData?->purchase_order_data?->rate ?? '0' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endcanApprove
        <div class="row" style="margin-top: 10px;">
            <div class="col-md-12">
            <div style="overflow-x: auto; width: 100%;">
                <table class="table table-bordered" id="purchaseRequestTable" style="min-width: 2500px;">
                    <thead>
                        <tr>
                            <th style="min-width: 300px;">Item</th>
                            @if($purchaseOrderReceivingData->category_id == 38)
                            <th style="min-width: 200px;">Size</th>
                            <th style="min-width: 200px;">Brand</th>
                            <th style="min-width: 250px;">Job Order</th>
                            @endif
                            <th style="min-width: 200px;">DC No</th>
                            @if($purchaseOrderReceivingData->category_id == 38)
                            <th style="min-width: 250px;">Required Weight Per Bag (grams)</th>
                            <!-- <th>Tolerance</th> -->
                            <th style="min-width: 250px;">Average Weight of 1 Bag (grams)</th>
                            @endif
                            @if($purchaseOrderReceivingData->category_id == 38)
                            <th style="min-width: 150px;">Total Bags</th>
                            @endif
                            @if($purchaseOrderReceivingData->category_id == 38)
                            <th style="min-width: 250px;">Total Weight Required (Kg)</th>
                            <th style="min-width: 250px;">Sample Average Weight (grams)</th>
                            @endif
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

                            <td>
                                <input type="text" name="job_order" id="job_order" value="JOB-KHI-11-2025-0001" readonly
                                    class="form-control">
                            </td>
                            @endif

                            <td>
                                <input type="text" name="dc_no" id="dc_no" value="{{ $purchaseOrderReceivingData->purchase_order_receiving->dc_no }}" readonly
                                    class="form-control">
                            </td>
                            @if($purchaseOrderReceivingData->category_id == 38)
                            <td>
                                <input type="text" name="required_weight_per_bag" value="{{ $purchaseOrderReceivingData?->purchase_order_data?->min_weight ?? null }}" id="required_weight_per_bag" readonly class="form-control">
                            </td>
                            <!-- <td>
                                <input type="text" name="tolerance" value="{{ $purchaseOrderReceivingData->tolerance ?? 0 }}" readonly class="form-control">
                            </td> -->

                            <td>
                                <input type="text" name="average_weight_of_one_bag" value="{{ (round(($purchaseOrderReceivingData->receive_weight * 1000) / $purchaseOrderReceivingData->qty, 2)) }}" onkeyup="calculate_total_recieved_weight(this)" id="average_weight_of_1_bag"
                                     class="form-control" placeholder="Average Weight of One Bag" readonly>
                            </td>
                            @endif

                            @if($purchaseOrderReceivingData->category_id == 38)
                            <td>
                                <input type="text" name="total_bags" id="total_bags" value="{{ $purchaseOrderReceivingData->qty }}" readonly
                                    class="form-control">
                            </td>
                            @else
                                <input type="hidden" name="total_bags" id="total_bags" value="{{ $purchaseOrderReceivingData->qty }}">
                            @endif

                            @if($purchaseOrderReceivingData->category_id == 38)
                            <td>
                                <input type="text" name="total_weight_required" value="{{ (($purchaseOrderReceivingData->qty ?? 0) * ($purchaseOrderReceivingData?->purchase_order_data?->min_weight / 1000 ?? 0)) }}" id="total_weight_required" value="Total Weight Required"
                                    readonly class="form-control">
                            </td>

                            <td>
                                <input type="text" name="sample_average_weight" id="total_weight_received" value="{{ $purchaseOrderReceivingData?->qc?->sample_average_weight ?? (round($purchaseOrderReceivingData?->receive_weight / ($purchaseOrderReceivingData?->qty ?: 1), 2)) / 1000 }}"
                                    readonly class="form-control">
                            </td>
                            @endif

                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>

        @if($purchaseOrderReceivingData->category_id == 38)
        <p style="margin-top: 20px; font-size: 20px;">Weight of randomly-selected 10-bags sets</p>
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
                        @php
                            $bags = $purchaseOrderReceivingData->qc?->bags?->toArray();
                            
                        @endphp
                        @for ($i = 0; $i < 5; $i++)
                            <tr>
                                <td style="width: 100px;">
                                    <input type="text" name="item" style="text-align: center" id="item"
                                        value="{{ $i + 1 }}" readonly class="form-control">
                                </td>
                                @php
                                    $net_weight = $bags[$i]["net_weight"] ?? 0;
                                    $bag_weight = $bags[$i]["bag_weight"] ?? 0;
                                @endphp
                                <td>
                                    <input type="text" onkeyup="calculateTotalWeight(this)" value="{{ $net_weight == 0 ? '' : $net_weight }}" name="net_weight[]" id="net_weight" placeholder="Net Weight"
                                        class="form-control" required>
                                </td>
                                <td>
                                    <input type="text" onkeyup="calculateTotalWeight(this)" name="bag_weight[]" value="{{ $bag_weight == 0 ? '' : $bag_weight }}" id="bag_weight" placeholder="Bag Weight"
                                        class="form-control" required>
                                </td>

                                <td>
                                    <input type="text" name="total_weight[]" value="{{ $bag_weight > 0 ? round($net_weight / $bag_weight, 2) : '' }}" id="total_weight" placeholder="Bag Weight"
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
                                <td style="width: 100px;">
                                    <input type="text" name="item" style="text-align: center" id="item"
                                        value="{{ $i + 1 }}" readonly class="form-control">
                                </td>
                                @php
                                    $net_weight = $bags[$i]["net_weight"] ?? 0;
                                    $bag_weight = $bags[$i]["bag_weight"] ?? 0;
                                @endphp
                                <td>
                                    <input type="text" onkeyup="calculateTotalWeight(this)" value="{{ $net_weight == 0 ? '' : $net_weight }}" name="net_weight[]" id="net_weight" placeholder="Net Weight"
                                        class="form-control" required>
                                </td>
                                <td>
                                    <input type="text" onkeyup="calculateTotalWeight(this)" name="bag_weight[]" value="{{ $bag_weight == 0 ? '' : $bag_weight }}" id="bag_weight" placeholder="Bag Weight"
                                        class="form-control" required>
                                </td>

                                <td>
                                    <input type="text" name="total_weight[]" value="{{ $bag_weight > 0 ? round($net_weight / $bag_weight, 2) : '' }}" id="total_weight" placeholder="Bag Weight"
                                        class="form-control" readonly>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
        @else
            @for ($i = 0; $i < 10; $i++)
                <input type="hidden" name="net_weight[]" value="0">
                <input type="hidden" name="bag_weight[]" value="0">
                <input type="hidden" name="total_weight[]" value="0">
            @endfor
        @endif


        <p style="margin-top: 20px; font-size: 20px;">Additional Data</p>
        <div class="row" style="margin-top: 10px;">
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Size:</label>
                    <input type="text" name="size" id="size" onkeyup="calculateTotalWeight(this)" value="{{ $purchaseOrderReceivingData->qc->size }}" class="form-control">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Phy/Che/Bio:</label>
                    <input type="text" name="bio" id="bio" onkeyup="calculateTotalWeight(this)" value="{{ $purchaseOrderReceivingData->qc->bio }}" class="form-control">
                </div>
            </div>
            <div class="col-md-4">

                <label class="form-label">Smell:</label>
                <select  name="smell" class="taxes form-group form-control select2">
                    <option value="">Select Smell</option>
                    <option value="2" @selected($purchaseOrderReceivingData->qc->smell == '2')>Smell 1</option>
                    <option value="3" @selected($purchaseOrderReceivingData->qc->smell == '3')>Smell 1</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top: 10px;">
        <style>
            .qc-not-ok {
                background-color: #ffcccc !important;
                color: #b30000 !important;
                padding: 2px 8px;
                border-radius: 4px;
                font-weight: bold;
                display: inline-block;
            }
            .qc-ok {
                background-color: #e6ffed !important;
                color: #22863a !important;
                padding: 2px 8px;
                border-radius: 4px;
                font-weight: bold;
                display: inline-block;
            }
        </style>
        <div class="col-md-4">

            <label class="form-label">Printing:</label>
            <br>
            <label class="{{ $purchaseOrderReceivingData->qc->printing == 1 ? 'qc-ok' : '' }}">
                <input type="radio" class="qc-param" name="printing" @checked($purchaseOrderReceivingData->qc->printing == 1) value="1"> Ok
            </label>
            <br>
            <label class="{{ $purchaseOrderReceivingData->qc->printing == 0 ? 'qc-not-ok' : '' }}">
                <input type="radio" class="qc-param" name="printing" @checked($purchaseOrderReceivingData->qc->printing == 0) value="0"> Not Ok
            </label>
        </div>
        <div class="col-md-4">

            <label class="form-label">Bottom Stitching:</label>
            <br>
            <label class="{{ $purchaseOrderReceivingData->qc->bottom_stitching == 1 ? 'qc-ok' : '' }}">
                <input type="radio" class="qc-param" name="bottom_stitching" @checked($purchaseOrderReceivingData->qc->bottom_stitching == 1) value="1"> Ok
            </label>
            <br>
            <label class="{{ $purchaseOrderReceivingData->qc->bottom_stitching == 0 ? 'qc-not-ok' : '' }}">
                <input type="radio" class="qc-param" name="bottom_stitching" @checked($purchaseOrderReceivingData->qc->bottom_stitching == 0) value="0"> Not Ok
            </label>
        </div>
        <div class="col-md-4">

            <label class="form-label">Ready to Pack:</label>
            <br>
            <label class="{{ $purchaseOrderReceivingData->qc->ready_to_pack == 1 ? 'qc-ok' : '' }}">
                <input type="radio" class="qc-param" name="ready_to_pack" @checked($purchaseOrderReceivingData->qc->ready_to_pack == 1) value="1"> Yes
            </label>
            <br>
            <label class="{{ $purchaseOrderReceivingData->qc->ready_to_pack == 0 ? 'qc-not-ok' : '' }}">
                <input type="radio" class="qc-param" name="ready_to_pack" @checked($purchaseOrderReceivingData->qc->ready_to_pack == 0) value="0"> No
            </label>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" style="margin-top: 10px; margin-bottom: 10px;">
            <label for="remarks">Remarks:</label>
            <textarea id="remarks" class="form-control" name="remarks" rows="4" cols="50" placeholder="">{{ $purchaseOrderReceivingData->qc->remarks }}</textarea>
        </div>
    </div>

    <div class="row" style="margin-top: 10px; margin-bottom: 30px;">
        @if(auth()->user()->can('approve') || $purchaseOrderReceivingData->purchase_order_receiving->created_by == auth()->user()->id)
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Accepted Qty:</label>
                    <input type="text" name="accepted_quantity" id="accepted_quantity" onkeyup="calculateQcQty('accepted')" value="{{ $purchaseOrderReceivingData->qc->accepted_quantity }}" class="form-control" >
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Rejected Qty:</label>
                    <input type="text" name="rejected_quantity" id="rejected_quantity" onkeyup="calculateQcQty('rejected')" value="{{ $purchaseOrderReceivingData->qc->rejected_quantity }}" class="form-control" >
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
        @endif
    </div>
    <div class="row bottom-button-bar" style="padding-bottom: 20px;">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
            <a onclick="deleteQc('{{ $purchaseOrderReceivingData->qc?->id }}')" class="btn btn-warning submitbutton">Delete</a>
        </div>
    </div>
</form>
<script>
    function deleteQc(id) {
        Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("store.qc.remove") }}',
                method: 'DELETE',
                data: { id }, 
                success: function(response) {
                    Swal.fire({
                        title: "Deleted!",
                        text: "The record has been deleted successfully.",
                        icon: "success"
                    });
                    $(".modal-sidebar-close").trigger("click");
                    if (typeof filterationCommon === 'function' && '{{ $refresh_url }}') {
                        filterationCommon('{{ $refresh_url }}');
                    }
                },
                
                error: function(xhr) {
                    console.error('Failed:', xhr.status, xhr.responseText);
                    alert('Error ' + xhr.status + ': ' + (xhr.responseJSON?.message || 'Unknown error'));
                }
            });
        }
    })
        
    }
    function calculateTotalWeight(element) {
        const el = $(element);
        
        const net_weight = el.closest("tr").find("#net_weight");
        const bag_weight = el.closest("tr").find("#bag_weight");
        const total_weight = el.closest("tr").find("#total_weight");

        if(!net_weight.val() || !bag_weight.val()) {
            total_weight.val('');
            updateOverallWeights();
            return;
        }

        const bag_val = parseFloat(bag_weight.val());
        if (bag_val === 0) {
            total_weight.val('');
            updateOverallWeights();
            return;
        }

        const result = (parseFloat(net_weight.val()) / bag_val).toFixed(2);
        total_weight.val(result);

        updateOverallWeights();
    }

    function updateOverallWeights() {
        let totalNetWeight = 0;
        let totalBagsCount = 0;

        let netWeights = $('input[name="net_weight[]"]');
        let bagWeights = $('input[name="bag_weight[]"]');

        for(let i = 0; i < netWeights.length; i++) {
            let nw = parseFloat($(netWeights[i]).val());
            let bw = parseFloat($(bagWeights[i]).val());

            if (!isNaN(nw) && !isNaN(bw) && bw > 0) {
                totalNetWeight += nw;
                totalBagsCount += bw;
            }
        }

        let avg = totalBagsCount > 0 ? (totalNetWeight / totalBagsCount).toFixed(2) : 0;
        $('#average_weight_of_1_bag').val(avg);
        
        // Show results in Sample Average Weight
        $('#total_weight_received').val(avg);
    }
</script>
<script>
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

    $(document).on('change', '.qc-param', function() {
        let name = $(this).attr('name');
        $('input[name="' + name + '"]').each(function() {
            let label = $(this).closest('label');
            label.removeClass('qc-not-ok qc-ok');
            if ($(this).is(':checked')) {
                if ($(this).val() == 0) {
                    label.addClass('qc-not-ok');
                } else if ($(this).val() == 1) {
                    label.addClass('qc-ok');
                }
            }
        });
    });
</script>
