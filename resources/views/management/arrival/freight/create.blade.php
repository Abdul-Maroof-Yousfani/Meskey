@extends('management.layouts.master')
@section('title')
    Create Freight
@endsection
@section('content')

    {{-- tkf-page: mobile form UI (mobile-ui.css section 7). Desktop same. --}}
    <div class="container-fluid tkf-page">
        {{-- mobile app-bar (desktop par card-header wala title hi dikhta hai) --}}
        @include('management.layouts.partials.mobile-page-head')
        <div class="row w-100 mx-auto align-items-center mph-head show-mobile">
            <a href="{{ route('freight.index') }}" class="mph-back" aria-label="Back" title="Back">
                <i class="ft-arrow-left"></i>
            </a>
            <div class="col-12 mph-title-col">
                <div class="mph-sub">Arrival</div>
                <h2 class="page-title mb-0">Create Freight</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header hide-mobile">
                        <h4 class="mb-0">Create Freight </h4>
                    </div>
                    <div class="card-body">




                        <div class="row form-mar">
                            <div class="col-xs-12 col-sm-12 col-md-12">
                                <div class="form-group">
                                    <label>Select Ticket:</label>
                                    <select name="arrival_ticket_id" id="arrival_ticket_id" class="form-control select2">
                                        <option value="">Select Ticket</option>
                                        @foreach ($tickets as $ticket)
                                            <option value="{{ $ticket->id }}">
                                                Ticket No: {{ $ticket->unique_no }} --
                                                Truck No: {{ $ticket->truck_no ?? '-' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div id="freightFormContainer"></div>


                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            $('.select2').select2();

            $('#arrival_ticket_id').change(function () {
                var ticketId = $(this).val();

                if (ticketId) {
                    $.ajax({
                        url: '{{ route('freight.getFreightForm') }}',
                        type: 'GET',
                        data: {
                            arrival_ticket_id: ticketId
                        },
                        beforeSend: function () {
                            Swal.fire({
                                title: "Loading...",
                                text: "Fetching freight form",
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function (response) {
                            Swal.close();
                            if (response.success) {
                                $('#freightFormContainer').html(response.html);
                            } else {
                                Swal.fire("Error", response.message, "error");
                                $('#freightFormContainer').empty();
                            }
                        },
                        error: function () {
                            Swal.close();
                            Swal.fire("Error", "Failed to load freight form", "error");
                            $('#freightFormContainer').empty();
                        }
                    });
                } else {
                    $('#freightFormContainer').empty();
                }
            });
        });
    </script>
@endsection