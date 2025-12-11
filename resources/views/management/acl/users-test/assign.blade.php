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
                    <select name="company[]" class="form-control company-select">
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}
                            </option>
                        @endforeach
                    </select>
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
                                function renderPermissionItems($items, $permission, $level = 0)
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
                                            '">
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
                                            renderPermissionItems($children, $permission, $level + 1);
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
                                                    data-level="0">
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
                                            @php renderPermissionItems($rootChildren, $permission, 1); @endphp
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

<script>
    $(document).ready(function() {

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


        initSelect2();

        $(document).on('change', '.company-select', function() {
            let card = $(this).closest('.clonecard');
            let companyId = $(this).val();

            let locationSelect = card.find('.location-select');
            let arrivalWrapper = card.find('.arrival-wrapper');

            if (!companyId) {
                locationSelect.html('');
                arrivalWrapper.html('<small class="text-muted">Select a company first</small>');
                return;
            }

            // Load locations based on selected company
            $.get(`/acl/get-company-locations/${companyId}`, function(data) {

                locationSelect.html('');
                arrivalWrapper.html(
                    '<small class="text-muted">Select a location to load arrival options</small>'
                );

                data.forEach(loc => {
                    locationSelect.append(
                        `<option value="${loc.id}">${loc.name}</option>`
                    );
                });

                // locationSelect.trigger('change');
                // initSelect2();
            });
        });

        setTimeout(function() {
            $('#card-container .clonecard').each(function() {
                let card = $(this);
                let companyId = card.find('.company-select').val();

                if (companyId) {
                    card.find('.company-select').trigger('change');
                }
            });
        }, 300);

        // $(document).on('change', '.location-select', function() {

        //     let card = $(this).closest('.clonecard');
        //     let selectedLocations = $(this).val();
        //     let wrapper = card.find('.arrival-wrapper');

        //     wrapper.html('');

        //     if (!selectedLocations || selectedLocations.length === 0) {
        //         wrapper.html(
        //             '<small class="text-muted">Select a location to load arrival options</small>');
        //         return;
        //     }

        //     selectedLocations.forEach(locationId => {

        //         let arrivalBox = $(`
        //     <div class="single-arrival mb-2">
        //         <label class="font-weight-bold">Arrival for Location</label>
        //         <select class="form-control arrival-select" name="arrival_location_id[${locationId}][]" multiple>
        //             <option>Loading...</option>
        //         </select>
        //     </div>
        // `);

        //         wrapper.append(arrivalBox);

        //         let selectField = arrivalBox.find('select');

        //         $.get(`/acl/get-arrival-locations/${locationId}`, function(data) {
        //             selectField.empty();

        //             data.forEach(item => {
        //                 selectField.append(
        //                     `<option value="${item.id}">${item.name}</option>`
        //                 );
        //             });

        //             selectField.select2({
        //                 width: '100%'
        //             });
        //         });
        //     });

        // });

        $(document).on('change', '.location-select', function() {

            let card = $(this).closest('.clonecard');
            let selectedLocations = $(this).val();
            let wrapper = card.find('.arrival-wrapper');

            wrapper.html('');

            if (!selectedLocations || selectedLocations.length === 0) {
                wrapper.html(
                    '<small class="text-muted">Select a location to load arrival options</small>');
                return;
            }

            // Create ONE single arrival dropdown
            let arrivalBox = $(`
        <div class="single-arrival mb-2">
            <label class="font-weight-bold">Arrival Locations</label>
            <select class="form-control arrival-select" name="arrival_location_id[]" multiple>
                <option>Loading...</option>
            </select>
        </div>
    `);

            wrapper.append(arrivalBox);
            let selectField = arrivalBox.find('select');

            selectField.empty();

            // Now fetch arrival locations for each selected location
            selectedLocations.forEach(locationId => {

                $.get(`/acl/get-arrival-locations/${locationId}`, function(data) {

                    data.forEach(item => {
                        selectField.append(
                            `<option value="${item.id}">${item.name} (${item.location_name})</option>`
                        );
                    });

                    selectField.select2({
                        width: '100%'
                    });

                });

            });

        });



        function toggleRemoveButton() {
            if ($('#card-container .clonecard').length === 1) {
                $('#card-container .clonecard .remove-card').hide();
            } else {
                $('#card-container .clonecard .remove-card').show();
            }
        }

        toggleRemoveButton();

        $('body').off('click', '.add-more').on('click', '.add-more', function() {

            let newCard = $('#card-container .clonecard:first').clone();

            // Remove old Select2 wrappers
            newCard.find('.select2').remove();

            // Reset selects completely
            newCard.find('select').each(function() {
                $(this)
                    .val('')
                    .removeClass('select2-hidden-accessible')
                    .removeAttr('data-select2-id');
            });

            // Reset Arrival section
            newCard.find('.arrival-wrapper')
                .html('<small class="text-muted">Select a location to load arrival options</small>');

            let totalCards = $('#card-container .clonecard').length;
            newCard.find('.location-select').attr('name', `company_location_id[${totalCards}][]`);
            newCard.find('.role-select').attr('name', `role[${totalCards}]`);
            newCard.find('.company-select').attr('name', `company[${totalCards}]`);

            // Append new card
            $('#card-container').append(newCard);

            initSelect2();

            toggleRemoveButton();
        });

        $('body').off('click', '.remove-card').on('click', '.remove-card', function() {
            if ($('#card-container .clonecard').length > 1) {
                $(this).closest('.clonecard').remove();
                toggleRemoveButton();
            }
        });
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
