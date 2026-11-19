@extends('management.layouts.master')
@section('title')
    Create First Weighbridge
@endsection
@section('content')

    {{-- tkf-page: mobile form UI (mobile-ui.css section 7). Desktop same. --}}
    <div class="container-fluid tkf-page">
        {{-- mobile app-bar (desktop par card-header wala title hi dikhta hai) --}}
        @include('management.layouts.partials.mobile-page-head')
        <div class="row w-100 mx-auto align-items-center mph-head show-mobile">
            <a href="{{ route('first-weighbridge.index') }}" class="mph-back" aria-label="Back" title="Back">
                <i class="ft-arrow-left"></i>
            </a>
            <div class="col-12 mph-title-col">
                <div class="mph-sub">Arrival</div>
                <h2 class="page-title mb-0">Create First Weighbridge</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header hide-mobile">
                        <h4 class="mb-0">Create First Weighbridge</h4>
                    </div>
                    <div class="card-body">


                        <form action="{{ route('first-weighbridge.store') }}" method="POST" id="ajaxSubmit"
                            autocomplete="off">
                            @csrf
                            <input type="hidden" id="url" value="{{ route('first-weighbridge.create') }}" />
                            <div class="row form-mar">
                                <div class="col-xs-12 col-sm-12 col-md-12">
                                    {!! getUserMissingInfoAlert()  !!}
                                    <div class="form-group">
                                        <label>Ticket:</label>
                                        <select class="form-control select2" name="arrival_ticket_id"
                                            id="arrival_ticket_id">
                                            <option value="">Select Ticket</option>
                                            @foreach ($ArrivalTickets as $arrivalTicket)
                                                <option value="{{ $arrivalTicket->id }}">
                                                    Ticket No: {{ $arrivalTicket->unique_no }} --
                                                    Truck No: {{ $arrivalTicket->truck_no }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="slabsContainer">
                            </div>
                            <div class="row bottom-button-bar tkf-actions">
                                <div class="col-12">
                                    <a href="{{ route('first-weighbridge.index') }}"
                                        class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
                                    <button type="submit" class="btn btn-primary submitbutton">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            $('#arrival_ticket_id').change(function () {
                var arrival_ticket_id = $(this).val();

                if (arrival_ticket_id) {
                    $.ajax({
                        url: '{{ route('getFirstWeighbridgeRelatedData') }}',
                        type: 'GET',
                        data: {
                            arrival_ticket_id: arrival_ticket_id
                        },
                        dataType: 'json',
                        beforeSend: function () {
                            Swal.fire({
                                title: "Processing...",
                                text: "Please wait while fetching slabs.",
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function (response) {
                            Swal.close();
                            if (response.success) {
                                // Append the rendered HTML to a container element
                                $('#slabsContainer').html(response.html);
                            } else {
                                Swal.fire("No Data", "No slabs found for this product.",
                                    "info");
                            }
                        },
                        error: function () {
                            Swal.close();
                            Swal.fire("Error", "Something went wrong. Please try again.",
                                "error");
                        }
                    });
                }
            });
        });

        $(document).ready(function () {
            $('.select2').select2();
        });
    </script>
@endsection