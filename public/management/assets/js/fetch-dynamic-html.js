function fetchDynamicHTML(route, targetId, params = {}, options = {}) {

    const target = $("#" + targetId);

    // Default options
    const settings = {
        method: options.method || "POST",
        loader: options.loader !== false,
        loadingText: options.loadingText || "Loading...",
        onSuccess: options.onSuccess || null,
        onError: options.onError || null,
        ...options
    };

    // Show loader
    if (settings.loader) {
        target.html(`
            <div class="text-center p-3">
                <div class="spinner-border text-primary"></div>
                <div>${settings.loadingText}</div>
            </div>
        `);
    }

    // Abort previous request if any
    if (target.data("ajaxRequest")) {
        target.data("ajaxRequest").abort();
    }

    // AJAX request
    let request = $.ajax({
        url: route,
        type: settings.method,
        data: params,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
        },
        success: function (response) {

            // Custom callback
            if (settings.onSuccess) {
                settings.onSuccess(response, target);
            } else {
                target.html(response);
            }

            updateJobOrderQty();
        },
        error: function (xhr) {

            // Custom error callback
            if (settings.onError) {
                settings.onError(xhr, target);
                return;
            }

            target.html(`
                <div class="alert alert-danger">
                    Failed to load content: ${xhr.status} - ${xhr.statusText}
                </div>
            `);
        }
    });

    // Store request for abort feature
    target.data("ajaxRequest", request);

    return request; // in case needed externally
}
