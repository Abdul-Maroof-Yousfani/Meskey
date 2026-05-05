<form action="{{ route('export-dispatch-qc.update', $DispatchQc->id) }}" method="POST" id="ajaxSubmit" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" id="listRefresh" value="{{ route('get.export-dispatch-qc') }}" />

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

    <div class="row" id="ticketDataContainer" style="margin-left: 4px; margin-right: 4px; width: 100%;">
        @if(count($Orders) > 0)
            <ul class="nav nav-tabs nav-justified w-100" id="orderTabs" role="tablist">
                @foreach($Orders as $index => $order)
                    <li class="nav-item">
                        <a class="nav-link {{ $index === 0 ? 'active' : '' }}" id="order-tab-{{ $index }}" data-toggle="tab" href="#order-content-{{ $index }}" role="tab" aria-controls="order-content-{{ $index }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                            {{ $order['type'] }}: {{ $order['number'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content pt-1 w-100" id="orderTabsContent">
                @foreach($Orders as $index => $order)
                    <div class="tab-pane fade show {{ $index === 0 ? 'active' : '' }}" id="order-content-{{ $index }}" role="tabpanel" aria-labelledby="order-tab-{{ $index }}">
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Customer:</label>
                                    <input type="text" value="{{ $order['customer'] }}" class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>Commodity:</label>
                                    <input type="text" value="{{ $order['commodity'] }}" class="form-control" readonly />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>EO Qty (MT):</label>
                                    <input type="number" value="{{ $order['so_qty'] }}" class="form-control" readonly step="0.01" />
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-3">
                                <div class="form-group">
                                    <label>DO Qty (MT):</label>
                                    <input type="number" value="{{ $order['do_qty'] }}" class="form-control" readonly step="0.01" />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12 col-sm-6 col-md-6">
                                <div class="form-group">
                                    <label>Factory:</label>
                                    <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                        @foreach($order['factory_names'] as $name)
                                            <option selected>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 col-md-6">
                                <div class="form-group">
                                    <label>Gala:</label>
                                    <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                        @foreach($order['gala_names'] as $name)
                                            <option selected>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>


            <input type="hidden" name="customer" value="{{ $DispatchQc->customer }}" />
            <input type="hidden" name="commodity" value="{{ $DispatchQc->commodity }}" />
            <input type="hidden" name="so_qty" value="{{ $DispatchQc->so_qty }}" />
            <input type="hidden" name="do_qty" value="{{ $DispatchQc->do_qty }}" />
            <input type="hidden" name="factory" value="{{ $DispatchQc->factory }}" />
            <input type="hidden" name="gala" value="{{ $DispatchQc->gala }}" />
        @else
            <div class="col-12 text-center">No order data found.</div>
        @endif
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

    <div class="row pt-3">
        <div class="col-12">
            <h6 class="header-heading-sepration">Outer Items</h6>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Weight (Per Item)</th>
                        <th>Qty</th>
                        <th>Total Weight</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($DispatchQc->loadingProgramItem->outerItems as $item)
                        <tr>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ number_format($item->weight, 3) }}</td>
                            <td>{{ number_format($item->qty, 3) }}</td>
                            <td>{{ number_format($item->total_weight, 3) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No outer items found.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($DispatchQc->loadingProgramItem->outerItems->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>Grand Total:</strong></td>
                        <td><strong>{{ number_format($DispatchQc->loadingProgramItem->outerItems->sum('total_weight'), 3) }}</strong></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a href="{{ route('export-dispatch-qc.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary submitbutton">Update</button>
        </div>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('.select2').select2();
    });

    $(document).ready(function() {
        $('#loading_program_item_id').change(function() {
            var loading_program_item_id = $(this).val();

            if (loading_program_item_id) {
                $.ajax({
                    url: '{{ route('export.getDispatchQcTicketData') }}',
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
                            populateTicketData(response.data);
                        } else {
                            Swal.fire("No Data", "No ticket details found.", "info");
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Something went wrong. Please try again.", "error");
                    }
                });
            } else {
                $('#ticketDataContainer').html('');
            }
        });
    });

    function populateTicketData(data) {
        if (!data.orders || data.orders.length === 0) {
            $('#ticketDataContainer').html('<div class="col-12 text-center">No order data found.</div>');
            return;
        }

        var tabsHtml = '<ul class="nav nav-tabs nav-justified w-100" id="orderTabs" role="tablist">';
        var contentHtml = '<div class="tab-content pt-1 w-100" id="orderTabsContent">';

        data.orders.forEach((order, index) => {
            var activeClass = index === 0 ? 'active' : '';
            var selectedAttr = index === 0 ? 'true' : 'false';
            var tabId = `order-tab-${index}`;
            var contentId = `order-content-${index}`;

            tabsHtml += `
                <li class="nav-item">
                    <a class="nav-link ${activeClass}" id="${tabId}" data-toggle="tab" href="#${contentId}" role="tab" aria-controls="${contentId}" aria-selected="${selectedAttr}">
                        ${order.type}: ${order.number}
                    </a>
                </li>
            `;

            var factoryOptions = order.factory_names && order.factory_names.length > 0 ?
                order.factory_names.map(name => `<option value="" selected>${name}</option>`).join('') : '';
            var galaOptions = order.gala_names && order.gala_names.length > 0 ?
                order.gala_names.map(name => `<option value="" selected>${name}</option>`).join('') : '';

            contentHtml += `
                <div class="tab-pane fade show ${activeClass}" id="${contentId}" role="tabpanel" aria-labelledby="${tabId}">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Customer:</label>
                                <input type="text" value="${order.customer}" class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>Commodity:</label>
                                <input type="text" value="${order.commodity}" class="form-control" readonly />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>EO Qty (MT):</label>
                                <input type="number" value="${order.so_qty}" class="form-control" readonly step="0.01" />
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-3">
                            <div class="form-group">
                                <label>DO Qty (MT):</label>
                                <input type="number" value="${order.do_qty}" class="form-control" readonly step="0.01" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Factory:</label>
                                <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                    ${factoryOptions}
                                </select>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6 col-md-6">
                            <div class="form-group">
                                <label>Gala:</label>
                                <select class="form-control select2 w-100" multiple disabled style="width: 100% !important;">
                                    ${galaOptions}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        tabsHtml += '</ul>';
        contentHtml += '</div>';

        var hiddenInputs = `
            <input type="hidden" name="customer" value="${data.customer}" />
            <input type="hidden" name="commodity" value="${data.commodity}" />
            <input type="hidden" name="so_qty" value="${data.so_qty}" />
            <input type="hidden" name="do_qty" value="${data.do_qty}" />
            <input type="hidden" name="factory" value="${data.factory_names ? data.factory_names.join(', ') : ''}" />
            <input type="hidden" name="gala" value="${data.gala_names ? data.gala_names.join(', ') : ''}" />
        `;

        $('#ticketDataContainer').html(tabsHtml + contentHtml + hiddenInputs);
        $('.select2').select2();
    }
</script>
