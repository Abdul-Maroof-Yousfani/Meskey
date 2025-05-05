{!! Form::open(['route' => 'roles.store', 'method' => 'POST', 'id' => 'ajaxSubmit']) !!}
<input type="hidden" id="listRefresh" value="{{ route('get.roles') }}" />

<div class="row form-mar">
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label class="form-label">Name:</label>
            {!! Form::text('name', null, ['placeholder' => 'Name', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group errorappend">
            <label class="form-label">Description:</label>
            {!! Form::textarea('description', null, [
                'placeholder' => 'Description',
                'class' => 'form-control',
                'rows' => 3,
            ]) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <div class="permissions-header">
                <label class="form-label">Permissions:</label>
                <div class="permissions-actions">
                    <button type="button" class="btn-action" id="expandAll">Expand All</button>
                    <button type="button" class="btn-action" id="collapseAll">Collapse All</button>
                </div>
            </div>
            <div class="permissions-search">
                <input type="text" id="searchPermissions" placeholder="Search permissions..." class="form-control">
            </div>
            <div class="permissions-tree">
                @foreach ($permission as $parent)
                    @if ($parent->parent_id === null)
                        <div class="permission-card">
                            <div class="permission-header" onclick="togglePermission('parent-{{ $parent->id }}')">
                                <div class="permission-title">
                                    <label class="custom-checkbox">
                                        <input type="checkbox" name="permission[]" value="{{ $parent->name }}"
                                            class="parent-checkbox" id="parent-{{ $parent->id }}">
                                        <span class="checkmark"></span>
                                        <span class="permission-name">{{ $parent->name }}</span>
                                    </label>
                                </div>
                                @if ($permission->where('parent_id', $parent->id)->count() > 0)
                                    <div class="permission-meta">
                                        <span
                                            class="permission-count">{{ $permission->where('parent_id', $parent->id)->count() }}</span>
                                        <div class="toggle-icon">
                                            <i class="toggle-arrow"></i>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="permission-children" id="parent-{{ $parent->id }}-children">
                                @foreach ($permission->where('parent_id', $parent->id) as $child)
                                    <div class="child-permission">
                                        <div class="child-header"
                                            onclick="togglePermission('child-{{ $child->id }}')">
                                            <div class="permission-title">
                                                <label class="custom-checkbox">
                                                    <input type="checkbox" name="permission[]"
                                                        value="{{ $child->name }}" class="child-checkbox"
                                                        id="child-{{ $child->id }}"
                                                        data-parent="parent-{{ $parent->id }}">
                                                    <span class="checkmark"></span>
                                                    <span class="permission-name">{{ $child->name }}</span>
                                                </label>
                                            </div>
                                            @if ($permission->where('parent_id', $child->id)->count() > 0)
                                                <div class="permission-meta">
                                                    <span
                                                        class="permission-count">{{ $permission->where('parent_id', $child->id)->count() }}</span>
                                                    <div class="toggle-icon">
                                                        <i class="toggle-arrow"></i>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="grandchild-permissions" id="child-{{ $child->id }}-children">
                                            @foreach ($permission->where('parent_id', $child->id) as $grandchild)
                                                <div class="grandchild-permission">
                                                    <label class="custom-checkbox">
                                                        <input type="checkbox" name="permission[]"
                                                            value="{{ $grandchild->name }}" class="grandchild-checkbox"
                                                            id="grandchild-{{ $grandchild->id }}"
                                                            data-parent="parent-{{ $parent->id }}"
                                                            data-child="child-{{ $child->id }}">
                                                        <span class="checkmark"></span>
                                                        <span class="permission-name">{{ $grandchild->name }}</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
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
{!! Form::close() !!}

<script>
    function togglePermission(id) {
        event.stopPropagation();

        if (event.target.type === 'checkbox' || $(event.target).closest('label').length > 0) {
            return;
        }

        $('#' + id + '-children').slideToggle(200);

        $(event.currentTarget).find('.toggle-arrow').toggleClass('rotated');
    }

    $(document).ready(function() {
        $('.custom-checkbox').on('click', function(e) {
            e.stopPropagation();
        });

        $('.parent-checkbox').change(function() {
            var isChecked = $(this).prop('checked');
            var parentId = $(this).attr('id');

            $('[data-parent="' + parentId + '"]').prop('checked', isChecked);
        });

        $('.child-checkbox').change(function() {
            var isChecked = $(this).prop('checked');
            var childId = $(this).attr('id');

            $('[data-child="' + childId + '"]').prop('checked', isChecked);

            updateParentState($(this));
        });

        $('.grandchild-checkbox').change(function() {
            updateParentState($(this));
        });

        function updateParentState($checkbox) {
            var childId = $checkbox.data('child');
            if (childId) {
                var anyGrandchildChecked = $('[data-child="' + childId + '"]:checked').length > 0;
                $('#' + childId).prop('checked', anyGrandchildChecked);
            }

            var parentId = $checkbox.data('parent');
            if (parentId) {
                var anyChildChecked = $('[data-parent="' + parentId + '"]:checked').length > 0;
                $('#' + parentId).prop('checked', anyChildChecked);
            }
        }

        $('#expandAll').click(function() {
            $('.permission-children, .grandchild-permissions').slideDown(200).addClass('show');
            $('.toggle-arrow').addClass('rotated');
        });

        $('#collapseAll').click(function() {
            $('.permission-children, .grandchild-permissions').slideUp(200).removeClass('show');
            $('.toggle-arrow').removeClass('rotated');
        });

        $('#searchPermissions').on('keyup', function() {
            var searchText = $(this).val().toLowerCase();

            if (searchText.length > 0) {
                $('.permission-children, .grandchild-permissions').slideDown(200).addClass('show');
                $('.toggle-arrow').addClass('rotated');

                $('.permission-name').each(function() {
                    var permissionText = $(this).text().toLowerCase();
                    var permissionItem = $(this).closest(
                        '.permission-card, .child-permission, .grandchild-permission');

                    if (permissionText.indexOf(searchText) > -1) {
                        permissionItem.show();
                        $(this).html(highlightText($(this).text(), searchText));
                    } else {
                        var hasMatchingChildren = false;

                        if (permissionItem.hasClass('permission-card')) {
                            hasMatchingChildren = permissionItem.find(
                                '.child-permission .permission-name, .grandchild-permission .permission-name'
                            ).text().toLowerCase().indexOf(searchText) > -1;
                        } else if (permissionItem.hasClass('child-permission')) {
                            hasMatchingChildren = permissionItem.find(
                                    '.grandchild-permission .permission-name').text()
                                .toLowerCase().indexOf(searchText) > -1;
                        }

                        if (hasMatchingChildren) {
                            permissionItem.show();
                        } else {
                            permissionItem.hide();
                        }
                    }
                });
            } else {
                $('.permission-card, .child-permission, .grandchild-permission').show();
                $('.permission-name').each(function() {
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
                    '<span class="highlight">' +
                    text.substring(index, index + searchText.length) +
                    '</span>' +
                    text.substring(index + searchText.length);
            }

            return text;
        }

        $('<style>.highlight { background-color: rgba(67, 97, 238, 0.2); padding: 0 2px; border-radius: 3px; }</style>')
            .appendTo('head');
    });
</script>
