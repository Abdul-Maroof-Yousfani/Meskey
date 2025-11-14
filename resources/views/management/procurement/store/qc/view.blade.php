<form action="{{ route('store.qc.store') }}" id="ajaxSubmit">
    <input type="hidden" name="purchase_receiving_data_id" value="{{ $id }}"
    <div style="padding-left: 10px; padding-right: 10px;">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">Date:</label>
                    <input type="date" name="date" id="date" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="form-label">GRN:</label>
                    <input type="text" name="grn" id="grn" class="form-control">
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
                            <th>Average Weight of 100 Bags</th>
                            <th>Total Bags</th>
                            <th>Total Weight</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseOrderBody">
                        <tr>
                            <td>
                                <input type="text" name="item" id="item" value="Item Name" readonly
                                    class="form-control">
                            </td>
                            <td>
                                <input type="text" name="size" id="size" value="Size Value" readonly
                                    class="form-control">
                            </td>
                            <td>
                                <input type="text" name="brand" id="brand" value="Brand Name" readonly
                                    class="form-control">
                            </td>

                            <td>
                                <input type="text" name="job_order" id="job_order" value="Job Order" readonly
                                    class="form-control">
                            </td>
                            <td>
                                <input type="text" name="required_weight_per_bag" id="required_weight_per_bag"
                                    value="Required Weight Per Bag" readonly class="form-control">
                            </td>

                            <td>
                                <input type="text" name="average_weight_of_100_bag" id="average_weight_of_100_bag"
                                    value="Average Weight of 100 Bags" readonly class="form-control">
                            </td>

                            <td>
                                <input type="text" name="total_bags" id="total_bags" value="Total Bags" readonly
                                    class="form-control">
                            </td>

                            <td>
                                <input type="text" name="total_weight" id="total_weight" value="Total Weight"
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
                            <th>Net Weight</th>
                            <th>Bag Weight</th>
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
                                    <input type="text" name="size" id="size" placeholder="Net Weight"
                                        readonly class="form-control">
                                </td>
                                <td>
                                    <input type="text" name="brand" id="brand" placeholder="Bag Weight"
                                        readonly class="form-control">
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
                            <th>Net Weight</th>
                            <th>Bag Weight</th>
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
                                    <input type="text" name="size" id="size" placeholder="Net Weight"
                                        readonly class="form-control">
                                </td>
                                <td>
                                    <input type="text" name="brand" id="brand" placeholder="Bag Weight"
                                        readonly class="form-control">
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
                    <input type="text" name="size" id="size" class="form-control">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label class="form-label">Phy/Che/Bio:</label>
                    <input type="text" name="bio" id="bio" class="form-control">
                </div>
            </div>
            <div class="col-md-4">

                <label class="form-label">Smell:</label>
                <select class="taxes form-group form-control select2">
                    <option value="">Select Smell</option>
                    <option>Smell 1</option>
                    <option>Smell 1</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row" style="margin-top: 10px;">
        <div class="col-md-4">

            <label class="form-label">Printing:</label>
            <br>
            <label>
                <input type="radio" name="printing" value="cash"> Ok
            </label>
            <br>
            <label>
                <input type="radio" name="printing" value="credit"> Not Ok
            </label>
        </div>
        <div class="col-md-4">

            <label class="form-label">Bottom Stitching:</label>
            <br>
            <label>
                <input type="radio" name="bottom_stitching" value="cash"> Ok
            </label>
            <br>
            <label>
                <input type="radio" name="bottom_stitching" value="credit"> Not Ok
            </label>
        </div>
        <div class="col-md-4">

            <label class="form-label">Ready to Pack:</label>
            <br>
            <label>
                <input type="radio" name="ready_to_pack" value="cash"> Yes
            </label>
            <br>
            <label>
                <input type="radio" name="ready_to_pack" value="credit"> No
            </label>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" style="margin-top: 10px; margin-bottom: 10px;">
            <label for="remarks">Remarks:</label>
            <textarea id="remarks" class="form-control" name="remarks" rows="4" cols="50" placeholder=""></textarea>
        </div>
    </div>

    <div class="row" style="margin-top: 10px; margin-bottom: 30px;">
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Accepted Qty:</label>
                <input type="text" name="accepted_quantity" id="accepted_quantity" value="{{ $purchaseOrderReceivingData->qc->accepted_quantity }}" class="form-control" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Rejected Qty:</label>
                <input type="text" name="rejected_quantity" id="rejected_quantity" value="{{ $purchaseOrderReceivingData->qc->rejected_quantity }}" class="form-control" required>
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label class="form-label">Deduction Per Bag:</label>
                <input type="text" name="deduction_per_bag" value="{{ $purchaseOrderReceivingData->qc->deduction_per_bag }}" id="deduction_per_bag" class="form-control">
            </div>
        </div>
    </div>
    <div class="row bottom-button-bar" style="padding-bottom: 20px;">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>
<script>
    $(".select2").select2();
</script>
