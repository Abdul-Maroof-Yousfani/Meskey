{!! Form::open(['route' => 'roles.store', 'method' => 'POST', 'id' => 'ajaxSubmit']) !!}
<input type="hidden" id="listRefresh" value="{{ route('get.roles') }}" />

<div class="perm_row perm_form-mar">
    <div class="perm_col-xs-12 perm_col-sm-12 perm_col-md-12">
        <div class="perm_form-group">
            <label class="perm_form-label">Name:</label>
            {!! Form::text('name', null, ['placeholder' => 'Name', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="perm_col-xs-12 perm_col-sm-12 perm_col-md-12">
        <div class="perm_form-group perm_errorappend">
            <label class="perm_form-label">Description:</label>
            {!! Form::textarea('description', null, [
                'placeholder' => 'Description',
                'class' => 'form-control',
                'rows' => 3,
            ]) !!}
        </div>
    </div>
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
                <input type="text" id="searchPermissions" placeholder="Search permissions..." class="form-control">
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
                            echo '<div class="perm_permission-header" onclick="togglePermission(\'' . $itemId . '\')">';
                            echo '<div class="perm_permission-title">';
                            echo '<label class="perm_custom-checkbox">';
                            echo '<input type="checkbox" name="permission[]" value="' .
                                $item->name .
                                '" class="perm_checkbox" ';
                            echo 'id="' .
                                $itemId .
                                '" data-level="' .
                                $level .
                                '" data-parent="' .
                                ($level > 0 ? 'item-' . $item->parent_id : '') .
                                '">';
                            echo '<span class="perm_checkmark"></span>';
                            echo '<span class="perm_permission-name">' . $item->name . '</span>';
                            echo '</label>';
                            echo '</div>';

                            if ($hasChildren) {
                                echo '<div class="perm_permission-meta">';
                                echo '<span class="perm_permission-count">' . $children->count() . '</span>';
                                echo '<div class="perm_toggle-icon">';
                                echo '<i class="perm_toggle-arrow"></i>';
                                echo '</div>';
                                echo '</div>';
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
                    <div class="perm_permission-card">
                        @php
                            $rootChildren = $permission->where('parent_id', $rootPermission->id);
                            $hasRootChildren = $rootChildren->count() > 0;
                            $rootId = 'item-' . $rootPermission->id;
                        @endphp

                        <div class="perm_permission-header" onclick="togglePermission('{{ $rootId }}')">
                            <div class="perm_permission-title">
                                <label class="perm_custom-checkbox">
                                    <input type="checkbox" name="permission[]" value="{{ $rootPermission->name }}"
                                        class="perm_checkbox perm_root-checkbox" id="{{ $rootId }}"
                                        data-level="0">
                                    <span class="perm_checkmark"></span>
                                    <span class="perm_permission-name">{{ $rootPermission->name }}</span>
                                </label>
                            </div>
                            @if ($hasRootChildren)
                                <div class="perm_permission-meta">
                                    <span class="perm_permission-count">{{ $rootChildren->count() }}</span>
                                    <div class="perm_toggle-icon">
                                        <i class="perm_toggle-arrow"></i>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($hasRootChildren)
                            <div class="perm_permission-children" id="{{ $rootId }}-children">
                                @php
                                    renderPermissionItems($rootChildren, $permission, 1);
                                @endphp
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="perm_row perm_bottom-button-bar">
    <div class="perm_col-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
        <button type="submit" class="btn btn-primary submitbutton">Save</button>
    </div>
</div>
{!! Form::close() !!}

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
