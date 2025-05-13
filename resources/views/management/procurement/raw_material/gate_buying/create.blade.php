<form action="{{ route('raw-material.gate-buying.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('raw-material.get.purchase-order') }}" />

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Location:</label>
                <select name="company_location_id" id="company_location_id" class="form-control  ">
                    <option value="">Location</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Contract Date:</label>
                <input type="date" name="contract_date" placeholder="Contract Date" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Ref No:</label>
                <input type="text" name="ref_no" placeholder="Ref No" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>S No:</label>
                <input type="text" name="contract_no" readonly placeholder="S No." class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Supplier Name:</label>
                <input type="text" name="supplier_name" placeholder="Supplier Name" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Purchaser Name:</label>
                <input type="text" name="purchaser_name" placeholder="Purchaser Name" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Contact Person Name:</label>
                <input type="text" name="contact_person" placeholder="Contact Person Name" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Mobile No:</label>
                <input type="text" name="mobile_number" placeholder="Mobile #" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Broker Name:</label>
                <input type="text" name="broker_name" placeholder="Broker Name" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Brokery:</label>
                <input type="text" name="brokery" placeholder="Brokery" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Commodity:</label>
                <select name="commodity" class="form-control select2">
                    <option value="">Select Commodity</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-bag-weight="{{ $product->bag_weight_for_purchasing }}">
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Rate:</label>
                <div class="input-group">
                    <input type="number" name="rate" placeholder="Rate" class="form-control" />
                    <div class="input-group-append">
                        <select name="rate_unit" class="form-control">
                            <option value="per mound">per mound</option>
                            <option value="per kg">per kg</option>
                            <option value="per 100kg">per 100kg</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Truck No:</label>
                <input type="text" name="truck_number" placeholder="Truck #" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Moisture:</label>
                <input type="number" name="moisture" placeholder="Moisture" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Chalky:</label>
                <input type="number" name="chalky" placeholder="Chalky" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Mixing:</label>
                <input type="number" name="mixing" placeholder="Mixing" class="form-control" />
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Red Rice:</label>
                <input type="number" name="red_rice" placeholder="Red Rice" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Payment Term:</label>
                <select name="payment_term" class="form-control select2">
                    <option value="Cash Payment">Cash Payment</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Online">Online</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Remarks:</label>
                <textarea name="remarks" placeholder="Remarks" class="form-control"></textarea>
            </div>
        </div>
    </div>

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Prepared By:</label>
                <input type="text" name="prepared_by_display" value="{{ auth()->user()->name }}" disabled
                    placeholder="Prepared By" class="form-control" />
                <input type="hidden" name="prepared_by" value="{{ auth()->user()->id }}" readonly
                    placeholder="Prepared By" class="form-control" />
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
        $('.select2').select2();

        $('[name="company_location_id"], [name="contract_date"]').change(function() {
            generateContractNumber();
        });

        function generateContractNumber() {
            const locationId = $('[name="company_location_id"]').val();
            const contractDate = $('[name="contract_date"]').val();

            if (locationId && contractDate) {
                $.ajax({
                    url: '{{ route('raw-material.generate.contract.number') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        location_id: locationId,
                        contract_date: contractDate
                    },
                    beforeSend: function() {},
                    success: function(response) {
                        if (response.success) {
                            $('[name="contract_no"]').val(response.contract_no);
                        }
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                    }
                });
            }
        }

        initializeDynamicSelect2('#company_location_id', 'company_locations', 'name', 'id', true, false);
    });
</script>
