<form method="POST" action="{{ route('company.update', $company->id) }}" id="ajaxSubmit" enctype="multipart/form-data" novalidate> 
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

    <!-- Company Settings Management Section (Groupwise) -->
    @php
        $typeBadges = [
            'boolean'  => 'badge-success',
            'string'   => 'badge-primary',
            'integer'  => 'badge-warning',
            'float'    => 'badge-info',
            'json'     => 'badge-dark',
            'datetime' => 'badge-secondary',
        ];

        $groupedSettings = $company->settings->groupBy(function($item) {
            return strtolower(trim($item->group ?: 'general'));
        });
    @endphp

    <div class="col-12 mt-2">
        <div class="card border mb-3" style="border-radius: 8px; border-color: #d1d5db; box-shadow: 0 2px 6px rgba(0,0,0,0.06);">
            <div class="card-header d-flex justify-content-between align-items-center py-2 px-3" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                <div>
                    <h5 class="mb-0 font-weight-bold text-primary" style="font-size: 1rem;">
                        <i class="ft-sliders mr-1"></i> Company Settings
                    </h5>
                    <small class="text-muted">Manage company-specific feature flags and configuration keys groupwise</small>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddNewGroup" title="Add a new group category">
                        <i class="ft-folder-plus mr-1"></i> Add Group
                    </button>
                    <button type="button" class="btn btn-sm btn-primary ml-1" id="btnAddNewSetting" title="Add setting to active group">
                        <i class="ft-plus mr-1"></i> Add Setting
                    </button>
                </div>
            </div>

            <datalist id="pusherSettingKeysList">
                <option value="PUSHER_APP_ID">Pusher Application ID</option>
                <option value="PUSHER_APP_KEY">Pusher Public Key</option>
                <option value="PUSHER_APP_SECRET">Pusher Secret Key</option>
                <option value="PUSHER_APP_CLUSTER">Pusher Cluster (e.g. ap2, mt1, ap4)</option>
                <option value="PUSHER_STATUS">Notification Status (1 = Active, 0 = Inactive)</option>
            </datalist>

            <div class="card-body p-3">
                <!-- Group Filter Navigation Pills -->
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
                    <ul class="nav nav-pills group-filter-pills p-1 rounded bg-light border" id="settingGroupPills" role="tablist" style="gap: 4px;">
                        <li class="nav-item">
                            <a class="nav-link active py-1 px-3 btn-filter-group font-weight-bold" href="javascript:void(0);" data-group="all" style="font-size: 0.85rem; border-radius: 4px;">
                                <i class="ft-grid mr-1"></i> All Settings
                            </a>
                        </li>
                        @foreach($groupedSettings as $grpKey => $grpItems)
                            @php
                                $displayGrpName = ucwords(str_replace(['_', '-'], ' ', $grpKey));
                            @endphp
                            <li class="nav-item">
                                <a class="nav-link py-1 px-3 btn-filter-group font-weight-bold" href="javascript:void(0);" data-group="{{ $grpKey }}" style="font-size: 0.85rem; border-radius: 4px;">
                                    <i class="ft-folder mr-1 text-primary"></i> {{ $displayGrpName }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Global Alert when no settings exist at all -->
                <div id="noSettingsGlobalAlert" class="alert alert-light border text-center py-3 my-2 text-muted {{ $company->settings->count() === 0 ? '' : 'd-none' }}">
                    <i class="ft-info mr-1 text-primary"></i> No company settings found yet. Click <strong>Add Setting</strong> or <strong>Add Group</strong> to create one.
                </div>

                <!-- Grouped Settings Cards Container -->
                <div id="settingGroupsContainer">
                    @forelse($groupedSettings as $grpKey => $grpItems)
                        @php
                            $displayGrpName = ucwords(str_replace(['_', '-'], ' ', $grpKey));
                        @endphp
                        <div class="card border mb-3 setting-group-card" data-group="{{ $grpKey }}" style="border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                            <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center" style="background: #fdfdfe; border-bottom: 1px solid #edf2f7;">
                                <div class="d-flex align-items-center">
                                    <i class="ft-folder font-medium-2 mr-2 text-primary"></i>
                                    <h6 class="mb-0 font-weight-bold text-dark text-capitalize" style="font-size: 0.95rem;">
                                        {{ $displayGrpName }} Settings
                                    </h6>
                                    <span class="badge badge-light-primary border ml-2 px-2 py-1 group-badge-count" style="font-size: 0.75rem;">
                                        {{ $grpItems->count() }} {{ Str::plural('setting', $grpItems->count()) }}
                                    </span>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-xs btn-outline-primary btn-add-setting-to-group" data-group="{{ $grpKey }}">
                                        <i class="ft-plus mr-1"></i> Add to {{ $displayGrpName }}
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm m-0 table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 32%;">Key <span class="text-danger">*</span></th>
                                                <th style="width: 18%;">Type</th>
                                                <th style="width: 42%;">Value</th>
                                                <th style="width: 8%; text-align: center;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="group-settings-tbody" data-group="{{ $grpKey }}">
                                            @forelse($grpItems as $setting)
                                                @php $sIdx = $setting->id; @endphp
                                                <tr class="setting-row" data-id="{{ $setting->id }}" data-group="{{ $grpKey }}">
                                                    <td>
                                                        <input type="hidden" name="settings[{{ $sIdx }}][id]" class="setting-id-input" value="{{ $setting->id }}">
                                                        <input type="hidden" name="settings[{{ $sIdx }}][group]" class="setting-group-input" value="{{ $grpKey }}">
                                                        <div class="input-group input-group-sm">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text bg-light text-muted px-2" style="font-size: 0.75rem;"><i class="ft-hash"></i></span>
                                                            </div>
                                                            <input type="text" name="settings[{{ $sIdx }}][key]" value="{{ $setting->key }}" class="form-control form-control-sm setting-key-input font-weight-bold" style="font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; background: #f8fafc; font-size: 0.82rem;" placeholder="e.g. stock_check" readonly />
                                                        </div>
                                                    </td>
                                                    <td class="align-middle">
                                                        <input type="hidden" name="settings[{{ $sIdx }}][type]" class="setting-type-input" value="{{ $setting->type }}">
                                                        @php
                                                            $badgeColor = $typeBadges[$setting->type] ?? 'badge-secondary';
                                                        @endphp
                                                        <span class="badge {{ $badgeColor }} text-uppercase px-2 py-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                                            {{ $setting->type }}
                                                        </span>
                                                    </td>
                                                    <td class="setting-value-cell align-middle">
                                                        @if($setting->type === 'boolean')
                                                            <select name="settings[{{ $sIdx }}][value]" class="form-control form-control-sm setting-value-input">
                                                                <option value="1" {{ in_array((string)$setting->value, ['1', 'true', 'yes'], true) ? 'selected' : '' }}>True (Enabled)</option>
                                                                <option value="0" {{ in_array((string)$setting->value, ['0', 'false', 'no'], true) ? 'selected' : '' }}>False (Disabled)</option>
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
                                                            <input type="datetime-local" name="settings[{{ $sIdx }}][value]" value="{{ $dtVal }}" class="form-control form-control-sm setting-value-input" />
                                                        @elseif($setting->type === 'integer')
                                                            <input type="number" step="1" name="settings[{{ $sIdx }}][value]" value="{{ $setting->value }}" class="form-control form-control-sm setting-value-input" placeholder="Integer value" />
                                                        @elseif($setting->type === 'float')
                                                            <input type="number" step="any" name="settings[{{ $sIdx }}][value]" value="{{ $setting->value }}" class="form-control form-control-sm setting-value-input" placeholder="Decimal value" />
                                                        @elseif($setting->type === 'json')
                                                            <input type="text" name="settings[{{ $sIdx }}][value]" value="{{ $setting->value }}" class="form-control form-control-sm setting-value-input" placeholder='{"key": "value"}' />
                                                        @else
                                                            <input type="text" name="settings[{{ $sIdx }}][value]" value="{{ $setting->value }}" class="form-control form-control-sm setting-value-input" placeholder="Value" />
                                                        @endif
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        {{-- <button type="button" class="btn btn-sm btn-outline-danger btn-delete-setting p-1" data-id="{{ $setting->id }}" title="Delete Setting">
                                                            <i class="ft-trash font-medium-1"></i>
                                                        </button> --}}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr class="no-group-settings-row">
                                                    <td colspan="4" class="text-center text-muted py-3">
                                                        <em>No settings in this group yet. Click <strong>Add to {{ $displayGrpName }}</strong> to create one.</em>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @empty
                    @endforelse
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

<style>
.group-filter-pills .btn-filter-group.active {
    background-color: #7367f0 !important;
    color: #fff !important;
}
.group-filter-pills .btn-filter-group:not(.active) {
    color: #5e5873;
}
.group-filter-pills .btn-filter-group:not(.active):hover {
    background-color: #eaecf0;
}
.setting-group-card {
    transition: all 0.2s ease-in-out;
}
</style>

<script>
$(document).ready(function() {
    var companyId = {{ $company->id }};
    var storeSettingUrl = "{{ route('company.settings.store', $company->id) }}";
    var baseUrlSetting = "{{ url('company/' . $company->id . '/settings') }}";

    // Unbind any previous event handlers to prevent duplicate execution when modal is reopened
    $(document).off('.companySettings');
    $('#ajaxSubmit').off('.companySettings');

    // Helper to render type-specific input field
    function renderValueInput(inputName, type, currentVal) {
        if (type === 'boolean') {
            var isTrue = (currentVal === '1' || currentVal === 'true' || currentVal === true);
            return `
                <select name="${inputName}" class="form-control form-control-sm setting-value-input">
                    <option value="1" ${isTrue ? 'selected' : ''}>True (Enabled)</option>
                    <option value="0" ${!isTrue ? 'selected' : ''}>False (Disabled)</option>
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
    $(document).on('change.companySettings', '.setting-type-select', function() {
        var $row = $(this).closest('tr');
        var selectedType = $(this).val();
        var $valCell = $row.find('.setting-value-cell');
        var inputName = $valCell.find('.setting-value-input').attr('name') || 'settings[' + Date.now() + '][value]';
        var currentVal = $valCell.find('.setting-value-input').val();

        if (selectedType !== 'boolean' && (currentVal === '1' || currentVal === '0')) {
            currentVal = '';
        }

        $valCell.html(renderValueInput(inputName, selectedType, currentVal));
    });

    // Helper: update all badge counts across pills and cards
    function updateGroupCounts() {
        var total = 0;
        $('.setting-group-card').each(function() {
            var grp = $(this).data('group');
            var count = $(this).find('tbody.group-settings-tbody tr.setting-row').length;
            $(this).find('.group-badge-count').text(count + (count === 1 ? ' setting' : ' settings'));
            $('.count-' + grp).text(count);
            total += count;
        });
        $('.count-all').text(total);

        if (total === 0) {
            $('#noSettingsGlobalAlert').removeClass('d-none');
        } else {
            $('#noSettingsGlobalAlert').addClass('d-none');
        }
    }

    // Filter by group pills
    $(document).on('click.companySettings', '.btn-filter-group', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.btn-filter-group').removeClass('active');
        $(this).addClass('active');

        var selectedGroup = $(this).data('group');
        if (selectedGroup === 'all') {
            $('.setting-group-card').show();
        } else {
            $('.setting-group-card').hide();
            $('.setting-group-card[data-group="' + selectedGroup + '"]').fadeIn(150);
        }
    });

    // Create a new setting row HTML
    function createNewSettingRowHtml(rowIdx, groupName) {
        var isPusher = (groupName || '').toLowerCase() === 'pusher';
        var listAttr = isPusher ? 'list="pusherSettingKeysList"' : '';
        var placeholder = isPusher ? 'e.g. PUSHER_APP_KEY' : 'e.g. setting_key';

        return `
            <tr class="setting-row new-setting-row" data-id="" data-group="${groupName}">
                <td>
                    <input type="hidden" name="settings[${rowIdx}][id]" class="setting-id-input" value="">
                    <input type="hidden" name="settings[${rowIdx}][group]" class="setting-group-input" value="${groupName}">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light text-muted px-2" style="font-size: 0.75rem;"><i class="ft-hash"></i></span>
                        </div>
                        <input type="text" name="settings[${rowIdx}][key]" value="" class="form-control form-control-sm setting-key-input font-weight-bold" style="font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.82rem;" placeholder="${placeholder}" ${listAttr} autocomplete="off" />
                    </div>
                </td>
                <td class="align-middle">
                    <select name="settings[${rowIdx}][type]" class="form-control form-control-sm setting-type-select">
                        <option value="string" selected>string</option>
                        <option value="boolean">boolean</option>
                        <option value="integer">integer</option>
                        <option value="float">float</option>
                        <option value="json">json</option>
                        <option value="datetime">datetime</option>
                    </select>
                </td>
                <td class="setting-value-cell align-middle">
                    <input type="text" name="settings[${rowIdx}][value]" value="" class="form-control form-control-sm setting-value-input" placeholder="Value" />
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-setting p-1" title="Remove Setting">
                        <i class="ft-trash font-medium-1"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    // Helper to format any group name dynamically
    function formatGroupName(name) {
        if (!name) return 'General';
        return name.replace(/[_-]/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
    }

    // Ensure a group card and pill exist in DOM
    function ensureGroupCardExists(groupName) {
        groupName = (groupName || 'general').trim().toLowerCase();
        if ($('.setting-group-card[data-group="' + groupName + '"]').length > 0) {
            return;
        }

        var displayName = formatGroupName(groupName);

        // Add filter pill
        var pillHtml = `
            <li class="nav-item">
                <a class="nav-link py-1 px-3 btn-filter-group font-weight-bold" href="javascript:void(0);" data-group="${groupName}" style="font-size: 0.85rem; border-radius: 4px;">
                    <i class="ft-folder mr-1 text-primary"></i> ${displayName}
                </a>
            </li>
        `;
        $('#settingGroupPills').append(pillHtml);

        // Add card
        var cardHtml = `
            <div class="card border mb-3 setting-group-card" data-group="${groupName}" style="border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center" style="background: #fdfdfe; border-bottom: 1px solid #edf2f7;">
                    <div class="d-flex align-items-center">
                        <i class="ft-folder font-medium-2 mr-2 text-primary"></i>
                        <h6 class="mb-0 font-weight-bold text-dark text-capitalize" style="font-size: 0.95rem;">
                            ${displayName} Settings
                        </h6>
                        <span class="badge badge-light-primary border ml-2 px-2 py-1 group-badge-count" style="font-size: 0.75rem;">
                            0 settings
                        </span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-xs btn-outline-primary btn-add-setting-to-group" data-group="${groupName}">
                            <i class="ft-plus mr-1"></i> Add to ${displayName}
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm m-0 table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 32%;">Key <span class="text-danger">*</span></th>
                                    <th style="width: 18%;">Type</th>
                                    <th style="width: 42%;">Value</th>
                                    <th style="width: 8%; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody class="group-settings-tbody" data-group="${groupName}">
                                <tr class="no-group-settings-row">
                                    <td colspan="4" class="text-center text-muted py-3">
                                        <em>No settings in this group yet. Click <strong>Add to ${displayName}</strong> to create one.</em>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        $('#settingGroupsContainer').append(cardHtml);
        $('#noSettingsGlobalAlert').addClass('d-none');
    }

    // Add row to specified group with click-debounce guard
    var isAddingSettingRow = false;
    function addSettingRowToGroup(groupName) {
        if (isAddingSettingRow) return;
        isAddingSettingRow = true;
        setTimeout(function() {
            isAddingSettingRow = false;
        }, 300);

        groupName = (groupName || 'general').trim().toLowerCase();
        ensureGroupCardExists(groupName);

        var $tbody = $('.group-settings-tbody[data-group="' + groupName + '"]');
        $tbody.find('.no-group-settings-row').remove();

        var rowIdx = 'new_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
        var $row = $(createNewSettingRowHtml(rowIdx, groupName));
        $tbody.append($row);

        setTimeout(function() {
            $row.find('.setting-key-input').focus();
        }, 50);
        updateGroupCounts();
    }

    // Add New Group button handler
    $(document).on('click.companySettings', '#btnAddNewGroup', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '<div class="d-flex align-items-center justify-content-center text-dark"><i class="ft-folder-plus text-primary mr-1" style="font-size: 1.3rem;"></i> <span style="font-size: 1.15rem; font-weight: 600;">New Settings Group</span></div>',
                html: `
                    <div class="text-left px-2 pt-1" style="text-align: left !important;">
                        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.88rem; text-align: left;">
                            Group Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="swalNewGroupName" class="form-control" placeholder="e.g. notifications, api, security, custom_portal" style="font-size: 0.9rem; padding: 0.5rem 0.75rem; border-radius: 4px;" autocomplete="off">
                        <small class="form-text text-muted mt-1 d-block" style="font-size: 0.78rem; text-align: left; line-height: 1.4;">
                            Enter a unique category name to group your company settings (e.g. <code>notifications</code>, <code>api</code>, <code>security</code>).
                        </small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="ft-check mr-1"></i> Create Group',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#7367f0',
                cancelButtonColor: '#82868b',
                focusConfirm: false,
                didOpen: () => {
                    const input = document.getElementById('swalNewGroupName');
                    if (input) {
                        input.focus();
                        input.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                e.stopPropagation();
                                Swal.clickConfirm();
                            }
                        });
                    }
                },
                preConfirm: () => {
                    const val = document.getElementById('swalNewGroupName').value;
                    if (!val || !val.trim()) {
                        Swal.showValidationMessage('Group name is required!');
                        return false;
                    }
                    return val.trim();
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    var groupName = result.value.trim().toLowerCase();
                    ensureGroupCardExists(groupName);
                    $('.btn-filter-group[data-group="' + groupName + '"]').trigger('click');
                    addSettingRowToGroup(groupName);
                }
            });
        } else {
            var groupName = prompt('Enter new group name (e.g. notifications, api, security):');
            if (groupName && groupName.trim()) {
                groupName = groupName.trim().toLowerCase();
                ensureGroupCardExists(groupName);
                $('.btn-filter-group[data-group="' + groupName + '"]').trigger('click');
                addSettingRowToGroup(groupName);
            }
        }
    });

    // Add Setting button handler (adds to currently active group or defaults)
    $(document).on('click.companySettings', '#btnAddNewSetting', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var activeGroup = $('.btn-filter-group.active').data('group');
        if (!activeGroup || activeGroup === 'all') {
            var $firstCard = $('.setting-group-card:visible').first();
            if (!$firstCard.length) {
                $firstCard = $('.setting-group-card').first();
            }
            if ($firstCard.length) {
                activeGroup = $firstCard.data('group');
            } else {
                activeGroup = 'general';
            }
        }
        addSettingRowToGroup(activeGroup);
    });

    // Add to specific group button handler
    $(document).on('click.companySettings', '.btn-add-setting-to-group', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var groupName = $(this).data('group');
        addSettingRowToGroup(groupName);
    });

    // Delete single setting
    $(document).on('click.companySettings', '.btn-delete-setting', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var $btn = $(this);
        var $row = $btn.closest('tr');
        var settingId = $btn.data('id') || $row.data('id') || $row.find('.setting-id-input').val();
        var $tbody = $row.closest('tbody');
        var groupName = $row.data('group') || $tbody.data('group') || 'general';

        function removeRowFromDom() {
            $row.fadeOut(200, function() {
                $(this).remove();
                if ($tbody.find('tr.setting-row').length === 0) {
                    var cap = formatGroupName(groupName);
                    $tbody.html(`
                        <tr class="no-group-settings-row">
                            <td colspan="4" class="text-center text-muted py-3">
                                <em>No settings in this group yet. Click <strong>Add to ${cap}</strong> to create one.</em>
                            </td>
                        </tr>
                    `);
                }
                updateGroupCounts();
            });
        }

        if (!settingId) {
            removeRowFromDom();
            return;
        }

        var confirmDelete = function(onConfirmed) {
            if (typeof Swal !== 'undefined') {
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
                        onConfirmed();
                    }
                });
            } else {
                if (confirm('Are you sure you want to delete this setting?')) {
                    onConfirmed();
                }
            }
        };

        confirmDelete(function() {
            var deleteUrl = baseUrlSetting + '/' + settingId;
            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(resp) {
                    removeRowFromDom();
                    if (typeof toastr !== 'undefined') {
                        toastr.success(resp.success || 'Setting deleted successfully.');
                    } else if (typeof Swal !== 'undefined') {
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
                    // Track for bulk deletion on form submit
                    var currentDeleted = $('#deletedSettingsInput').val();
                    var arr = currentDeleted ? currentDeleted.split(',') : [];
                    arr.push(settingId);
                    $('#deletedSettingsInput').val(arr.join(','));
                    removeRowFromDom();
                }
            });
        });
    });

    // Clear validation highlighting & auto-detect type on key input
    $(document).on('input.companySettings change.companySettings', '.setting-key-input', function() {
        var $input = $(this);
        $input.removeClass('is-invalid');

        var keyVal = ($input.val() || '').trim().toUpperCase();
        var $row = $input.closest('tr');
        var group = ($row.data('group') || '').toLowerCase();

        if (group === 'pusher') {
            if (keyVal === 'PUSHER_STATUS' || keyVal === 'STATUS') {
                var $typeSelect = $row.find('.setting-type-select');
                if ($typeSelect.length && $typeSelect.val() !== 'boolean') {
                    $typeSelect.val('boolean').trigger('change');
                }
            } else if (keyVal === 'PUSHER_APP_CLUSTER' || keyVal === 'CLUSTER') {
                var $valInput = $row.find('.setting-value-input');
                if ($valInput.length && !$valInput.val()) {
                    $valInput.attr('placeholder', 'ap2');
                }
            }
        }
    });

    // Form submission validation & cleanup of empty new rows
    $('#ajaxSubmit').on('submit.companySettings', function(e) {
        var hasError = false;
        $('.new-setting-row').each(function() {
            var $row = $(this);
            var keyVal = ($row.find('.setting-key-input').val() || '').trim();
            var valVal = ($row.find('.setting-value-input').val() || '').trim();

            if (!keyVal && !valVal) {
                // Completely empty row, discard before submit
                $row.remove();
            } else if (!keyVal) {
                hasError = true;
                var grp = $row.data('group');
                $('.btn-filter-group[data-group="' + grp + '"]').trigger('click');
                $row.find('.setting-key-input').addClass('is-invalid').focus();

                if (typeof toastr !== 'undefined') {
                    toastr.error('Please enter a key for all settings or delete empty rows.');
                } else if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Key Required',
                        text: 'Please enter a key for the new setting or delete the row.',
                        confirmButtonColor: '#7367f0'
                    });
                }
                return false;
            }
        });

        if (hasError) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
    });

    // Initial group count update
    updateGroupCounts();
});
</script>

