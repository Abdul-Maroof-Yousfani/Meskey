@php
    $companies = App\Models\Acl\Company::all();
    $locations = App\Models\Master\CompanyLocation::where('status', 'active')->get();
@endphp

<div class="card p-3">
    <div class="card-header bg-white border-bottom-0 pb-0">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h5 class="text-bold-600 mb-0"><i class="ft-info mr-1 text-primary"></i> Data Import Instructions</h5>
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-outline-info btn-sm mr-2" id="downloadSampleBtn"><i class="ft-download mr-1"></i> Download Sample CSV</button>
                <button type="button" class="close modal-sidebar-close closebutton" aria-label="Close" style="font-size: 1.5rem; line-height: 1; cursor: pointer;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
        <div class="card p-2 bg-light bg-lighten-4 mb-2" style="border: 1px solid #d1d4d7; border-radius: 8px;">
            <p class="mb-2" style="font-size: 13px; color: #555;">Follow the CSV structure below precisely. The first row must be the <b>header</b>. Required fields are marked with a <span class="text-danger">*</span>. Status is automatically set to <b>Active</b>.</p>
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
                        <tr><td>0</td><td><strong>Company Name</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td>Unique Company Name of the Customer (also creates COA Account)</td></tr>
                        <tr><td>1</td><td><strong>Owner Name</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td>Full Name of the Business Owner</td></tr>
                        <tr><td>2</td><td><strong>Owner Mobile Number</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td>11 digits (e.g. 03001234567)</td></tr>
                        <tr><td>3</td><td><strong>Owner CNIC Number</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td>Format: 12345-1234567-1</td></tr>
                        <tr><td>4</td><td><strong>Customer Type</strong></td><td class="text-center font-weight-bold"><span class="text-danger">*</span></td><td><code>local</code> or <code>international</code> (Default: <code>local</code>)</td></tr>
                        <tr><td>5-9</td><td>Misc Details</td><td class="text-center">-</td><td>Email Address, Phone Number, Address, NTN Number, STN Number (Optional)</td></tr>
                        <tr><td>10-11</td><td>Next of Kin</td><td class="text-center">-</td><td>Next of Kin Name, Next of Kin Mobile Number (Optional)</td></tr>
                        <tr><td>12</td><td>Create as Broker</td><td class="text-center">-</td><td><code>Yes</code> / <code>No</code></td></tr>
                        <tr><td colspan="4" class="text-bold-600 bg-white py-2" style="color: #666;"><i class="ft-credit-card mr-1"></i> Optional Company Bank Details</td></tr>
                        <tr><td>13-17</td><td>Company Bank</td><td class="text-center">-</td><td>Company Bank Name, Company Branch Name, Company Branch Code, Company Account Title, Company Account Number</td></tr>
                        <tr><td colspan="4" class="text-bold-600 bg-white py-2" style="color: #666;"><i class="ft-credit-card mr-1"></i> Optional Owner Bank Details</td></tr>
                        <tr><td>18-22</td><td>Owner Bank</td><td class="text-center">-</td><td>Owner Bank Name, Owner Branch Name, Owner Branch Code, Owner Account Title, Owner Account Number</td></tr>
                        <tr><td colspan="4" class="text-bold-600 bg-white py-2" style="color: #666;"><i class="ft-users mr-1"></i> Optional Consignee Details</td></tr>
                        <tr><td>23-27</td><td>Consignee Details</td><td class="text-center">-</td><td>Consignee Name, Consignee Contact Number, Consignee Contact Person, Consignee Email Address, Consignee Address</td></tr>
                        <tr><td>28</td><td><strong>Location Names</strong></td><td class="text-center">-</td><td>Pipe separated Names (e.g. <code>Karachi|Lahore</code>). Case-insensitive.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form id="importCustomersForm" class="mt-2">
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
            <a type="button" class="btn btn-danger modal-sidebar-close closebutton" style="color: #fff; cursor: pointer;">Close</a>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        let stopRequested = false;

        $(document).on('click', '.modal-sidebar-close, .closebutton, [data-close="model"]', function() {
            $('.modal-sidebar').removeClass('open');
            $('body').removeClass('drawer-opened');
            $('.modal-sidebar .modal-tab-content').html('');
            $('#settinsgs, #modal2, #deletemodal, #ajaxModal').hide().addClass('d-none');
        });

        $('#downloadSampleBtn').click(function() {
            const headers = "Company Name,Owner Name,Owner Mobile Number,Owner CNIC Number,Customer Type,Email Address,Phone Number,Address,NTN Number,STN Number,Next of Kin Name,Next of Kin Mobile Number,Create as Broker,Company Bank Name,Company Branch Name,Company Branch Code,Company Account Title,Company Account Number,Owner Bank Name,Owner Branch Name,Owner Branch Code,Owner Account Title,Owner Account Number,Consignee Name,Consignee Contact Number,Consignee Contact Person,Consignee Email Address,Consignee Address,Location Names";
            // Using Excel-friendly quotes and formula for leading zeros
            const sampleRow = 'Alpha Trading,John Doe,"03001234567",12345-1234567-1,local,john@example.com,021-345678,Plot 123 Industrial Area Karachi,NTN-123456,STN-789012,Jane Doe,"03007654321",No,Muslim Commercial Bank,SITE Branch,MCB001,Alpha Trading,"0011223344",United Bank Limited,Clifton Branch,UBL002,John Doe,"9988776655",Alpha Port Facility,"03211112222",Ali Khan,ali@alphatrading.com,Port Qasim Karachi,';
            const csvContent = headers + "\n" + sampleRow;
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "customer_import_sample.csv");
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
                    // Robust CSV parsing to handle empty columns and quotes
                    const data = [];
                    let start = 0;
                    let inQuotes = false;
                    for (let i = 0; i < row.length; i++) {
                        if (row[i] === '"') inQuotes = !inQuotes;
                        if (row[i] === ',' && !inQuotes) {
                            data.push(row.substring(start, i));
                            start = i + 1;
                        }
                    }
                    data.push(row.substring(start));

                    const sanitizedData = data.map(s => s.trim().replace(/^"|"$/g, '').replace(/^=/, '').replace(/^"|"$/g, ''));

                    const customerName = sanitizedData[0] || 'Unknown';
                    $('#importProgressStatus').text(`Processing: ${customerName}`);

                    try {
                        const response = await $.ajax({
                            url: '{{ route("customer.import-row") }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                row_data: sanitizedData
                            }
                        });
                        
                        successCount++;
                        logResult(index + 1, customerName, 'Success', 'text-success');
                    } catch (error) {
                        failCount++;
                        let msg = error.responseJSON ? error.responseJSON.message : 'Network error';
                        logResult(index + 1, customerName, `Failed: ${msg}`, 'text-danger');
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
                    filterationCommon(`{{ route('get.customer') }}`);
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
