@extends('management.layouts.master')
@section('title')
    Indicative Prices
@endsection
@section('content')
    <div class="content-wrapper indicative">
        <section id="extended">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form id="filterForm" class="form">
                                <div class="row">
                                    <div class="col-md-6 my-1">
                                        <h2 class="page-title"> Indicative Prices</h2>
                                    </div>
                                    <div class="col-md-6 my-1">
                                        <div class="row justify-content-end text-left">
                                            <input type="hidden" name="page" value="{{ request('page', 1) }}">
                                            <input type="hidden" name="per_page" value="{{ request('per_page', 25) }}">

                                            <div class="col-xs-4 col-sm-4 col-md-4">
                                                <div class="form-group">
                                                    <label>Date:</label>
                                                    <input type="date" class="form-control" name="date"
                                                        value="{{ date('Y-m-d') }}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="card-content">
                            <div class="card-body table-responsive" id="filteredData">
                                <table class="table m-0" id="indicativePricesTable">
                                    <thead>
                                        <tr>
                                            <th>S.No</th>
                                            <th>Commodity</th>
                                            <th>Location</th>
                                            <th>Type</th>
                                            <th>Crop Year</th>
                                            <th>Delivery Condition</th>
                                            <th>Cash Rate</th>
                                            <th>Cash Days</th>
                                            <th>Credit Rate</th>
                                            <th>Credit Days</th>
                                            <th>Others</th>
                                            <th>Remarks</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="empty-row">
                                            <td>#</td>
                                            <td>
                                                <select class="form-control product-select" name="product_id" required>
                                                    <option value="">Select Commodity</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-control location-select" name="location_id" required>
                                                    <option value="">Select Location</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-control type-select" name="type_id" required>
                                                    <option value="">Select Type</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-control" name="crop_year" required>
                                                    @php
                                                        $currentYear = date('Y');
                                                        for ($i = $currentYear; $i >= $currentYear - 4; $i--) {
                                                            echo "<option value='$i'" .
                                                                ($i == $currentYear ? ' selected' : '') .
                                                                ">$i</option>";
                                                        }
                                                    @endphp
                                                </select>
                                            </td>
                                            <td><input type="text" class="form-control" name="delivery_condition"
                                                    required></td>
                                            <td><input type="number" step="0.01" class="form-control" name="cash_rate">
                                            </td>
                                            <td><input type="number" class="form-control" name="cash_days"></td>
                                            <td><input type="number" step="0.01" class="form-control"
                                                    name="credit_rate"></td>
                                            <td><input type="number" class="form-control" name="credit_days"></td>
                                            <td><input type="text" class="form-control" name="others"></td>
                                            <td><input type="text" class="form-control" name="remarks"></td>
                                            <td>
                                                <button class="btn btn-success btn-sm save-row">Save</button>
                                                <button class="btn btn-danger btn-sm cancel-row">Cancel</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="text-center mt-3">
                                    <button id="addNewRow" class="btn btn-primary">Add New Row</button>
                                </div>
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
            // initializeDynamicSelect2('#product_id', 'products', 'name', 'id', false, false);
            // initializeDynamicSelect2('#location_id', 'company_locations', 'name', 'id', false, false);
            // initializeDynamicSelect2('#type_id', 'sauda_types', 'name', 'id', false, false);

            initializeDynamicSelect2('.empty-row .product-select', 'products', 'name', 'id', false, false);
            initializeDynamicSelect2('.empty-row .location-select', 'company_locations', 'name', 'id', false,
                false);
            initializeDynamicSelect2('.empty-row .type-select', 'sauda_types', 'name', 'id', false, false);

            loadIndicativePrices();

            $('#filterForm').off('change', 'select').on('change', 'select', function() {
                loadIndicativePrices();
            });

            $('#addNewRow').off('click').on('click', function() {

                const newRow = $(`<tr class="new-row">
                    <td>#</td>
                    <td>
                        
                        <select class="form-control product-select" name="product_id" required>
                            <option value="">Select Commodity</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-control location-select" name="location_id" required>
                            <option value="">Select Location</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-control type-select" name="type_id" required>
                            <option value="">Select Type</option>
                        </select>
                    </td>
                    <td>
                        <select class="form-control" name="crop_year" required>
                            @php
                                $currentYear = date('Y');
                                for ($i = $currentYear; $i >= $currentYear - 4; $i--) {
                                    echo "<option value='$i'" . ($i == $currentYear ? ' selected' : '') . ">$i</option>";
                                }
                            @endphp
                        </select>
                    </td>
                    <td><input type="text" class="form-control" name="delivery_condition" required></td>
                    <td><input type="number" step="0.01" class="form-control" name="cash_rate"></td>
                    <td><input type="number" class="form-control" name="cash_days"></td>
                    <td><input type="number" step="0.01" class="form-control" name="credit_rate"></td>
                    <td><input type="number" class="form-control" name="credit_days"></td>
                    <td><input type="text" class="form-control" name="others"></td>
                    <td><input type="text" class="form-control" name="remarks"></td>
                    <td>
                        <button class="btn btn-success btn-sm save-row">Save</button>
                        <button class="btn btn-danger btn-sm cancel-row">Cancel</button>
                    </td>
                </tr>`);

                $('.empty-row').before(newRow);

                initializeDynamicSelect2(newRow.find('.product-select'), 'products', 'name', 'id', false,
                    false);
                initializeDynamicSelect2(newRow.find('.location-select'), 'company_locations', 'name', 'id',
                    false, false);
                initializeDynamicSelect2(newRow.find('.type-select'), 'sauda_types', 'name', 'id', false,
                    false);
            });

            $('#indicativePricesTable').off('click', '.save-row').on('click', '.save-row', function() {
                const row = $(this).closest('tr');
                const isNew = row.hasClass('new-row') || row.hasClass('empty-row');
                const formData = {
                    product_id: row.find('[name="product_id"]').val(),
                    location_id: row.find('[name="location_id"]').val(),
                    type_id: row.find('[name="type_id"]').val(),
                    crop_year: row.find('[name="crop_year"]').val(),
                    delivery_condition: row.find('[name="delivery_condition"]').val(),
                    cash_rate: row.find('[name="cash_rate"]').val(),
                    cash_days: row.find('[name="cash_days"]').val(),
                    credit_rate: row.find('[name="credit_rate"]').val(),
                    credit_days: row.find('[name="credit_days"]').val(),
                    others: row.find('[name="others"]').val(),
                    remarks: row.find('[name="remarks"]').val(),
                    _token: '{{ csrf_token() }}'
                };

                clearRowErrors(row);

                const saveBtn = $(this);
                saveBtn.html('<i class="fa fa-spinner fa-spin"></i> Saving');

                const url = isNew ? '{{ route('indicative-prices.store') }}' :
                    '{{ route('indicative-prices.update', '') }}/' + row.data('id');
                const method = isNew ? 'POST' : 'PUT';

                console.log({
                    as: "adaasd"
                });

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            loadIndicativePrices();
                            showToast('success', response.message);
                        } else {
                            showToast('error', response.message);
                            saveBtn.html('Save');
                        }
                    },
                    error: function(xhr) {
                        saveBtn.html('Save');

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            displayRowErrors(row, errors);
                            showToast('error', xhr.responseJSON.message || 'Validation failed');
                        } else {
                            showToast('error', xhr.responseJSON.message || 'An error occurred');
                        }
                    }
                });
            });

            function displayRowErrors(row, errors) {
                for (const field in errors) {
                    const inputField = row.find(`[name="${field}"]`);
                    if (inputField.length) {
                        inputField.addClass('is-invalid');

                        const isSelect2 = inputField.hasClass('select2-hidden-accessible');
                        if (isSelect2) {
                            inputField.next('.select2-container').find('.select2-selection').addClass('is-invalid');
                        }

                        const errorElementExists = isSelect2 ?
                            inputField.next('.select2-container').next('.error-message').length :
                            inputField.next('.error-message').length;

                        if (!errorElementExists) {
                            const errorHtml = `<div class="error-message text-danger">${errors[field][0]}</div>`;
                            if (isSelect2) {
                                inputField.next('.select2-container').after(errorHtml);
                            } else {
                                inputField.after(errorHtml);
                            }
                        } else {
                            if (isSelect2) {
                                inputField.next('.select2-container').next('.error-message').text(errors[field][0]);
                            } else {
                                inputField.next('.error-message').text(errors[field][0]);
                            }
                        }
                    }
                }
            }

            function clearRowErrors(row) {
                row.find('.is-invalid').removeClass('is-invalid');

                row.find('.select2-selection.is-invalid').removeClass('is-invalid');

                row.find('.error-message').remove();
            }

            $('#indicativePricesTable').off('click', '.cancel-row').on('click', '.cancel-row', function() {
                const row = $(this).closest('tr');
                clearRowErrors(row);

                if (row.hasClass('new-row')) {
                    row.remove();
                } else if (row.hasClass('empty-row')) {
                    row.find('select').val('').trigger('change');
                    row.find('input').val('');
                } else {
                    row.find('td:not(:first):not(:last)').each(function() {
                        const cell = $(this);
                        if (cell.attr('data-original-content')) {
                            cell.html(cell.attr('data-original-content'));
                        }
                    });

                    row.find('.edit-btn').show();
                    row.find('.delete-btn').show();
                    row.find('.save-row, .cancel-row').remove();
                    row.removeClass('editing');
                }
            });


            $('#indicativePricesTable').off('click', '.edit-btn').on('click', '.edit-btn', function() {
                const row = $(this).closest('tr');

                if (row.hasClass('editing')) return;

                row.addClass('editing');

                $(this).hide();
                row.find('.delete-btn').hide();

                row.find('td:last').append(`
    <button class="btn btn-success btn-sm save-row">Save</button>
    <button class="btn btn-danger btn-sm cancel-row">Cancel</button>`);

                const cells = row.find('td:not(:last)');
                cells.each(function(index) {
                    const cell = $(this);

                    if (index === 0) return;

                    if (!cell.attr('data-original-content')) {
                        cell.attr('data-original-content', cell.html());
                    }

                    const value = cell.text().trim();

                    if (index === 1 || index === 2 || index === 3 || index === 4) {
                        const selectName = index === 1 ? 'product_id' :
                            index === 2 ? 'location_id' :
                            index === 3 ? 'type_id' : 'crop_year';

                        if (!cell.attr('data-original-value')) {
                            cell.attr('data-original-value', value);
                            cell.attr('data-original-id', cell.data('id'));
                        }

                        let options = '';
                        if (index === 4) {
                            const currentYear = new Date().getFullYear();
                            for (let i = currentYear; i >= currentYear - 4; i--) {
                                options +=
                                    `<option value="${i}" ${value == i ? 'selected' : ''}>${i}</option>`;
                            }
                        } else {
                            options =
                                `<option value="${cell.data('id')}" selected>${value}</option>`;
                        }

                        cell.html(
                            `<select class="form-control" name="${selectName}">${options}</select>`
                        );
                    } else {
                        cell.html(`<input type="${index >= 5 && index <= 9 ? 'number' : 'text'}" 
                         class="form-control" 
                         name="${getFieldName(index)}" 
                         value="${value}" 
                         ${index >= 5 && index <= 9 ? 'step="0.01"' : ''}>`);
                    }
                });

                initializeDynamicSelect2(row.find('[name="product_id"]'), 'products', 'name', 'id', false,
                    false);
                initializeDynamicSelect2(row.find('[name="location_id"]'), 'company_locations', 'name',
                    'id', false, false);
                initializeDynamicSelect2(row.find('[name="type_id"]'), 'sauda_types', 'name', 'id', false,
                    false);
            });

            $('#indicativePricesTable').off('click', '.delete-btn').on('click', '.delete-btn', function() {

                const row = $(this).closest('tr');
                const id = row.data('id');

                if (confirm('Are you sure you want to delete this record?')) {
                    $.ajax({
                        url: '{{ route('indicative-prices.destroy', '') }}/' + id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                loadIndicativePrices();
                                showToast('success', response.message);
                            } else {
                                showToast('error', response.message);
                            }
                        },
                        error: function(xhr) {
                            showToast('error', xhr.responseJSON.message || 'An error occurred');
                        }
                    });
                }
            });
        });

        function loadIndicativePrices() {
            const formData = $('#filterForm').serialize();
            // formData += '&company_id={{ request()->company_id }}';

            $.ajax({
                url: '{{ route('get.indicative-prices') }}',
                type: 'GET',
                data: formData,
                beforeSend: function() {
                    $('#indicativePricesTable tbody').html(
                        '<tr><td colspan="13" class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>'
                    );
                },
                success: function(response) {
                    $('#indicativePricesTable tbody').html(response);

                    const emptyRow = $(`<tr class="empty-row">
                <td>#</td>
                <td>
                    <select class="form-control product-select" name="product_id" required>
                        <option value="">Select Commodity</option>
                    </select>
                </td>
                <td>
                    <select class="form-control location-select" name="location_id" required>
                        <option value="">Select Location</option>
                    </select>
                </td>
                <td>
                    <select class="form-control type-select" name="type_id" required>
                        <option value="">Select Type</option>
                    </select>
                </td>
                <td>
                    <select class="form-control" name="crop_year" required>
                        @php
                            $currentYear = date('Y');
                            for ($i = $currentYear; $i >= $currentYear - 4; $i--) {
                                echo "<option value='$i'" . ($i == $currentYear ? ' selected' : '') . ">$i</option>";
                            }
                        @endphp
                    </select>
                </td>
                <td><input type="text" class="form-control" name="delivery_condition" required></td>
                <td><input type="number" step="0.01" class="form-control" name="cash_rate"></td>
                <td><input type="number" class="form-control" name="cash_days"></td>
                <td><input type="number" step="0.01" class="form-control" name="credit_rate"></td>
                <td><input type="number" class="form-control" name="credit_days"></td>
                <td><input type="text" class="form-control" name="others"></td>
                <td><input type="text" class="form-control" name="remarks"></td>
                <td>
                    <button class="btn btn-success btn-sm save-row">Save</button>
                    <button class="btn btn-danger btn-sm cancel-row">Cancel</button>
                </td>
            </tr>`);

                    $('#indicativePricesTable tbody').append(emptyRow);

                    initializeDynamicSelect2($('#indicativePricesTable tbody .empty-row .product-select'),
                        'products', 'name', 'id', false, false);
                    initializeDynamicSelect2($('#indicativePricesTable tbody .empty-row .location-select'),
                        'company_locations', 'name', 'id', false, false);
                    initializeDynamicSelect2($('#indicativePricesTable tbody .empty-row .type-select'),
                        'sauda_types', 'name', 'id', false, false);
                },
                error: function(xhr) {
                    $('#indicativePricesTable tbody').html(
                        '<tr><td colspan="13" class="text-center text-danger">Error loading data</td></tr>');
                }
            });
        }

        function getFieldName(index) {
            const fields = [
                '',
                'product_id',
                'location_id',
                'type_id',
                'crop_year',
                'delivery_condition',
                'cash_rate',
                'cash_days',
                'credit_rate',
                'credit_days',
                'others',
                'remarks'
            ];
            return fields[index];
        }

        function showToast(type, message) {
            console.log(type + ': ' + message);
        }
    </script>
@endsection
