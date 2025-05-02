@extends('management.layouts.master')
@section('title')
    Arrival Locations
@endsection
@section('content')
    <div class="content-wrapper">

        <section id="extended">
            <div class="row w-100 mx-auto">
                <div class="col-xs-6 col-sm-6 col-md-6 col-lg-6">
                    <h2 class="page-title"> QC Relief Management</h2>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-0">
                                        <label>Select Product:</label>
                                        <select name="product_id" id="product_id" class="form-control select2">
                                            <option value="">Select Product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div id="parametersContainer">
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
        $(document).ready(function() {
            $('.select2').select2();

            $('#product_id').change(function() {
                var productId = $(this).val();

                if (productId) {
                    $.ajax({
                        url: '{{ route('qc-relief.get-parameters') }}',
                        type: 'GET',
                        data: {
                            product_id: productId
                        },
                        beforeSend: function() {
                            Swal.fire({
                                title: "Loading...",
                                text: "Fetching QC parameters",
                                allowOutsideClick: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function(response) {
                            Swal.close();
                            if (response.success) {
                                $('#parametersContainer').html(response.html);
                            } else {
                                Swal.fire("Error", "Failed to load parameters", "error");
                            }
                        },
                        error: function() {
                            Swal.close();
                            Swal.fire("Error", "Something went wrong", "error");
                        }
                    });
                } else {
                    $('#parametersContainer').empty();
                }
            });

            $(document).on('submit', '#reliefParametersForm', function(e) {
                e.preventDefault();

                $.ajax({
                    url: '{{ route('qc-relief.save-parameters') }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    beforeSend: function() {
                        Swal.fire({
                            title: "Saving...",
                            text: "Updating QC relief parameters",
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message
                        });
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire("Error", "Failed to save parameters", "error");
                    }
                });
            });
        });
        $(document).ready(function() {
            filterationCommon(`{{ route('get.arrival-location') }}`)
        });
    </script>
@endsection
