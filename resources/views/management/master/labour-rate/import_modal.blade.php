<div class="card p-3">
    <div class="card shadow-none border-0 mb-0">
        <div class="card-header bg-white border-bottom-0 pb-0 px-0">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="text-bold-600 mb-0"><i class="ft-info mr-1 text-primary"></i> Data Import Instructions</h5>
                <a href="{{ route('labour-rate.download-sample') }}" class="btn btn-outline-info btn-sm">
                    <i class="ft-download mr-1"></i> Download Sample Excel
                </a>
            </div>
            <div class="card p-2 bg-light bg-lighten-4 mb-2" style="border: 1px solid #d1d4d7; border-radius: 8px;">
                <p class="mb-2" style="font-size: 13px; color: #555;">
                    Follow the structure below. The first row must be the <b>column header</b>. Supports <b>.xlsx, .xls, and .csv</b> files.
                    Required fields are marked with a <span class="text-danger">*</span>.
                    <br><small class="text-muted">Tip: Download the sample file above — it includes a "Reference Data" sheet with valid packings, commodities, and locations from your database.</small>
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size: 11px;">
                        <thead style="background-color: #f8f9fb;">
                            <tr>
                                <th class="border-top-0">#</th>
                                <th class="border-top-0">Column Header</th>
                                <th class="border-top-0 text-center">Required</th>
                                <th class="border-top-0">Example / Validation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td><strong>Rate</strong></td>
                                <td class="text-center font-weight-bold"><span class="text-danger">*</span></td>
                                <td>Numeric rate value (e.g. <code>12.00</code>, <code>10.50</code>)</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td><strong>Packing</strong></td>
                                <td class="text-center font-weight-bold"><span class="text-danger">*</span></td>
                                <td>Bag Packing Name or ID (e.g. <code>50 kg</code>, <code>25 kg</code>, <code>5 kg</code>)</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td><strong>Commodity</strong></td>
                                <td class="text-center font-weight-bold"><span class="text-danger">*</span></td>
                                <td>Commodity/Category Name or ID (e.g. <code>Irri-6</code>, <code>Super</code>)</td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td><strong>Factory / Location</strong></td>
                                <td class="text-center font-weight-bold"><span class="text-danger">*</span></td>
                                <td>Arrival Location / Factory Name or ID (e.g. <code>A-45</code>, <code>Larkana Location</code>)</td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td><strong>Description</strong></td>
                                <td class="text-center text-muted">Optional</td>
                                <td>Any note or remark (e.g. <code>Standard loading rate</code>)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <form id="importLabourRatesForm" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    <label class="form-label font-weight-bold">Select Excel or CSV File <span class="text-danger">*</span></label>
                    <input type="file" id="labourRateFile" name="file" class="form-control" accept=".xlsx, .xls, .csv" required>
                    <small class="text-muted mt-1 d-block">Supported file formats: .xlsx, .xls, .csv (Max 10 MB)</small>
                </div>
            </div>

            <div id="importProgressWrapper" class="mt-3" style="display: none;">
                <div class="d-flex justify-content-between mb-1">
                    <span id="importProgressStatus" class="font-weight-600">Uploading & Processing...</span>
                </div>
                <div class="progress" style="height: 12px; border-radius: 10px;">
                    <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 100%; border-radius: 10px;"></div>
                </div>
                <div id="importResultLog" class="mt-2 p-2 border bg-light shadow-sm" style="max-height: 220px; overflow-y: auto; font-size: 12px; border-radius: 8px; display: none;"></div>
            </div>

            <div class="text-right mt-3">
                <button type="submit" id="startImportBtn" class="btn btn-primary btn-min-width">
                    <i class="ft-upload mr-1"></i> Start Import
                </button>
                <button type="button" class="btn btn-secondary modal-sidebar-close" data-close="model">Close</button>
            </div>
        </form>
    </div>
</div>

<script>
    $('#importLabourRatesForm').on('submit', function(e) {
        e.preventDefault();

        const fileInput = document.getElementById('labourRateFile');
        if (!fileInput.files || fileInput.files.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No File Selected',
                text: 'Please select an Excel or CSV file to import.'
            });
            return;
        }

        const formData = new FormData(this);
        const $submitBtn = $('#startImportBtn');
        const $progressWrapper = $('#importProgressWrapper');
        const $resultLog = $('#importResultLog');

        $progressWrapper.show();
        $resultLog.hide().html('');
        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Importing...');

        $.ajax({
            url: '{{ route("labour-rate.import") }}',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                $submitBtn.prop('disabled', false).html('<i class="ft-upload mr-1"></i> Start Import');
                $('#importProgressStatus').text('Completed!');

                let logHtml = `<div class="mb-2">`;
                logHtml += `<span class="badge badge-success mr-1">Created: ${response.created_count || 0}</span>`;
                logHtml += `<span class="badge badge-info mr-1">Updated: ${response.updated_count || 0}</span>`;
                if (response.error_count && response.error_count > 0) {
                    logHtml += `<span class="badge badge-danger">Errors: ${response.error_count}</span>`;
                }
                logHtml += `</div>`;

                if (response.errors && response.errors.length > 0) {
                    logHtml += `<div class="text-danger mt-2 font-weight-bold">Issues encountered:</div><ul class="text-danger pl-3 mb-0">`;
                    response.errors.forEach(err => {
                        logHtml += `<li>${err}</li>`;
                    });
                    logHtml += `</ul>`;
                }

                $resultLog.html(logHtml).show();

                Swal.fire({
                    icon: (response.error_count && response.error_count > 0) ? 'warning' : 'success',
                    title: (response.error_count && response.error_count > 0) ? 'Import Completed with Notes' : 'Success!',
                    text: response.message
                });

                // Refresh the list table
                if (typeof filterationCommon === 'function') {
                    filterationCommon('{{ route("get.labour-rate") }}');
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).html('<i class="ft-upload mr-1"></i> Start Import');
                $('#importProgressStatus').text('Import Failed');
                
                let errorMsg = 'An unexpected error occurred.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        let parsed = JSON.parse(xhr.responseText);
                        errorMsg = parsed.message || parsed.error || xhr.responseText;
                    } catch(e) {
                        errorMsg = xhr.statusText;
                    }
                }

                $resultLog.html(`<div class="text-danger font-weight-bold">Error: ${errorMsg}</div>`).show();

                Swal.fire({
                    icon: 'error',
                    title: 'Import Failed',
                    text: errorMsg
                });
            }
        });
    });
</script>
