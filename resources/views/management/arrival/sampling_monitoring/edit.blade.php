<form action="{{ route('sampling-monitoring.update', $arrivalSamplingRequest->id) }}" method="POST" id="ajaxSubmit"
    autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.sampling-monitoring') }}" />
    <div class="row form-mar">
        <div class="col-12">
            <h6 class="header-heading-sepration">
                Ticket Detail
            </h6>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <fieldset data-toggle="collapse" href="#collapse11" aria-expanded="false" aria-controls="collapse11">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button class="btn btn-primary" type="button">
                            Ticket No#
                            <i class="fa fa-chevron-down ml-2 toggle-icon" aria-hidden="true"></i>
                        </button>
                    </div>
                    <input type="text" disabled class="form-control"
                        value="{{ optional($arrivalSamplingRequest->arrivalTicket)->unique_no }}"
                        placeholder="Button on left">
                </div>
            </fieldset>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div id="collapse11" role="tabpanel" aria-labelledby="headingCollapse11" class="collapse " style="">
                <div class="row form-mar">
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group">
                            <label>Supplier:</label>
                            <input type="text" class="form-control" name="supplier"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->supplier_name }}">
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group">
                            <label>Broker:</label>
                            <input type="text" class="form-control" name="broker"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->broker_name }}">
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4 full">
                        <div class="form-group">
                            <label class="d-block">Contract Detail:</label>
                            <select name="arrival_purchase_order_id" id="arrival_purchase_order_id"
                                class="form-control select2 ">
                                <option value="">N/A</option>
                                @foreach ($arrivalPurchaseOrders as $arrivalPurchaseOrder)
                                    <option data-saudatypeid="{{ $arrivalPurchaseOrder->sauda_type_id }}"
                                        @selected(optional($arrivalSamplingRequest->arrivalTicket)->arrival_purchase_order_id == $arrivalPurchaseOrder->id) value="{{ $arrivalPurchaseOrder->id }}">
                                        {{ $arrivalPurchaseOrder->unique_no }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4 full">
                        <div class="form-group">
                            <label class="d-block">Sauda Type:</label>
                            <input type="hidden" name="sauda_type_id" id="actual_sauda_type_id"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->sauda_type_id ?? '' }}">
                            <select name="sauda_type_id_display" id="sauda_type_id" @disabled(optional($arrivalSamplingRequest->arrivalTicket)->sauda_type_id ??
                                    (null && optional($arrivalSamplingRequest->arrivalTicket)->arrival_purchase_order_id ?? null))
                                class="form-control w-100 select2">
                                <option value="">N/A</option>
                                @foreach ($saudaTypes as $saudaType)
                                    <option @selected(optional($arrivalSamplingRequest->arrivalTicket)->sauda_type_id == $saudaType->id) value="{{ $saudaType->id }}">
                                        {{ $saudaType->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label class="d-block">Accounts Of:</label>
                            <input type="text" name="accounts_of" disabled
                                value=" {{ optional($arrivalSamplingRequest->arrivalTicket)->accountsOf->name ?? '' }}"
                                placeholder="Truck No" class="form-control" autocomplete="off" />
                        </div>
                    </div>

                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Station:</label>
                            <input type="text" name="station_name" placeholder="Station" class="form-control"
                                disabled autocomplete="off"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->station_name }}" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Truck Type:</label>
                            <input type="text" name="arrival_truck_type_id" placeholder="Truck Type" disabled
                                class="form-control" autocomplete="off"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->truckType->name ?? '' }}" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Truck No:</label>
                            <input type="text" name="truck_no"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->truck_no }}" disabled
                                placeholder="Truck No" class="form-control" autocomplete="off" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Bilty No: </label>
                            <input type="text" name="bilty_no" disabled
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->bilty_no }}"
                                placeholder="Bilty No" class="form-control" autocomplete="off" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>No of bags: </label>
                            <input type="text" name="bags" placeholder="No of bags" class="form-control"
                                disabled autocomplete="off"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->bags }}" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>Sample Money: </label>
                            <input type="text" readonly name="sample_money"
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->truckType->sample_money ?? 0 }}"
                                placeholder="No of bags" class="form-control" autocomplete="off" />
                        </div>
                    </div>
                    <div class="col-xs-4 col-sm-4 col-md-4">
                        <div class="form-group ">
                            <label>LOading Date: (Optional)</label>
                            <input type="date" name="loading_date" disabled
                                value="{{ optional($arrivalSamplingRequest->arrivalTicket)->loading_date }}"
                                placeholder="Bilty No" class="form-control" autocomplete="off" />
                        </div>
                    </div>

                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <div class="form-group ">
                            <label>Ticket Remarks:</label>
                            <textarea name="remarks" row="2" class="form-control" disabled placeholder="Remarks">{{ optional($arrivalSamplingRequest->arrivalTicket)->remarks }}</textarea>
                        </div>
                    </div>


                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group">
                        <label>Product:</label>
                        <input type="text" disabled="" class="form-control"
                            value="ITEM: {{ optional(optional($arrivalSamplingRequest->arrivalTicket)->product)->name }}"
                            placeholder="Button on left">
                    </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12">
                    <div class="form-group ">
                        <label>Status:</label>
                        <select name="stage_status" id="stage_status" class="form-control select2 ">
                            <option value="" hidden>Choose Status</option>
                            <option value="approved">Approved</option>
                            <option value="resampling">Request Resampling</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <div id="slabsContainer">
        <div class="row">

            <div class="col-12">
                <h6 class="header-heading-sepration">
                    QC Checklist
                </h6>
            </div>
        </div>
        <div class="row w-100 mx-auto">
            <div class="col-md-4">

            </div>
            <div class="col-md-3 py-2 QcResult">
                <h6>Result</h6>
            </div>
            <div class="col-md-3 py-2 Suggested">
                <h6>Suggested Deduction</h6>
            </div>
            <div class="col-md-2 py-2 QcResult">
                <h6>Deduction</h6>
            </div>

        </div>
        <div class="striped-rows">
            @if (count($results) != 0)
            
                @foreach ($results as $slab)
                    <?php
                    // dd(optional($arrivalSamplingRequest->arrivalTicket)->product->id, $slab->checklist_value, $slab->slabType->id);
                    $getDeductionSuggestion = getDeductionSuggestion($slab->slabType->id, optional($arrivalSamplingRequest->arrivalTicket)->product->id, $slab->checklist_value);
                    
                  
                    ?>
                    <div class="form-group row">
                        <input type="hidden" name="product_slab_type_id[]" value="{{ $slab->slabType->id }}">
                        <label class="col-md-4 label-control font-weight-bold"
                            for="striped-form-1">{{ $slab->slabType->name }}</label>
                        <div class="col-md-3 QcResult">
                            <input type="text" id="striped-form-1" readonly class="form-control"
                                name="checklist_value[]" value="{{ $slab->checklist_value }}" placeholder="%">
                        </div>
                        <div class="col-md-3 Suggested">
                            <input type="text" id="striped-form-1" readonly class="form-control" placehold
                                name="suggested_value[]" value="{{ $getDeductionSuggestion->deduction_value ?? 0 }}"
                                placeholder="Suggested Deduction">
                        </div>
                        <div class="col-md-2 QcResult">
                            <input type="text" id="striped-form-1" class="form-control bg-white" placehold
                                name="applied_deduction[]" value="{{ $slab->applied_deduction ?? 0 }}"
                                placeholder="Deduction">
                        </div>
                    </div>
                @endforeach
            @else
                <div class="alert alert-warning">
                    No Slabs Found
                </div>
            @endif
        </div>

        <br>
        <div class="row w-100 mx-auto">
            <div class="col-md-4">

            </div>
            <div class="col-md-6 py-2 QcResult">
                <h6>Result</h6>
            </div>
            <div class="col-md-2 py-2 QcResult">
                <h6>Deduction</h6>
            </div>

        </div>
        <div class="striped-rows">
            @if (count($Compulsuryresults) != 0)
                @foreach ($Compulsuryresults as $slab)
                    {{-- @dd($slab); --}}
                    <?php
                    //  $getDeductionSuggestion = getDeductionSuggestion($slab->slabType->id, optional($arrivalSamplingRequest->arrivalTicket)->product->id, $slab->checklist_value);
                    ?>
                    <div class="form-group row">
                        <input type="hidden" name="compulsory_param_id[]" value="{{ $slab->qcParam->id }}">
                        <label class="col-md-4 label-control font-weight-bold"
                            for="striped-form-1">{{ $slab->qcParam->name }}</label>
                        <div class="col-md-6 QcResult">
                            @if ($slab->qcParam->type == 'dropdown')
                                <input type="text" id="striped-form-1" readonly class="form-control"
                                    name="compulsory_checklist_value[]" value="{{ $slab->compulsory_checklist_value }}"
                                    placeholder="%">
                            @else
                                <textarea type="text" id="striped-form-1" readonly class="form-control" name="compulsory_checklist_value[]"
                                    placeholder="%"> {{ $slab->compulsory_checklist_value }}</textarea>
                            @endif
                        </div>

                        <div class="col-md-2 QcResult">
                            <input type="text" id="striped-form-1" class="form-control bg-white" placehold
                                name="compulsory_aapplied_deduction[]" value="{{ $slab->applied_deduction ?? 0 }}"
                                placeholder="Deduction">
                        </div>
                    </div>
                @endforeach
            @else
                <div class="alert alert-warning">
                    No Slabs Found
                </div>
            @endif
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="header-heading-sepration">
                    Other Details
                </h6>
            </div>
            <div class="col-12 px-3">
                <div class="form-group ">
                    <label>Sample Taken By:</label>
                    <select name="sample_taken_by" id="sample_taken_by" class="form-control select2" disabled>
                        <option value="">Sample Taken By</option>
                        @foreach ($sampleTakenByUsers as $sampleTakenUser)
                            <option @selected($arrivalSamplingRequest->sample_taken_by == $sampleTakenUser->id) value="{{ $sampleTakenUser->id }}">
                                {{ $sampleTakenUser->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-12 px-3">
                <div class="form-group ">
                    <label>Sample Analysis By: </label>
                    <input type="text" readonly disabled name="sample_analysis_by"
                        placeholder="Sample Analysis By" class="form-control" autocomplete="off"
                        value="{{ auth()->user()->name ?? '' }}" />
                </div>
            </div>
            <div class="col-12 px-3">
                <div class="form-group ">
                    <label>Party Ref. No: </label>
                    <select name="party_ref_no" id="party_ref_no" class="form-control select2" disabled>
                        <option value="{{ $arrivalSamplingRequest->party_ref_no }}">
                            {{ $arrivalSamplingRequest->party_ref_no }}</option>
                    </select>
                </div>
            </div>
        </div>

    </div>



    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Your Remarks (Optional):</label>
                <textarea name="remarks" row="4" class="form-control" placeholder="Description">{{ $arrivalSamplingRequest->remark }}</textarea>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>



<script>
    $(document).ready(function() {
        $('#arrival_sampling_request_id').change(function() {
            var samplingRequestId = $(this).val();

            if (samplingRequestId) {
                $.ajax({
                    url: '{{ route('getSlabsByProduct') }}',
                    type: 'GET',
                    data: {
                        sampling_request_id: samplingRequestId
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching slabs.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            // Append the rendered HTML to a container element
                            $('#slabsContainer').html(response.html);
                        } else {
                            Swal.fire("No Data", "No slabs found for this product.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            }
        });

        $('.select2').select2();

        $(document).on('change', '[name="arrival_purchase_order_id"]', function() {
            let saudaTypeId = $(this).find(':selected').data('saudatypeid');

            let $saudaTypeSelect = $('#sauda_type_id');
            let $hiddenInput = $('#actual_sauda_type_id');

            if (saudaTypeId) {
                $saudaTypeSelect.val(saudaTypeId).trigger('change');
                $saudaTypeSelect.prop('disabled', true);
                $hiddenInput.val(saudaTypeId);
            } else {
                $saudaTypeSelect.prop('disabled', false).val('').trigger('change');
                $hiddenInput.val('');
            }
        });

        initializeDynamicSelect2('#party_ref_no', 'arrival_custom_sampling', 'party_ref_no', 'party_ref_no',
            true,
            false);
        //initializeDynamicSelect2('#sampling_request_id', 'arrival_sampling_requests', 'name', 'id', false, false);
        //initializeDynamicSelect2('#supplier_name', 'suppliers', 'name', 'name', true, false);
        //initializeDynamicSelect2('#broker_name', 'brokers', 'name', 'name', true, false);
        //  function initializeDynamicSelect2(selector, tableName, columnName, idColumn = 'id', enableTags = false, isMultiple = true) {
    });
</script>

<style>
    .Suggested {
        background: #00990078;
    }

    .QcResult {
        background: #8080802b;
    }
</style>
