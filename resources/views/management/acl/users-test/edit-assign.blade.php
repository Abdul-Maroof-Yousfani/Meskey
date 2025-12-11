{!! Form::open([
    'route' => ['users-test.assign.save', $user->id],
    'method' => 'POST',
    'id' => 'ajaxSubmit',
]) !!}
<input type="hidden" id="url" value="{{ route('users-test.index') }}" />

<div class="row">
    <div class="col-12 mb-2">
        <h6 class="header-heading-sepration">
            Company Detail
        </h6>
    </div>

    <div id="card-container" class="w-100 overflow-hidden p-3" style="padding-top: 0px !important;">
        <div class="clonecard border-1">
            <div class="row justify-content-center">

                <!-- Company -->
                <div class="col-md-12">
                    <label>Company:</label>
                    <select name="company[]" class="form-control company-select" disabled>
                        @foreach ($companies as $company)
                            @if ($company->id == $companyId)
                                <option value="{{ $company->id }}" selected>
                                    {{ $company->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <!-- hidden field so value is still submitted -->
                    <input type="hidden" name="company[0]" value="{{ $companyId }}">
                </div>


                <!-- Location -->
                <div class="col-md-12 mt-3">
                    <label>Location:</label>
                    <select name="company_location_id[0][]" class="form-control location-select" multiple>
                    </select>
                </div>

                <!-- Arrival -->
                <div class="col-md-12 mt-3">
                    <label>Arrival Location:</label>

                    <div class="arrival-wrapper">
                        <small class="text-muted">Select a location to load arrival
                            options</small>
                    </div>
                </div>
            </div>


            <div class="perm_row perm_form-mar">
                <div class="perm_col-xs-12 perm_col-sm-12 perm_col-md-12">
                    <div class="perm_form-group">
                        <div class="perm_permissions-header">
                            <label class="perm_form-label">Permissions:</label>
                            <div class="perm_permissions-actions">
                                <button type="button" class="perm_btn-action" id="expandAll">Expand All</button>
                                <button type="button" class="perm_btn-action" id="collapseAll">Collapse All</button>
                            </div>
                        </div>

                        <div class="perm_permissions-search">
                            <input type="text" id="searchPermissions" placeholder="Search permissions..."
                                class="form-control">
                        </div>

                        <div class="perm_permissions-tree">
                            @php
                                function renderPermissionItems($items, $permission, $assignedPermissions, $level = 0)
                                {
                                    foreach ($items as $item) {
                                        $children = $permission->where('parent_id', $item->id);
                                        $hasChildren = $children->count() > 0;
                                        $itemClass = 'perm_permission-item perm_level-' . $level;
                                        $itemId = 'item-' . $item->id;

                                        echo '<div class="' . $itemClass . '">';
                                        echo '<div class="perm_permission-header" onclick="togglePermission(\'' .
                                            $itemId .
                                            '\')">';

                                        echo '<div class="perm_permission-title">
                                    <label class="perm_custom-checkbox">
                                        <input type="checkbox" name="permission[]" value="' .
                                            $item->name .
                                            '" 
                                               class="perm_checkbox" id="' .
                                            $itemId .
                                            '" 
                                               data-level="' .
                                            $level .
                                            '" 
                                               data-parent="' .
                                            ($level > 0 ? 'item-' . $item->parent_id : '') .
                                            '"
                                            ' .
                                            (in_array($item->name, $assignedPermissions) ? 'checked' : '') .
                                            '>
                                        <span class="perm_checkmark"></span>
                                        <span class="perm_permission-name">' .
                                            $item->name .
                                            '</span>
                                    </label>
                                </div>';

                                        if ($hasChildren) {
                                            echo '<div class="perm_permission-meta">
                                        <span class="perm_permission-count">' .
                                                $children->count() .
                                                '</span>
                                        <div class="perm_toggle-icon"><i class="perm_toggle-arrow"></i></div>
                                      </div>';
                                        }

                                        echo '</div>';

                                        if ($hasChildren) {
                                            echo '<div class="perm_permission-children" id="' . $itemId . '-children">';
                                            renderPermissionItems(
                                                $children,
                                                $permission,
                                                $assignedPermissions,
                                                $level + 1,
                                            );
                                            echo '</div>';
                                        }

                                        echo '</div>';
                                    }
                                }
                            @endphp

                            @php
                                $rootPermissions = $permission->where('parent_id', null);
                            @endphp

                            @foreach ($rootPermissions as $rootPermission)
                                @php
                                    $rootChildren = $permission->where('parent_id', $rootPermission->id);
                                    $hasRootChildren = $rootChildren->count() > 0;
                                    $rootId = 'item-' . $rootPermission->id;
                                @endphp

                                <div class="perm_permission-card">
                                    <div class="perm_permission-header"
                                        onclick="togglePermission('{{ $rootId }}')">
                                        <div class="perm_permission-title">
                                            <label class="perm_custom-checkbox">
                                                <input type="checkbox" name="permission[]"
                                                    value="{{ $rootPermission->name }}"
                                                    class="perm_checkbox perm_root-checkbox" id="{{ $rootId }}"
                                                    data-level="0"
                                                    {{ in_array($rootPermission->name, $assignedPermissions) ? 'checked' : '' }}>
                                                <span class="perm_checkmark"></span>
                                                <span class="perm_permission-name">{{ $rootPermission->name }}</span>
                                            </label>
                                        </div>

                                        @if ($hasRootChildren)
                                            <div class="perm_permission-meta">
                                                <span class="perm_permission-count">{{ $rootChildren->count() }}</span>
                                                <div class="perm_toggle-icon"><i class="perm_toggle-arrow"></i></div>
                                            </div>
                                        @endif
                                    </div>

                                    @if ($hasRootChildren)
                                        <div class="perm_permission-children" id="{{ $rootId }}-children">
                                            @php renderPermissionItems($rootChildren, $permission, $assignedPermissions, 1); @endphp
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>

<div class="row bottom-button-bar mb-4">
    <div class="col-12 mb-3">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
        <button type="submit" class="btn btn-primary submitbutton">Save</button>
    </div>
</div>

{!! Form::close() !!}

{{-- <script>
    let selectedArrivals = {!! json_encode($selectedArrivals ?? []) !!};
    let selectedLocations = {!! json_encode($selectedLocations ?? []) !!};
    let editCompanyId = {{ $companyId ?? 'null' }};

    $(document).ready(function() {

        /* ------------------ Select2 Init ------------------ */
        function initSelect2() {
            try {
                $('.company-select, .location-select, .arrival-select').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
            } catch (e) {}

            $('.company-select').select2({
                width: '100%'
            });
            $('.location-select').select2({
                width: '100%'
            });
            $('.arrival-select').select2({
                width: '100%'
            });
        }

        /* ------------------ Reindex Cards ------------------ */
        function reindexCards() {
            $('#card-container .clonecard').each(function(index) {
                $(this).find('.company-select').attr('name', `company[${index}]`);
                $(this).find('.location-select').attr('name', `company_location_id[${index}][]`);
            });
        }

        /* ------------------ Load Company Locations ------------------ */
        function loadCompanyLocations(card, companyId) {

            let locationSelect = card.find('.location-select');
            let wrapper = card.find('.arrival-wrapper');

            locationSelect.empty();
            wrapper.html('<small class="text-muted">Select a location to load arrival options</small>');

            if (!companyId) return;

            $.get(`/acl/get-company-locations/${companyId}`, function(data) {

                data.forEach(loc => {

                    let isSelected =
                        selectedLocations.includes(String(loc.id)) ||
                        selectedLocations.includes(Number(loc.id)) ?
                        'selected' :
                        '';

                    locationSelect.append(
                        `<option value="${loc.id}" ${isSelected}>${loc.name}</option>`
                    );
                });

                locationSelect.trigger('change');
                initSelect2();
            });
        }

        /* ------------------ Load Arrival Locations ------------------ */
        function populateArrivalSelect(card, locationId) {

            let arrivalBox = card.find(`.single-arrival[data-location="${locationId}"]`);
            if (!arrivalBox.length) return;

            let selectField = arrivalBox.find('select.arrival-select');

            $.get(`/acl/get-arrival-locations/${locationId}`, function(data) {

                selectField.empty();

                data.forEach(item => {

                    let isSelected =
                        selectedArrivals.includes(String(item.id)) ||
                        selectedArrivals.includes(Number(item.id)) ?
                        'selected' :
                        '';

                    selectField.append(
                        `<option value="${item.id}" ${isSelected}>${item.name}</option>`
                    );
                });

                if (selectField.hasClass('select2-hidden-accessible')) {
                    selectField.select2('destroy');
                }

                selectField.select2({
                    width: '100%'
                });
            });
        }

        /* ------------------ Build Arrival UI ------------------ */
        function buildArrivalWrapperForCard(card) {

            let selected = card.find('.location-select').val() || [];
            let wrapper = card.find('.arrival-wrapper');

            wrapper.html('');

            if (!selected.length) {
                wrapper.html('<small class="text-muted">Select a location to load arrival options</small>');
                return;
            }

            selected.forEach(function(locationId) {

                let arrivalBox = $(`
                    <div class="single-arrival mb-2" data-location="${locationId}">
                        <label class="font-weight-bold">Arrival for Location</label>
                        <select name="arrival_location_id[${locationId}][]" class="form-control arrival-select" multiple></select>
                    </div>
                `);

                wrapper.append(arrivalBox);

                populateArrivalSelect(card, locationId);
            });
        }

        /* ------------------ Change Events ------------------ */

        // Company change
        $(document).on('change', '.company-select', function() {
            let card = $(this).closest('.clonecard');
            let companyId = $(this).val();
            loadCompanyLocations(card, companyId);
        });

        // Location change
        $(document).on('change', '.location-select', function() {
            let card = $(this).closest('.clonecard');
            buildArrivalWrapperForCard(card);
        });

        /* ------------------ Add / Remove Card ------------------ */

        $('body').on('click', '.add-more', function() {

            let newCard = $('#card-container .clonecard:first').clone();

            newCard.find('.select2').remove();
            newCard.find('select').each(function() {
                $(this)
                    .val(null)
                    .removeClass('select2-hidden-accessible')
                    .removeAttr('data-select2-id');
            });

            newCard.find('.arrival-wrapper')
                .html('<small class="text-muted">Select a location to load arrival options</small>');

            $('#card-container').append(newCard);

            reindexCards();
            initSelect2();
        });

        $('body').on('click', '.remove-card', function() {
            if ($('#card-container .clonecard').length > 1) {
                $(this).closest('.clonecard').remove();
                reindexCards();
            }
        });

        /* ------------------ Initial Load (Edit + Normal) ------------------ */

        setTimeout(function() {

            $('#card-container .clonecard').each(function(index) {

                let card = $(this);
                let companyId = editCompanyId ?? card.find('.company-select').val();

                if (companyId) {
                    loadCompanyLocations(card, companyId);

                    setTimeout(function() {
                        buildArrivalWrapperForCard(card);
                    }, 200);
                }
            });

        }, 300);

        // Init Select2 on load
        initSelect2();

    });
</script> --}}

<script>
    let selectedArrivals = {!! json_encode($selectedArrivals ?? []) !!};
    let selectedLocations = {!! json_encode($selectedLocations ?? []) !!};
    let editCompanyId = {{ $companyId ?? 'null' }};

    $(document).ready(function() {

        function initSelect2() {
            try {
                $('.company-select, .location-select, .arrival-select').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
            } catch (e) {}
            $('.company-select, .location-select, .arrival-select').select2({
                width: '100%'
            });
        }

        function reindexCards() {
            $('#card-container .clonecard').each(function(index) {
                $(this).find('.company-select').attr('name', `company[${index}]`);
                $(this).find('.location-select').attr('name', `company_location_id[${index}][]`);
            });
        }

        function loadCompanyLocations(card, companyId) {
            let locationSelect = card.find('.location-select');
            let wrapper = card.find('.arrival-wrapper');

            locationSelect.empty();
            wrapper.html('<small class="text-muted">Select a location to load arrival options</small>');

            if (!companyId) return;

            $.get(`/acl/get-company-locations/${companyId}`, function(data) {
                data.forEach(loc => {
                    let isSelected = selectedLocations.includes(String(loc.id)) ||
                        selectedLocations.includes(Number(loc.id)) ? 'selected' : '';
                    locationSelect.append(
                        `<option value="${loc.id}" ${isSelected}>${loc.name}</option>`);
                });
                locationSelect.trigger('change');
                initSelect2();
            });
        }

        function buildArrivalWrapperForCard(card) {
            let selected = card.find('.location-select').val() || [];
            let wrapper = card.find('.arrival-wrapper');
            wrapper.html('');

            if (!selected.length) {
                wrapper.html('<small class="text-muted">Select a location to load arrival options</small>');
                return;
            }

            // SINGLE dropdown for ALL selected locations
            let arrivalBox = $(`
            <div class="single-arrival mb-2">
                <label class="font-weight-bold">Arrival Locations</label>
                <select class="form-control arrival-select" name="arrival_location_id[]" multiple></select>
            </div>
        `);

            wrapper.append(arrivalBox);
            let selectField = arrivalBox.find('select');
            selectField.empty();

            selected.forEach(locationId => {
                $.get(`/acl/get-arrival-locations/${locationId}`, function(data) {
                    data.forEach(item => {
                        let isSelected = selectedArrivals.includes(String(item.id)) ||
                            selectedArrivals.includes(Number(item.id)) ? 'selected' :
                            '';
                        selectField.append(
                            `<option value="${item.id}" ${isSelected}>${item.name} (${item.location_name})</option>`
                        );
                    });
                    if (selectField.hasClass('select2-hidden-accessible')) {
                        selectField.select2('destroy');
                    }
                    selectField.select2({
                        width: '100%'
                    });
                });
            });
        }

        // Company change
        $(document).on('change', '.company-select', function() {
            let card = $(this).closest('.clonecard');
            let companyId = $(this).val();
            loadCompanyLocations(card, companyId);
        });

        // Location change
        $(document).on('change', '.location-select', function() {
            let card = $(this).closest('.clonecard');
            buildArrivalWrapperForCard(card);
        });

        // Add / Remove card
        $('body').on('click', '.add-more', function() {
            let newCard = $('#card-container .clonecard:first').clone();
            newCard.find('.select2').remove();
            newCard.find('select').val(null).removeClass('select2-hidden-accessible').removeAttr(
                'data-select2-id');
            newCard.find('.arrival-wrapper').html(
                '<small class="text-muted">Select a location to load arrival options</small>');
            $('#card-container').append(newCard);
            reindexCards();
            initSelect2();
        });

        $('body').on('click', '.remove-card', function() {
            if ($('#card-container .clonecard').length > 1) {
                $(this).closest('.clonecard').remove();
                reindexCards();
            }
        });

        // Initial load for edit
        // setTimeout(function() {
        //     $('#card-container .clonecard').each(function() {
        //         let card = $(this);
        //         let companyId = editCompanyId ?? card.find('.company-select').val();
        //         if (companyId) {
        //             loadCompanyLocations(card, companyId);
        //             setTimeout(function() {
        //                 card.find('.location-select').trigger('change');
        //             }, 200);
        //         }
        //     });
        // }, 300);

        $('#card-container .clonecard').each(function() {
            let card = $(this);
            let companyId = card.find('.company-select').val();

            if (companyId) {
                loadCompanyLocations(card, companyId);
            }
        });


        initSelect2();
    });
</script>


{{-- permissions script --}}
<script>
    function togglePermission(id) {
        event.stopPropagation();

        if (event.target.type === 'checkbox' || $(event.target).closest('label').length > 0) {
            return;
        }

        $('#' + id + '-children').slideToggle(200);
        $(event.currentTarget).find('.perm_toggle-arrow').toggleClass('perm_rotated');
    }

    $(document).ready(function() {
        $('.perm_custom-checkbox').on('click', function(e) {
            e.stopPropagation();
        });

        $('.perm_checkbox').change(function() {
            var isChecked = $(this).prop('checked');
            var itemId = $(this).attr('id');

            $('#' + itemId + '-children').find('.perm_checkbox').prop('checked', isChecked);

            updateParentState($(this));
        });

        function updateParentState($checkbox) {
            var parentId = $checkbox.data('parent');

            if (parentId) {
                var $parent = $('#' + parentId);
                var $siblings = $('[data-parent="' + parentId + '"]');
                var anyChecked = false;

                $siblings.each(function() {
                    if ($(this).prop('checked')) {
                        anyChecked = true;
                        return false;
                    }
                });

                $parent.prop('checked', anyChecked);

                updateParentState($parent);
            }
        }

        $('#expandAll').click(function() {
            $('.perm_permission-children').slideDown(200).addClass('perm_show');
            $('.perm_toggle-arrow').addClass('perm_rotated');
        });

        $('#collapseAll').click(function() {
            $('.perm_permission-children').slideUp(200).removeClass('perm_show');
            $('.perm_toggle-arrow').removeClass('perm_rotated');
        });

        $('#searchPermissions').on('keyup', function() {
            var searchText = $(this).val().toLowerCase();

            if (searchText.length > 0) {
                $('.perm_permission-children').slideDown(200).addClass('perm_show');
                $('.perm_toggle-arrow').addClass('perm_rotated');

                $('.perm_permission-name').each(function() {
                    var permissionText = $(this).text().toLowerCase();
                    var permissionItem = $(this).closest(
                        '.perm_permission-card, .perm_permission-item');

                    if (permissionText.indexOf(searchText) > -1) {
                        permissionItem.show();
                        $(this).html(highlightText($(this).text(), searchText));

                        permissionItem.parents('.perm_permission-card, .perm_permission-item')
                            .show();
                    } else {
                        var hasMatchingChildren = permissionItem.find('.perm_permission-name')
                            .text().toLowerCase().indexOf(searchText) > -1;

                        if (hasMatchingChildren) {
                            permissionItem.show();
                        } else {
                            permissionItem.hide();
                        }
                    }
                });
            } else {
                $('.perm_permission-card, .perm_permission-item').show();
                $('.perm_permission-name').each(function() {
                    $(this).html($(this).text());
                });
            }
        });

        function highlightText(text, searchText) {
            if (!searchText) return text;

            var lowerText = text.toLowerCase();
            var lowerSearchText = searchText.toLowerCase();
            var index = lowerText.indexOf(lowerSearchText);

            if (index >= 0) {
                return text.substring(0, index) +
                    '<span class="perm_highlight">' +
                    text.substring(index, index + searchText.length) +
                    '</span>' +
                    text.substring(index + searchText.length);
            }

            return text;
        }
    });
</script>
