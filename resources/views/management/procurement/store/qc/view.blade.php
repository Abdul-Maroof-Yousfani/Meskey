        <div style="padding-left: 10px; padding-right: 10px;">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Date:</label>
                        <input type="date" name="date" readonly value="{{ $purchaseOrderReceivingData->qc->date }}"
                            id="date" class="form-control">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">GRN:</label>
                        <input type="text" name="grn" id="grn" value="{{ $grn }}" readonly
                            class="form-control">
                    </div>
                </div>
            </div>
            <div class="row" style="margin-top: 10px;">
                <div class="col-md-12">
                    <table class="table table-bordered" id="purchaseRequestTable">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Size</th>
                                <th>Brand</th>
                                <th>Job Order</th>
                                <th>Required Weight Per Bag</th>
                                <th>Average Weight of 1 Bag</th>
                                <th>Total Bags</th>
                                <th>Total Weight Required (Kg)</th>
                                <th>Total Weight Received (Kg)</th>
                            </tr>
                        </thead>
                        <tbody id="purchaseOrderBody">
                            <tr>
                                    <td>
                                <input type="text" name="item" id="item" value="{{ getItem($purchaseOrderReceivingData->item_id)->name }}" readonly
                                    class="form-control">
                            </td>
                            <td>
                                <input type="text" name="size" id="size" value="{{ $purchaseOrderReceivingData?->purchase_order_data?->size ?? null }}" readonly
                                    class="form-control">
                            </td>
                            <td>
                                <input type="text" name="brand" id="brand" value="{{ $purchaseOrderReceivingData?->purchase_order_data?->brand ?? null }}" readonly
                                    class="form-control">
                            </td>
                            @php
                                $data = $purchaseOrderReceivingData->purchase_order_data->purchase_request_data->JobOrder;
                                $string = "";
                                foreach($data as $datum) {
                                    $string .= $datum->job_order_data->job_order_no . ", ";
                                }

                            @endphp
                            <td>
                                <input type="text" name="job_order" id="job_order" value="{{ trim($string, ", ") }}" readonly
                                    class="form-control">
                            </td>
                            <td>
                                <input type="text" name="required_weight_per_bag" value="{{ $purchaseOrderReceivingData?->purchase_order_data?->min_weight ?? null }}" id="required_weight_per_bag" readonly class="form-control">
                            </td>

                            <td>
                                <input type="text" name="average_weight_of_one_bag" value="{{ $purchaseOrderReceivingData?->qc?->average_weight_of_one_bag }}" onkeyup="calculate_total_recieved_weight(this)" id="average_weight_of_1_bag"
                                     class="form-control" placeholder="Average Weight of One Bag" readonly>
                            </td>

                            <td>
                                <input type="text" name="total_bags" id="total_bags" value="{{ $purchaseOrderReceivingData?->qty }}" readonly
                                    class="form-control">
                            </td>

                            <td>
                                <input type="text" name="total_weight_required" value="{{ (($purchaseOrderReceivingData?->qty ?? 0) * ($purchaseOrderReceivingData?->purchase_order_data?->min_weight ?? 0)) / 1000 }}" id="total_weight_required" value="Total Weight Required"
                                    readonly class="form-control">
                            </td>

                            <td>
                                <input type="text" name="total_weight_received" id="total_weight_received" value="{{ ($purchaseOrderReceivingData?->qty * $purchaseOrderReceivingData?->qc?->average_weight_of_one_bag) / 1000 }}"
                                    readonly class="form-control">
                            </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

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
                        @php
                            $bags = $purchaseOrderReceivingData->qc?->bags?->toArray();

                        @endphp
                        <tbody id="purchaseOrderBody">
                            @for ($i = 0; $i < 5; $i++)

                                @php
                                    $net_weight = $bags[$i]["net_weight"] ?? 0;
                                    $bag_weight = $bags[$i]["bag_weight"] ?? 0;
                                @endphp
                                <tr>
                                    <td style="width: 100px;">
                                        <input type="text" name="item" style="text-align: center" id="item"
                                            value="{{ $i + 1 }}" readonly class="form-control">
                                    </td>
                                    <td>
                                        <input type="text" name="net_weight[]"
                                            value="{{ $bags[$i]['net_weight'] ?? '' }}" id="net_weight"
                                            placeholder="Net Weight" readonly class="form-control">
                                    </td>
                                    <td>
                                        <input type="text" name="bag_weight[]"
                                            value="{{ $bags[$i]['bag_weight'] ?? '' }}" id="bag_weight"
                                            placeholder="Bag Weight" readonly class="form-control" >
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
                               @php
                                    $net_weight = $bags[$i]["net_weight"] ?? 0;
                                    $bag_weight = $bags[$i]["bag_weight"] ?? 0;
                                @endphp
                                <tr>
                                    <td style="width: 100px; text-align: center;">
                                        <input type="text" name="item" style="text-align: center"
                                            id="item" value="{{ $i + 1 }}" readonly
                                            class="form-control">
                                    </td>
                                    <td>
                                        <input type="text" name="net_weight[]"
                                            value="{{ $bags[$i]['net_weight'] ?? '' }}" id="net_weight"
                                            placeholder="Net Weight" readonly class="form-control">
                                    </td>
                                    <td>
                                        <input type="text" name="bag_weight[]"
                                            value="{{ $bags[$i]['bag_weight'] ?? '' }}" id="bag_weight"
                                            placeholder="Bag Weight"  class="form-control" readonly>
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


            <p style="margin-top: 20px; font-size: 20px;">Additional Data</p>
            <div class="row" style="margin-top: 10px;">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Size:</label>
                        <input type="text" name="size" id="size"
                            value="{{ $purchaseOrderReceivingData->qc->size }}" readonly class="form-control">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Phy/Che/Bio:</label>
                        <input type="text" name="bio" id="bio"
                            value="{{ $purchaseOrderReceivingData->qc->bio }}" readonly class="form-control">
                    </div>
                </div>
                <div class="col-md-4">

                    <label class="form-label">Smell:</label>
                    <select name="smell" readonly class="taxes form-group form-control select2">
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
                    <input type="radio" name="printing" disabled @checked($purchaseOrderReceivingData->qc->printing == 1) value="1"> Ok
                </label>
                <br>
                <label>
                    <input type="radio" name="printing" disabled @checked($purchaseOrderReceivingData->qc->printing == 0) value="0"> Not
                    Ok
                </label>
            </div>
            <div class="col-md-4">

                <label class="form-label">Bottom Stitching:</label>
                <br>
                <label>
                    <input type="radio" name="bottom_stitching" disabled @checked($purchaseOrderReceivingData->qc->bottom_stitching == 1)
                        value="1"> Ok
                </label>
                <br>
                <label>
                    <input type="radio" name="bottom_stitching" disabled @checked($purchaseOrderReceivingData->qc->bottom_stitching == 0)
                        value="0"> Not Ok
                </label>
            </div>
            <div class="col-md-4">

                <label class="form-label">Ready to Pack:</label>
                <br>
                <label>
                    <input type="radio" name="ready_to_pack" disabled @checked($purchaseOrderReceivingData->qc->ready_to_pack == 1) value="1">
                    Yes
                </label>
                <br>
                <label>
                    <input type="radio" name="ready_to_pack" disabled @checked($purchaseOrderReceivingData->qc->ready_to_pack == 0) value="0">
                    No
                </label>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12" style="margin-top: 10px; margin-bottom: 10px;">
                <label for="remarks">Remarks:</label>
                <textarea id="remarks" class="form-control" readonly name="remarks" rows="4" cols="50"
                    placeholder="">{{ $purchaseOrderReceivingData->qc->remarks }}</textarea>
            </div>
        </div>

        <form action="{{ route('store.qc.update-amount') }}" method="POST" id="ajaxSubmit2">
           
            @if ($purchaseOrderReceivingData->qc->canApprove() || $type == "view")
                <input type="hidden" name="id" value="{{ $purchaseOrderReceivingData->qc->id }}" />

                <input type="hidden" name="total_bags" id="total_bags"
                    value="{{ $purchaseOrderReceivingData?->qty }}" readonly
                    class="form-control">

                <div class="row" style="margin-top: 10px; margin-bottom: 30px;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Accepted Qty:</label>
                            <input type="text" name="accepted_quantity" id="accepted_quantity"
                                value="{{ $purchaseOrderReceivingData->qc->accepted_quantity }}"
                                class="form-control" @readonly($type == "view")>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Rejected Qty:</label>
                            <input type="text" name="rejected_quantity" id="rejected_quantity"
                                value="{{ $purchaseOrderReceivingData->qc->rejected_quantity }}"
                                class="form-control" @readonly($type == "view")>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Deduction Per Bag:</label>
                            <input type="text" name="deduction_per_bag" id="deduction_per_bag"
                                value="{{ $purchaseOrderReceivingData->qc->deduction_per_bag }}"
                                class="form-control" @readonly($type == "view")>
                        </div>
                    </div>
                </div>
                @endif
        </form>


        @if($type != "view")
            <div class="row">
                <div class="col-12">
                    <x-approval-status-and-saved :model="$purchaseOrderReceivingData->qc" />
                </div>
            </div>
        @endif
        <div class="row bottom-button-bar" style="padding-bottom: 20px;">
            &nbsp;
        </div>
        
        <script>
            
         
            $(".select2").select2();
        </script>
