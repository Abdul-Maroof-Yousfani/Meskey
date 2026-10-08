<form method="POST" action="{{ route('company.update', $company->id) }}" id="ajaxSubmit" enctype="multipart/form-data"> 
@method('PUT')
<input type="hidden" id="listRefresh" value="{{ route('get.company') }}" />
<input type="hidden" name="deleted_settings" id="deletedSettingsInput" value="" />

<div class="row form-mar">
    <div class="col-md-12 mb-4">
        <div class="avatar-upload">
            <div class="avatar-edit">
                <input type='file' id="imageUpload" name="logo" accept=".png, .jpg, .jpeg" />
                <label for="imageUpload">
                      <i class="ft-camera"></i>
                </label>
            </div>
            <div class="avatar-preview">
                <div id="imagePreview" style="background-image: url('{{ image_path($company->logo) }}');">
                </div>
            </div>
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="name" value="{{ $company->name }}" placeholder="Name" class="form-control" />
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group">
            <label>Email: <small>(Optional)</small></label>
            <input type="email" name="email" value="{{ $company->email }}" placeholder="Email" class="form-control" />
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group">
            <label>Phone: <small>(Optional)</small></label>
            <input type="text" name="phone" value="{{ $company->phone }}" placeholder="Phone" class="form-control" />
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group">
            <label>Registration No: <small>(Optional)</small></label>
            <input type="text" name="registration_no" value="{{ $company->registration_no }}" placeholder="Registration No" class="form-control" />
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group">
            <label>NTN#: <small>(Optional)</small></label>
            <input type="text" name="ntn" value="{{ $company->ntn }}" placeholder="NTN No" class="form-control" />
        </div>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6">
        <div class="form-group">
            <label>STN#: <small>(Optional)</small></label>
            <input type="text" name="stn" value="{{ $company->stn }}" placeholder="ST No" class="form-control" />
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Address:</label>
            <textarea name="address" rows="2" class="form-control" placeholder="Address">{{ $company->address }}</textarea>
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Connection Name: <small>(Optional)</small></label>
            <input type="text" name="connection_name" value="{{ $company->connection_name ?? $company->connection_database }}" placeholder="Connection name" class="form-control" />
        </div>
    </div>

    <!-- Company Settings Management Section -->
    <div class="col-12 mt-2">
        <div class="card border mb-3" style="border-radius: 8px; border-color: #d1d5db; box-shadow: 0 1px 4px rgba(0,0,0,0.05);">
            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <div>
                    <h5 class="mb-0 font-weight-bold text-primary" style="font-size: 1rem;">
                        <i class="ft-sliders mr-1"></i> Company Settings
                    </h5>
                    <small class="text-muted">Manage company-specific feature flags and configuration keys</small>
                </div>
                <button type="button" class="btn btn-sm btn-primary" id="btnAddNewSetting">
                    <i class="ft-plus mr-1"></i> Add Setting
                </button>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm m-0" id="companySettingsTable">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 26%;">Key <span class="text-danger">*</span></th>
                                <th style="width: 18%;">Type</th>
                                <th style="width: 26%;">Value</th>
                                <th style="width: 18%;">Group</th>
                                <th style="width: 12%; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="companySettingsTbody">
                            @forelse($company->settings as $idx => $setting)
                                <tr class="setting-row" data-id="{{ $setting->id }}">
                                    <td>
                                        <input type="hidden" name="settings[{{ $idx }}][id]" class="setting-id-input" value="{{ $setting->id }}">
                                        <input type="text" name="settings[{{ $idx }}][key]" value="{{ $setting->key }}" class="form-control form-control-sm setting-key-input" placeholder="e.g. stock_check" required />
                                    </td>
                                    <td>
                                        <select name="settings[{{ $idx }}][type]" class="form-control form-control-sm setting-type-select">
                                            <option value="string" {{ $setting->type === 'string' ? 'selected' : '' }}>string</option>
                                            <option value="boolean" {{ $setting->type === 'boolean' ? 'selected' : '' }}>boolean</option>
                                            <option value="integer" {{ $setting->type === 'integer' ? 'selected' : '' }}>integer</option>
                                            <option value="float" {{ $setting->type === 'float' ? 'selected' : '' }}>float</option>
                                            <option value="json" {{ $setting->type === 'json' ? 'selected' : '' }}>json</option>
                                            <option value="datetime" {{ $setting->type === 'datetime' ? 'selected' : '' }}>datetime</option>
                                        </select>
                                    </td>
                                    <td class="setting-value-cell">
                                        @if($setting->type === 'boolean')
                                            <select name="settings[{{ $idx }}][value]" class="form-control form-control-sm setting-value-input">
                                                <option value="1" {{ in_array((string)$setting->value, ['1', 'true', 'yes'], true) ? 'selected' : '' }}>True</option>
                                                <option value="0" {{ in_array((string)$setting->value, ['0', 'false', 'no'], true) ? 'selected' : '' }}>False</option>
                                            </select>
                                        @elseif($setting->type === 'datetime')
                                            @php
                                                $dtVal = '';
                                                if (!empty($setting->value)) {
                                                    try {
                                                        $dtVal = \Carbon\Carbon::parse($setting->value)->format('Y-m-d\TH:i');
                                                    } catch (\Exception $e) {
                                                        $dtVal = $setting->value;
                                                    }
                                                }
                                            @endphp
                                            <input type="datetime-local" name="settings[{{ $idx }}][value]" value="{{ $dtVal }}" class="form-control form-control-sm setting-value-input" />
                                        @elseif($setting->type === 'integer')
                                            <input type="number" step="1" name="settings[{{ $idx }}][value]" value="{{ $setting->value }}" class="form-control form-control-sm setting-value-input" placeholder="Integer value" />
                                        @elseif($setting->type === 'float')
                                            <input type="number" step="any" name="settings[{{ $idx }}][value]" value="{{ $setting->value }}" class="form-control form-control-sm setting-value-input" placeholder="Decimal value" />
                                        @elseif($setting->type === 'json')
                                            <input type="text" name="settings[{{ $idx }}][value]" value="{{ $setting->value }}" class="form-control form-control-sm setting-value-input" placeholder='{"key": "value"}' />
                                        @else
                                            <input type="text" name="settings[{{ $idx }}][value]" value="{{ $setting->value }}" class="form-control form-control-sm setting-value-input" placeholder="Value" />
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" name="settings[{{ $idx }}][group]" value="{{ $setting->group }}" class="form-control form-control-sm setting-group-input" placeholder="e.g. inventory" />
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-success btn-save-setting" data-id="{{ $setting->id }}" title="Save Setting">
                                                <i class="ft-check font-medium-1"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-setting" data-id="{{ $setting->id }}" title="Delete Setting">
                                                <i class="ft-trash font-medium-1"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr id="noSettingsRow">
                                    <td colspan="5" class="text-center text-muted py-3">
                                        <em>No custom settings defined for this company yet. Click <strong>Add Setting</strong> to create one.</em>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row bottom-button-bar">
    <div class="col-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
        <button type="submit" class="btn btn-primary submitbutton">Save</button>
    </div>
</div>
</form>

<script>
$(document).ready(function() {
    var companyId = {{ $company->id }};
    var storeSettingUrl = "{{ route('company.settings.store', $company->id) }}";
    var baseUrlSetting = "{{ url('company/' . $company->id . '/settings') }}";

    // Helper to render type-specific input field
    function renderValueInput(inputName, type, currentVal) {
        if (type === 'boolean') {
            var isTrue = (currentVal === '1' || currentVal === 'true' || currentVal === true);
            return `
                <select name="${inputName}" class="form-control form-control-sm setting-value-input">
                    <option value="1" ${isTrue ? 'selected' : ''}>True</option>
                    <option value="0" ${!isTrue ? 'selected' : ''}>False</option>
                </select>
            `;
        } else if (type === 'datetime') {
            var dt = '';
            if (currentVal) {
                dt = String(currentVal).replace(' ', 'T').substring(0, 16);
            }
            return `
                <input type="datetime-local" name="${inputName}" value="${dt}" class="form-control form-control-sm setting-value-input" />
            `;
        } else if (type === 'integer') {
            return `
                <input type="number" step="1" name="${inputName}" value="${currentVal || ''}" class="form-control form-control-sm setting-value-input" placeholder="Integer value" />
            `;
        } else if (type === 'float') {
            return `
                <input type="number" step="any" name="${inputName}" value="${currentVal || ''}" class="form-control form-control-sm setting-value-input" placeholder="Decimal value" />
            `;
        } else if (type === 'json') {
            return `
                <input type="text" name="${inputName}" value="${currentVal || ''}" class="form-control form-control-sm setting-value-input" placeholder='{"key": "value"}' />
            `;
        } else {
            return `
                <input type="text" name="${inputName}" value="${currentVal || ''}" class="form-control form-control-sm setting-value-input" placeholder="Value" />
            `;
        }
    }

    // Dynamic value field switcher based on type
    $(document).on('change', '.setting-type-select', function() {
        var $row = $(this).closest('tr');
        var selectedType = $(this).val();
        var $valCell = $row.find('.setting-value-cell');
        var inputName = $valCell.find('.setting-value-input').attr('name') || 'settings[' + Date.now() + '][value]';
        var currentVal = $valCell.find('.setting-value-input').val();

        // If switching from boolean to text/number, don't keep 1 or 0 as literal value unless intended
        if (selectedType !== 'boolean' && (currentVal === '1' || currentVal === '0')) {
            currentVal = '';
        }

        $valCell.html(renderValueInput(inputName, selectedType, currentVal));
    });

    // Add new setting row
    $('#btnAddNewSetting').on('click', function() {
        $('#noSettingsRow').remove();
        var rowIdx = 'new_' + Date.now();
        var newRowHtml = `
            <tr class="setting-row" data-id="">
                <td>
                    <input type="hidden" name="settings[${rowIdx}][id]" class="setting-id-input" value="">
                    <input type="text" name="settings[${rowIdx}][key]" value="" class="form-control form-control-sm setting-key-input" placeholder="e.g. key_name" required />
                </td>
                <td>
                    <select name="settings[${rowIdx}][type]" class="form-control form-control-sm setting-type-select">
                        <option value="string" selected>string</option>
                        <option value="boolean">boolean</option>
                        <option value="integer">integer</option>
                        <option value="float">float</option>
                        <option value="json">json</option>
                        <option value="datetime">datetime</option>
                    </select>
                </td>
                <td class="setting-value-cell">
                    <input type="text" name="settings[${rowIdx}][value]" value="" class="form-control form-control-sm setting-value-input" placeholder="Value" />
                </td>
                <td>
                    <input type="text" name="settings[${rowIdx}][group]" value="general" class="form-control form-control-sm setting-group-input" placeholder="e.g. inventory" />
                </td>
                <td class="text-center align-middle">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-success btn-save-setting" data-id="" title="Save Setting">
                            <i class="ft-check font-medium-1"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-setting" data-id="" title="Delete Setting">
                            <i class="ft-trash font-medium-1"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        $('#companySettingsTbody').append(newRowHtml);
    });

    // Inline save single setting via AJAX
    $(document).on('click', '.btn-save-setting', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var settingId = $row.data('id') || $row.find('.setting-id-input').val();
        var key = $row.find('.setting-key-input').val();
        var type = $row.find('.setting-type-select').val();
        var value = $row.find('.setting-value-input').val();
        var group = $row.find('.setting-group-input').val();

        if (!key || key.trim() === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Key Required',
                text: 'Please enter a setting key name.'
            });
            return;
        }

        var url = settingId ? (baseUrlSetting + '/' + settingId) : storeSettingUrl;
        var method = settingId ? 'PUT' : 'POST';

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>');

        $.ajax({
            url: url,
            type: method,
            data: {
                _token: '{{ csrf_token() }}',
                key: key,
                type: type,
                value: value,
                group: group
            },
            success: function(resp) {
                $btn.prop('disabled', false).html('<i class="ft-check font-medium-1"></i>');
                if (resp.data && resp.data.id) {
                    $row.attr('data-id', resp.data.id);
                    $row.find('.setting-id-input').val(resp.data.id);
                    $btn.attr('data-id', resp.data.id);
                    $row.find('.btn-delete-setting').attr('data-id', resp.data.id);
                }
                if (typeof toastr !== 'undefined') {
                    toastr.success(resp.success || 'Setting saved successfully!');
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved',
                        text: resp.success || 'Setting saved successfully!',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).html('<i class="ft-check font-medium-1"></i>');
                var errMessage = 'Failed to save setting.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errMessage
                });
            }
        });
    });

    // Delete single setting
    $(document).on('click', '.btn-delete-setting', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr');
        var settingId = $btn.data('id') || $row.data('id') || $row.find('.setting-id-input').val();

        if (!settingId) {
            // Unsaved new row, just remove from DOM
            $row.fadeOut(200, function() {
                $(this).remove();
                if ($('#companySettingsTbody tr.setting-row').length === 0) {
                    $('#companySettingsTbody').append('<tr id="noSettingsRow"><td colspan="5" class="text-center text-muted py-3"><em>No custom settings defined for this company yet. Click <strong>Add Setting</strong> to create one.</em></td></tr>');
                }
            });
            return;
        }

        Swal.fire({
            title: 'Delete Setting?',
            text: 'Are you sure you want to delete this setting?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                var deleteUrl = baseUrlSetting + '/' + settingId;
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(resp) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            if ($('#companySettingsTbody tr.setting-row').length === 0) {
                                $('#companySettingsTbody').append('<tr id="noSettingsRow"><td colspan="5" class="text-center text-muted py-3"><em>No custom settings defined for this company yet. Click <strong>Add Setting</strong> to create one.</em></td></tr>');
                            }
                        });
                        if (typeof toastr !== 'undefined') {
                            toastr.success(resp.success || 'Setting deleted successfully.');
                        } else {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: resp.success || 'Setting deleted successfully.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        var currentDeleted = $('#deletedSettingsInput').val();
                        var arr = currentDeleted ? currentDeleted.split(',') : [];
                        arr.push(settingId);
                        $('#deletedSettingsInput').val(arr.join(','));
                        $row.remove();
                    }
                });
            }
        });
    });
});
</script>
