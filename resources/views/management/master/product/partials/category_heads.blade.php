{{-- Category Heads Type-Wise Selection (Shows all category heads when Sort Type is 'head' and Parent Product is null) --}}
<div class="col-xs-12 col-sm-12 col-md-12" id="categoryHeadsSection" style="display: none;">
    <div class="p-2 mb-2 rounded border" style="background: #f8fafc; border-color: #e2e8f0 !important;">
        {{-- Section Header --}}
        <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
            <label class="font-weight-bold text-dark font-small-3 mb-0">
                <i class="ft-layers text-primary mr-1"></i>Category Heads:
            </label>
            <div class="font-small-2">
                <a href="javascript:void(0);" id="btnCheckAllCategoryHeads" class="mr-2 text-primary font-weight-bold">
                    <i class="ft-check-square mr-1"></i>Check All
                </a>
                <a href="javascript:void(0);" id="btnUncheckAllCategoryHeads" class="text-muted">
                    <i class="ft-square mr-1"></i>Uncheck All
                </a>
            </div>
        </div>

        {{-- Type-Wise Clean Columns --}}
        <div class="row" id="categoryHeadsContainer">
            {{-- Dynamically populated columns for Head Product, B2 Product, By Product --}}
        </div>
    </div>
</div>

<style>
    .heads-type-column-card {
        border-color: #e2e8f0 !important;
        background: #ffffff;
        border-radius: 6px;
    }
    .head-item-row {
        padding: 5px 8px;
        border-radius: 4px;
        transition: background 0.15s ease;
    }
    .head-item-row:hover {
        background-color: #f1f5f9;
    }
    .heads-column-items {
        max-height: 220px;
        overflow-y: auto;
    }
    .heads-column-items::-webkit-scrollbar {
        width: 4px;
    }
    .heads-column-items::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 4px;
    }
    .head-item-checkbox {
        accent-color: #007bff;
    }
</style>

<script>
(function() {
    var headsApiUrl = "{{ route('category.heads.get', ':id') }}";
    var cachedData = null;

    function getSelectedSortType() {
        return $('input[name="product_category_flags"]:checked').val() || 'head';
    }

    function updateCountBadge() {
        // Update column "All" checkboxes
        $('.group-check-all').each(function() {
            var groupSlug = $(this).data('group');
            var $groupItems = $('.head-item-checkbox[data-group="' + groupSlug + '"]');
            var groupTotal = $groupItems.length;
            var groupChecked = $groupItems.filter(':checked').length;
            $(this).prop('checked', groupTotal > 0 && groupTotal === groupChecked);
        });
    }

    function renderCategoryHeads() {
        var $section = $('#categoryHeadsSection');
        var $container = $('#categoryHeadsContainer');

        // 1. Hide if someone selects a Parent Product
        var parentId = $('#parent_id').val();
        if (parentId) {
            $section.slideUp(150);
            return;
        }

        // 2. Only show when Sort Type is 'head' selected
        var sortType = getSelectedSortType();
        if (sortType !== 'head') {
            $section.slideUp(150);
            return;
        }

        // 3. If no category data available, hide
        if (!cachedData || !cachedData.category) {
            $section.slideUp(150);
            $container.empty();
            return;
        }

        var groups = cachedData.groups || [];
        var totalItems = 0;
        groups.forEach(function(g) {
            totalItems += (g.items || []).length;
        });

        if (totalItems === 0) {
            $container.html(`
                <div class="col-12 py-2 text-center text-muted font-small-2">
                    <i class="ft-info mr-1"></i> No heads configured for ${cachedData.category.name}.
                </div>
            `);
            $section.slideDown(150);
            return;
        }

        var colClass = 'col-md-4 col-sm-6';
        if (groups.length === 2) {
            colClass = 'col-md-6 col-sm-6';
        } else if (groups.length === 1) {
            colClass = 'col-12';
        }

        // Render ALL category heads in balanced, clean columns with visible single checkboxes
        var html = '';
        groups.forEach(function(group) {
            if (!group.items || group.items.length === 0) return;

            var itemsHtml = '';
            group.items.forEach(function(item) {
                itemsHtml += `
                    <label class="head-item-row d-flex align-items-center mb-1 cursor-pointer text-dark" for="chk_head_${item.id}">
                        <input type="checkbox"
                               class="mr-2 head-item-checkbox"
                               id="chk_head_${item.id}"
                               name="selected_heads[]"
                               value="${item.id}"
                               data-group="${group.slug}"
                               checked
                               style="width: 17px; height: 17px; cursor: pointer; accent-color: #007bff; flex-shrink: 0;">
                        <span class="font-small-3 font-weight-bold text-truncate" title="${item.name}">
                            ${item.name}
                        </span>
                    </label>
                `;
            });

            html += `
                <div class="${colClass} mb-2">
                    <div class="heads-type-column-card p-2 border shadow-xs h-100">
                        <div class="d-flex justify-content-between align-items-center pb-1 mb-2 border-bottom">
                            <div class="d-flex align-items-center">
                                <span class="font-weight-bold text-dark font-small-3 mr-1">${group.title}</span>
                                <span class="badge badge-pill badge-light border text-muted font-small-1">(${group.items.length})</span>
                            </div>
                            <div>
                                <label class="d-flex align-items-center mb-0 cursor-pointer font-small-2 text-muted" for="chk_grp_${group.slug}">
                                    <input type="checkbox"
                                           class="mr-1 group-check-all"
                                           id="chk_grp_${group.slug}"
                                           data-group="${group.slug}"
                                           checked
                                           style="width: 15px; height: 15px; cursor: pointer; accent-color: #007bff;">
                                    <span>All</span>
                                </label>
                            </div>
                        </div>
                        <div class="heads-column-items pr-1">
                            ${itemsHtml}
                        </div>
                    </div>
                </div>
            `;
        });

        $container.html(html);
        updateCountBadge();
        $section.slideDown(150);
    }

    function fetchCategoryHeads(categoryId) {
        if (!categoryId) {
            cachedData = null;
            $('#categoryHeadsSection').slideUp(150);
            $('#categoryHeadsContainer').empty();
            return;
        }

        var url = headsApiUrl.replace(':id', categoryId);

        // Show loading state if sort type is 'head' and parent_id is null
        var parentId = $('#parent_id').val();
        var sortType = getSelectedSortType();
        if (!parentId && sortType === 'head') {
            $('#categoryHeadsContainer').html(`
                <div class="col-12 py-2 text-center text-muted font-small-2">
                    <i class="ft-refresh-cw font-small-2 spinner mr-1"></i> Loading heads...
                </div>
            `);
            $('#categoryHeadsSection').slideDown(120);
        }

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    cachedData = res;
                    renderCategoryHeads();
                } else {
                    cachedData = null;
                    $('#categoryHeadsSection').slideUp(150);
                }
            },
            error: function() {
                cachedData = null;
                $('#categoryHeadsContainer').html(`
                    <div class="col-12 py-2 text-center text-danger font-small-2">
                        <i class="ft-alert-circle mr-1"></i> Failed to load heads.
                    </div>
                `);
            }
        });
    }

    // 1. Radio Sort Type listener: show all when 'head' selected, hide when other sort type
    $(document).off('change.sort_type_heads', 'input[name="product_category_flags"]').on('change.sort_type_heads', 'input[name="product_category_flags"]', function() {
        renderCategoryHeads();
    });

    // 2. Category change listener
    $(document).off('change.load_cat_heads', '#category_id').on('change.load_cat_heads', '#category_id', function() {
        var catId = $(this).val();
        fetchCategoryHeads(catId);
    });

    // 3. Parent Product change listener: hide when parent product is selected, show all and check when null!
    $(document).off('change.parent_heads_toggle', '#parent_id').on('change.parent_heads_toggle', '#parent_id', function() {
        var parentId = $(this).val();
        if (parentId) {
            $('#categoryHeadsSection').slideUp(150);
        } else {
            renderCategoryHeads();
            $('.head-item-checkbox').prop('checked', true);
            updateCountBadge();
        }
    });

    // 4. Check All button
    $(document).off('click', '#btnCheckAllCategoryHeads').on('click', '#btnCheckAllCategoryHeads', function(e) {
        e.preventDefault();
        $('.head-item-checkbox').prop('checked', true);
        $('.group-check-all').prop('checked', true);
        updateCountBadge();
    });

    // 5. Uncheck All button
    $(document).off('click', '#btnUncheckAllCategoryHeads').on('click', '#btnUncheckAllCategoryHeads', function(e) {
        e.preventDefault();
        $('.head-item-checkbox').prop('checked', false);
        $('.group-check-all').prop('checked', false);
        updateCountBadge();
    });

    // 6. Group Checkbox change listener
    $(document).off('change', '.group-check-all').on('change', '.group-check-all', function() {
        var groupSlug = $(this).data('group');
        var isChecked = $(this).is(':checked');
        $('.head-item-checkbox[data-group="' + groupSlug + '"]').prop('checked', isChecked);
        updateCountBadge();
    });

    // 7. Item Checkbox change listener
    $(document).off('change', '.head-item-checkbox').on('change', '.head-item-checkbox', function() {
        updateCountBadge();
    });

    // Initial check on modal load
    var initialCatId = $('#category_id').val();
    if (initialCatId) {
        fetchCategoryHeads(initialCatId);
    }
})();
</script>
