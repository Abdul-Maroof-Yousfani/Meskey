@php
    $companies = App\Models\Acl\Company::all();
    $locations = App\Models\Master\CompanyLocation::where('status', 'active')->get();
@endphp

<div class="card p-3">
<div class="card shadow-none border-0 mb-0">
    <div class="card-header bg-white border-bottom-0 pb-0">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h5 class="text-bold-600 mb-0"><i class="ft-info mr-1 text-primary"></i> Data Import Instructions</h5>
            <button type="button" class="btn btn-outline-info btn-sm" id="downloadSampleBtn"><i class="ft-download mr-1"></i> Download Sample CSV</button>
        </div>
        <div class="card p-2 bg-light bg-lighten-4 mb-2" style="border: 1px solid #d1d4d7; border-radius: 8px;">
            <p class="mb-2" style="font-size: 13px; color: #555;">Follow the CSV structure below precisely. The first row must be the <b>header</b>. Required fields are marked with a <span class="text-danger">*</span>.</p>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size: 11px;">
                    <thead style="background-color: #f8f9fb;">
                        <tr>
                            <th class="border-top-0">#</th>
                            <th class="border-top-0">Column Title</th>
                            <th class="border-top-0 text-center">Required</th>
                            <th class="border-top-0">Notes / Validation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>0</td><td><strong>Company Name</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td>Unique Company Name of the Supplier</td></tr>
                        <tr><td>1</td><td><strong>Owner Name</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td>Full Name of the Business Owner</td></tr>
                        <tr><td>2</td><td><strong>Owner Mobile No</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td>11 digits (e.g. 03001234567)</td></tr>
                        <tr><td>3</td><td><strong>Owner CNIC No</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td>Format: 12345-1234567-1</td></tr>
                        <tr><td>4</td><td><strong>Type</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td><code>raw_material</code> or <code>store_supplier</code></td></tr>
                        <tr><td>5</td><td><strong>Status</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td><code>active</code> or <code>inactive</code></td></tr>
                        <tr><td>6-10</td><td>Misc Details</td><td class="text-center">-</td><td>Email, Phone, Address, NTN, STN (Optional)</td></tr>
                        <tr><td>11</td><td>Create Broker</td><td class="text-center">-</td><td><code>Yes</code>/<code>No</code></td></tr>
                        <tr><td colspan="4" class="text-bold-600 bg-white py-2" style="color: #666;"><i class="ft-credit-card mr-1"></i> Optional Bank Details</td></tr>
                        <tr><td>12-16</td><td>Company Bank</td><td class="text-center">-</td><td>Bank, Branch, Code, Title, Account</td></tr>
                        <tr><td>17-21</td><td>Owner Bank</td><td class="text-center">-</td><td>Bank, Branch, Code, Title, Account</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form id="importSuppliersForm" class="mt-2">
        <div class="row">
            <div class="col-md-12">
                <label class="form-label font-weight-bold">Upload CSV File <span class="text-danger">*</span></label>
                <input type="file" id="csvFile" name="csvFile" class="form-control" accept=".csv" required>
            </div>
        </div>

        <div id="importProgressWrapper" class="mt-3" style="display: none;">
            <div class="d-flex justify-content-between mb-1">
                <span id="importProgressStatus" class="font-weight-600">Preparing...</span>
                <span id="importProgressPercent" class="badge badge-primary">0%</span>
            </div>
            <div class="progress" style="height: 12px; border-radius: 10px;">
                <div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%; border-radius: 10px;"></div>
            </div>
            <div id="importResultLog" class="mt-2 p-2 border bg-light shadow-sm" style="max-height: 200px; overflow-y: auto; font-size: 11px; border-radius: 8px;"></div>
        </div>

        <div class="text-right mt-3">
            <button type="button" id="startImportBtn" class="btn btn-primary btn-min-width"><i class="ft-upload mr-1"></i> Start Import</button>
            <button type="button" id="stopImportBtn" class="btn btn-danger btn-min-width" style="display: none;"><i class="ft-x mr-1"></i> Cancel</button>
            <button type="button" class="btn btn-secondary" onclick="$('#ajaxModal').modal('hide')">Close</button>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        let stopRequested = false;

        $('#downloadSampleBtn').click(function() {
            const headers = "Company Name,Owner Name,Owner Mobile No,Owner CNIC No,Type,Status,Email,Phone,Address,NTN,STN,Create as Broker,Comp Bank Name,Comp Branch,Comp Code,Comp Title,Comp Account,Own Bank Name,Own Branch,Own Code,Own Title,Own Account";
            // Using Excel-friendly quotes and formula for leading zeros
            const sampleRow = 'Matrix Co,John Doe,"03001234567",12345-1234567-1,raw_material,active,john@example.com,021-345678,Street 123 Karachi,NTN888,STN999,No,MCB,Site Branch,MC001,Title 1,"000111222",UBL,Gulshan,UB002,Title 2,"333444555"';
            const csvContent = headers + "\n" + sampleRow;
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "supplier_import_sample.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        $('#startImportBtn').click(function() {
            const fileInput = document.getElementById('csvFile');

            if (fileInput.files.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Wait!',
                    text: 'Please select a CSV file.'
                });
                return;
            }

            const file = fileInput.files[0];
            
            // Check file extension
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();
            if (fileExtension !== 'csv') {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File',
                    text: 'Only CSV files are allowed.'
                });
                return;
            }

            const reader = new FileReader();

            reader.onload = function(e) {
                const text = e.target.result;
                const rows = text.split(/\r?\n/).filter(row => row.trim() !== '');
                
                if (rows.length <= 1) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Empty File',
                        text: 'CSV file is empty or only contains headers.'
                    });
                    return;
                }

                const dataRows = rows.slice(1); // Skip header
                const totalRows = dataRows.length;
                let processedRows = 0;
                let successCount = 0;
                let failCount = 0;

                $('#importProgressWrapper').show();
                $('#startImportBtn').hide();
                $('#stopImportBtn').show();
                $('#importResultLog').html('');
                stopRequested = false;

                processRowRecursive(0);

                async function processRowRecursive(index) {
                    if (index >= totalRows || stopRequested) {
                        finishImport();
                        return;
                    }

                    const row = dataRows[index];
                    // Improved CSV parsing: handles quotes and multi-word names with spaces
                    const data = row.match(/(".*?"|[^,]+)/g) || [];
                    const sanitizedData = data.map(s => s.trim().replace(/^"|"$/g, '').replace(/^=/, '').replace(/^"|"$/g, ''));

                    const supplierName = sanitizedData[0] || 'Unknown';
                    $('#importProgressStatus').text(`Processing: ${supplierName}`);

                    try {
                        const response = await $.ajax({
                            url: '{{ route("supplier.import-row") }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                row_data: sanitizedData
                            }
                        });
                        
                        successCount++;
                        logResult(index + 1, supplierName, 'Success', 'text-success');
                    } catch (error) {
                        failCount++;
                        let msg = error.responseJSON ? error.responseJSON.message : 'Network error';
                        logResult(index + 1, supplierName, `Failed: ${msg}`, 'text-danger');
                    }

                    processedRows++;
                    const percent = Math.round((processedRows / totalRows) * 100);
                    $('#importProgressBar').css('width', percent + '%');
                    $('#importProgressPercent').text(percent + '%');

                    processRowRecursive(index + 1);
                }

                function logResult(rowNum, name, message, colorClass) {
                    $('#importResultLog').append(`<div class="${colorClass}">Row ${rowNum} [${name}]: ${message}</div>`);
                    const log = document.getElementById('importResultLog');
                    log.scrollTop = log.scrollHeight;
                }

                function finishImport() {
                    $('#importProgressStatus').text(stopRequested ? 'Import Cancelled' : 'Import Complete');
                    $('#startImportBtn').show().text('Import More');
                    $('#stopImportBtn').hide();
                    Swal.fire({
                        icon: stopRequested ? 'info' : 'success',
                        title: stopRequested ? 'Import Stopped' : 'Import Finished',
                        text: `Success: ${successCount}, Failed: ${failCount}`
                    });
                    filterationCommon(`{{ route('get.supplier') }}`);
                }
            };

            reader.readAsText(file);
        });

        $('#stopImportBtn').click(function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "Cancel the import process? Records already imported will stay.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    stopRequested = true;
                }
            });
        });
    });
</script>
