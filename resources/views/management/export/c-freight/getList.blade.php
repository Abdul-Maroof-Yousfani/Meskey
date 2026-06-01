<div class="tab-content" id="myTabContent">
    <!-- Pending Requests Tab -->
    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
        <table class="table m-0">
            <thead>
                <tr>
                    <th>S no.</th>
                    <th>Export Order</th>
                    <th>Buyer</th>
                    <th>Port</th>
                    <th>Required Cont.</th>
                    <th>ETR</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($freights->where('status', 'Pending Rates') as $key => $freight)
                    <tr>
                        <td>{{ $loop->iteration + ($freights->currentPage() - 1) * $freights->perPage() }}</td>
                        <td>{{ $freight->exportOrder->voucher_no ?? '' }}</td>
                        <td>{{ $freight->exportOrder->buyer->name ?? '' }}</td>
                        <td>{{ $freight->exportOrder->portOfDischarge->name ?? '' }}</td>
                        <td>{{ $freight->requested_containers }}</td>
                        <td>{{ $freight->etr }}</td>
                        <td><span class="badge badge-warning">{{ $freight->status }}</span></td>
                        <td>
                            <!-- View Request -->
                            <button style="user-select: none;" onclick="openModal(this,'{{ route('c-freight.show', $freight->id) }}','View Request',true,'80%')" class="btn btn-sm btn-secondary mb-1" title="View Request"><i class="ft-eye"></i></button>

                            <!-- Edit Request -->
                            <button style="user-select: none;" onclick="openModal(this,'{{ route('c-freight.edit-request', $freight->id) }}','Edit Request',false,'80%')" class="btn btn-sm btn-primary mb-1" title="Edit Request"><i class="ft-edit-2"></i></button>
                            
                            <!-- Rates & Booking -->
                            <button style="user-select: none;" onclick="openModal(this,'{{ route('c-freight.edit', $freight->id) }}','Add Rates & Booking',false,'90%')" class="btn btn-sm btn-info mb-1" title="Add Rates & Booking"><i class="ft-list"></i> Rates</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No pending requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Bookings Record Tab (Image 3) -->
    <div class="tab-pane fade" id="booked" role="tabpanel" aria-labelledby="booked-tab">
        <table class="table m-0">
            <thead>
                <tr>
                    <th>Booking No</th>
                    <th>Bill of Lading Numb</th>
                    <th>Quantity</th>
                    <th>Line</th>
                    <th>Through</th>
                    <th>T/S</th>
                    <th>POD</th>
                    <th>Return Port</th>
                    <th>Vessel Name</th>
                    <th>Cutoff SI</th>
                    <th>Cutoff CY</th>
                    <th>ETD</th>
                    <th>ETA</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($freights->where('status', 'Booked') as $freight)
                    <tr>
                        <td>{{ $freight->booking_no }}</td>
                        <td>{{ $freight->bl_number }}</td>
                        <td>{{ $freight->quantity }}</td>
                        <td>
                            @php
                                $approvedRate = $freight->rates->where('is_approved', 1)->first();
                            @endphp
                            {{ $freight->shipping_line ?? ($approvedRate ? $approvedRate->shipping_line : '') }}
                        </td>
                        <td>{{ $freight->through_logistic }}</td>
                        <td>{{ $freight->t_s }}</td>
                        <td>{{ $freight->exportOrder->portOfDischarge->name ?? '' }}</td>
                        <td>{{ $freight->return_port }}</td>
                        <td>{{ $freight->vessel_name }}</td>
                        <td>{{ $freight->cutoff_si ? date('d-M-y', strtotime($freight->cutoff_si)) : '' }}</td>
                        <td>{{ $freight->cutoff_cy ? date('d-M-y', strtotime($freight->cutoff_cy)) : '' }}</td>
                        <td>{{ $freight->etd ? date('d-M-y', strtotime($freight->etd)) : '' }}</td>
                        <td>{{ $freight->eta ? date('d-M-y', strtotime($freight->eta)) : '' }}</td>
                        <td>
                            <button style="user-select: none;" onclick="openModal(this,'{{ route('c-freight.show-booking', $freight->id) }}','Booking Record',true,'80%')" class="btn btn-sm btn-primary mb-1" title="View Booking Record">
                                <i class="ft-eye"></i>
                            </button>
                            <button style="user-select: none;" onclick="openModal(this,'{{ route('c-freight.edit', $freight->id) }}','Edit Booking Details',false,'90%')" class="btn btn-sm btn-info mb-1" title="Edit Booking Details">
                                <i class="ft-edit-2"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center">No bookings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between mt-2">
    <div>
        Showing {{ $freights->firstItem() }} to {{ $freights->lastItem() }} of {{ $freights->total() }} entries
    </div>
    <div>
        {{ $freights->links('vendor.pagination.bootstrap-4') }}
    </div>
</div>

<script>
    // Maintain active tab
    $('#myTab a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href"); // activated tab
        $('.tab-pane').removeClass('show active');
        $(target).addClass('show active');
    });
</script>
