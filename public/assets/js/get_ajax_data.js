function get_ajax_data() {

    var form = $('#list_data');
    var actionUrl = form.attr('action');
    $('.loader-container').show();
    $('#response').html('');
    $.ajax({
        type: "get",
        url: actionUrl,
        data: form.serialize(), // serializes the form's elements.
        async: true,
        cache: false,
        success: function(data) {
            $('.loader-container').hide();
            $('#response').html(data);
        }
    });
}

function getFilteredData() {
    var form = $('#filter_list_data');
    var actionUrl = form.attr('action');
    var modalId = $('#modalId').val();
    $('.loader-container').show();
    $('#response').html('');
    $.ajax({
        type: "get",
        url: actionUrl,
        data: form.serialize(), // serializes the form's elements.
        async: true,
        cache: false,
        success: function(data) {
            $('#'+modalId).modal('hide');
            $('.loader-container').hide();
            $('#response').html(data);
        }
    });
}

function getAjaxDataOnEditColumns() {

    var actionUrl = $('#ajaxLoadUrl').val();
    var modalId = $('#modalId').val();
    $('.loader-container').show();
    $('#response').html('');
    $.ajax({
        type: "get",
        url: actionUrl,
        data: [], // serializes the form's elements.
        async: true,
        cache: false,
        success: function(data) {
            $('#'+modalId).modal('hide');
            $('.loader-container').hide();
            $('#response').html(data);
        }
    });
}

$(document).ready(function () {
    $(document).on('click', function (event) {
        if (!$(event.target).closest('#searchInput, #searchSuggestions').length) {
            $('#searchSuggestions').hide(); // Hide suggestions when clicking outside
        }
    });
    $('#searchInput').on('input', function () {
        var searchTerm = $(this).val();
        var searchInputUrl = $('#searchInputUrl').val();

        if (searchTerm.length >= 3) {
            $.ajax({
                url: searchInputUrl,
                method: "GET",
                data: { search: searchTerm },
                success: function (data) {
                    $('#searchSuggestions').html(data).show(); // Show the suggestions
                }
            });
        } else {
            $('#searchSuggestions').html('').hide(); // Hide the suggestions
        }
    });
});