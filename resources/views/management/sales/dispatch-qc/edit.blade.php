<form action="{{ route('sales.dispatch-qc.update', $DispatchQc->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('sales.get.dispatch-qc') }}" />

    <div class="row form-mar">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Tickets:</label>
                <select class="form-control select2" name="loading_program_item_id" id="loading_program_item_id">
                    <option value="">Select Ticket</option>
                    @foreach ($Tickets as $ticket)
                        <option value="{{ $ticket->id }}" {{ $ticket->id == $DispatchQc->loading_program_item_id ? 'selected' : '' }}>
                            {{ $ticket->transaction_number }} -- {{ $ticket->truck_number }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row" id="ticketDataContainer" style="margin-left: 4px; margin-right: 4px;">
        <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>Customer:</label>
                    <input type="text" name="customer" value="{{ $DispatchQc->customer ?? '' }}" class="form-control" readonly />
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>Commodity:</label>
                    <input type="text" name="commodity" value="{{ $DispatchQc->commodity ?? '' }}" class="form-control" readonly />
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>SO Qty:</label>
                    <input type="number" name="so_qty" value="{{ $DispatchQc->so_qty ?? '' }}" class="form-control" readonly step="0.01" />
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>DO Qty:</label>
                    <input type="number" name="do_qty" value="{{ $DispatchQc->do_qty ?? '' }}" class="form-control" readonly step="0.01" />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-6">
                <div class="form-group">
                    <label>Factory:</label>
                    <select class="form-control select2 w-100" name="factory_display[]" id="factory_display" multiple disabled style="width: 100% !important;">
                        @php
                            $deliveryOrder = $DispatchQc->loadingProgramItem->loadingProgram->deliveryOrder ?? null;
                            $loadingProgramItem = $DispatchQc->loadingProgramItem ?? null;
                            
                            if ($deliveryOrder && $deliveryOrder->arrival_location_id) {
                                // Get from delivery order
                                $arrivalLocationIds = explode(',', $deliveryOrder->arrival_location_id);
                                $arrivalLocations = \App\Models\Master\ArrivalLocation::whereIn('id', $arrivalLocationIds)->get();
                                foreach($arrivalLocations as $location) {
                                    echo '<option value="' . $location->id . '" selected>' . $location->name . '</option>';
                                }
                            } elseif ($loadingProgramItem && $loadingProgramItem->arrival_location_id) {
                                // Fallback to loading program item
                                $arrivalLocation = \App\Models\Master\ArrivalLocation::find($loadingProgramItem->arrival_location_id);
                                if ($arrivalLocation) {
                                    echo '<option value="' . $arrivalLocation->id . '" selected>' . $arrivalLocation->name . '</option>';
                                }
                            }
                        @endphp
                    </select>
                    <input type="hidden" name="factory" value="{{ $DispatchQc->factory ?? '' }}" />
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6">
                <div class="form-group">
                    <label>Gala:</label>
                    <select class="form-control select2 w-100" name="gala_display[]" id="gala_display" multiple disabled style="width: 100% !important;">
                        @php
                            if ($deliveryOrder && $deliveryOrder->sub_arrival_location_id) {
                                // Get from delivery order
                                $subArrivalLocationIds = explode(',', $deliveryOrder->sub_arrival_location_id);
                                $subArrivalLocations = \App\Models\Master\ArrivalSubLocation::whereIn('id', $subArrivalLocationIds)->get();
                                foreach($subArrivalLocations as $location) {
                                    echo '<option value="' . $location->id . '" selected>' . $location->name . '</option>';
                                }
                            } elseif ($loadingProgramItem && $loadingProgramItem->sub_arrival_location_id) {
                                // Fallback to loading program item
                                $subArrivalLocation = \App\Models\Master\ArrivalSubLocation::find($loadingProgramItem->sub_arrival_location_id);
                                if ($subArrivalLocation) {
                                    echo '<option value="' . $subArrivalLocation->id . '" selected>' . $subArrivalLocation->name . '</option>';
                                }
                            }
                        @endphp
                    </select>
                    <input type="hidden" name="gala" value="{{ $DispatchQc->gala ?? '' }}" />
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>QC Remarks:</label>
                <textarea name="qc_remarks" placeholder="Enter QC remarks" class="form-control" rows="3">{{ $DispatchQc->qc_remarks }}</textarea>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status">
                    <option value="">Select Status</option>
                    <option value="accept" {{ $DispatchQc->status == 'accept' ? 'selected' : '' }}>Accept</option>
                    <option value="reject" {{ $DispatchQc->status == 'reject' ? 'selected' : '' }}>Reject</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Attachments:</label>
                <input type="file" name="attachments[]" class="form-control" multiple accept="image/*,application/pdf,.doc,.docx">
                <small class="text-muted">Allowed: Images, PDF, DOC, DOCX (Max 10MB each)</small>

                @if($DispatchQc->attachments->count() > 0)
                <div class="mt-2">
                    <label>Current Attachments:</label>
                    <div class="row">
                        @foreach($DispatchQc->attachments as $attachment)
                            <div class="col-md-4 mb-2">
                                <div class="card">
                                    <div class="card-body text-center p-2">
                                        @if(Str::contains($attachment->file_type, ['image']))
                                            <img src="{{ asset($attachment->file_path) }}" alt="{{ $attachment->file_name }}" class="img-fluid rounded" style="max-height: 50px;">
                                        @else
                                            <i class="ft-file-text font-medium-2"></i>
                                        @endif
                                        <p class="mt-1 mb-1 small">{{ Str::limit($attachment->file_name, 15) }}</p>
                                        <a href="{{ asset($attachment->file_path) }}" target="_blank" class="btn btn-xs btn-primary">View</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a href="{{ route('sales.sales-qc.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });

    $(document).ready(function() {
        // Handle ticket selection
        $('#loading_program_item_id').change(function() {
            var loading_program_item_id = $(this).val();

            if (loading_program_item_id) {
                $.ajax({
                    url: '{{ route('sales.getTicketRelatedData') }}',
                    type: 'GET',
                    data: {
                        loading_program_item_id: loading_program_item_id
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        Swal.fire({
                            title: "Processing...",
                            text: "Please wait while fetching ticket details.",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            // Populate the form with ticket data
                            populateTicketData(response.data);
                        } else {
                            Swal.fire("No Data", "No ticket details found.",
                                "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.",
                            "error");
                    }
                });
            } else {
                // Clear ticket data container if no ticket selected
                $('#ticketDataContainer').html('');
            }
        });
    });

    function populateTicketData(data) {
        var html = `
            <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>Customer:</label>
                    <input type="text" name="customer" value="${data.customer}" class="form-control" readonly />
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>Commodity:</label>
                    <input type="text" name="commodity" value="${data.commodity}" class="form-control" readonly />
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>SO Qty:</label>
                    <input type="number" name="so_qty" value="${data.so_qty}" class="form-control" readonly step="0.01" />
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-3">
                <div class="form-group">
                    <label>DO Qty:</label>
                    <input type="number" name="do_qty" value="${data.do_qty}" class="form-control" readonly step="0.01" />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12 col-sm-6 col-md-6">
                <div class="form-group">
                    <label>Factory:</label>
                    <input type="text" name="factory" value="${data.factory}" class="form-control" readonly />
                </div>
            </div>
            <div class="col-xs-12 col-sm-6 col-md-6">
                <div class="form-group">
                    <label>Gala:</label>
                    <input type="text" name="gala" value="${data.gala}" class="form-control" readonly />
                </div>
            </div>
        </div>
        `;

        $('#ticketDataContainer').html(html);
    }
</script>
