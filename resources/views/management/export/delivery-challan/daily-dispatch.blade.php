@extends('management.layouts.master')
@section('title')
    Daily Export Dispatch
@endsection
@section('content')
    <div class="content-wrapper">
        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title">Daily Export Dispatch</h2>
                </div>
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6 text-right">
                    <a href="{{ route('export-delivery-challan.index') }}" class="btn btn-secondary">Back to Delivery Challans</a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Generate Report</h4>
                        </div>
                        <div class="card-content">
                            <div class="card-body">
                                <form action="{{ route('export-delivery-challan.daily-dispatch.report') }}" method="POST" target="_blank" id="dispatchFilterForm">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4 form-group">
                                            <label for="do_id">Delivery Order <span class="text-danger">*</span></label>
                                            <select name="do_id" id="do_id" class="form-control select2" required>
                                                <option value="" selected disabled>Select DO</option>
                                                @foreach($delivery_orders as $order)
                                                    <option value="{{ $order->id }}">{{ $order->reference_no ?? $order->do_no ?? $order->id }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="start_date">Start Date</label>
                                            <input type="date" name="start_date" id="start_date" class="form-control">
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="end_date">End Date</label>
                                            <input type="date" name="end_date" id="end_date" class="form-control">
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="location">Factory</label>
                                            <select name="location[]" id="location" class="form-control select2" multiple="multiple">
                                            </select>
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="container">Container</label>
                                            <select name="container[]" id="container" class="form-control select2" multiple="multiple">
                                            </select>
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="truck">Truck</label>
                                            <select name="truck[]" id="truck" class="form-control select2" multiple="multiple">
                                            </select>
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="packing">Packing</label>
                                            <select name="packing[]" id="packing" class="form-control select2" multiple="multiple">
                                            </select>
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="iso_code">ISO Doc Control Code</label>
                                            <input type="text" name="iso_code" id="iso_code" class="form-control" value="MFT/QR/037">
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="issue_date">Issue Date</label>
                                            <input type="text" name="issue_date" id="issue_date" class="form-control" value="18-07-14">
                                        </div>

                                        <div class="col-md-4 form-group">
                                            <label for="issue_no">Issue#</label>
                                            <input type="text" name="issue_no" id="issue_no" class="form-control" value="1">
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-12 text-right">
                                            <button type="submit" class="btn btn-primary" id="generateReportBtn" disabled>Generate Report</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
<script>
    $(document).ready(function () {
        $('.select2').select2();

        $('#do_id').change(function() {
            var do_id = $(this).val();
            
            if (do_id) {
                $('#generateReportBtn').prop('disabled', false);

                $.ajax({
                    url: "{{ route('export-delivery-challan.daily-dispatch.filters') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        do_id: do_id
                    },
                    success: function(response) {
                        populateDropdown('#location', response.locations);
                        populateDropdown('#container', response.containers);
                        populateDropdown('#truck', response.trucks);
                        populateDropdown('#packing', response.packings);

                        if (response.min_date) {
                            $('#start_date').val(response.min_date);
                        } else {
                            $('#start_date').val('');
                        }
                        
                        if (response.max_date) {
                            $('#end_date').val(response.max_date);
                        } else {
                            $('#end_date').val('');
                        }
                    },
                    error: function(err) {
                        console.error('Error fetching filters', err);
                    }
                });
            } else {
                $('#generateReportBtn').prop('disabled', true);
            }
        });

        function populateDropdown(selector, data) {
            var dropdown = $(selector);
            dropdown.empty();
            var allValues = [];
            $.each(data, function(index, value) {
                dropdown.append('<option value="' + value + '">' + value + '</option>');
                allValues.push(value);
            });
            dropdown.val(allValues).trigger('change');
        }
    });
</script>
@endsection
