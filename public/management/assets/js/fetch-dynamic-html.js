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

    // Abort previous request if any BEFORE showing loader
    const prevRequest = target.data("ajaxRequest");
    if (prevRequest) {
        target.data("ajaxRequest", null);
        try {
            prevRequest.abort();
        } catch (e) {
            // ignore
        }
    }

    // Show loader
    if (settings.loader) {
        target.html(`
            <div class="text-center p-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <div class="mt-2 text-muted">${settings.loadingText}</div>
            </div>
        `);
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
            // ignore if undefined
            if (typeof updateJobOrderQty !== 'undefined') {
                updateJobOrderQty();
            }
        },
        error: function (xhr, textStatus, errorThrown) {
            // If request was aborted, ignore it completely (do not overwrite loader or display error)
            if (textStatus === "abort" || xhr.statusText === "abort" || errorThrown === "abort" || (xhr.status === 0 && (xhr.statusText === "abort" || textStatus === "abort"))) {
                return;
            }

            // Custom error callback
            if (settings.onError) {
                settings.onError(xhr, target, textStatus, errorThrown);
                return;
            }

            let errorMsg = xhr.statusText || errorThrown || "Error";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }

            target.html(`
                <div class="alert alert-danger m-2">
                    Failed to load content: ${xhr.status ? xhr.status + ' - ' : ''}${errorMsg}
                </div>
            `);
        },
        complete: function (xhr, textStatus) {
            if (target.data("ajaxRequest") === request) {
                target.data("ajaxRequest", null);
            }
        }
    });

    // Store request for abort feature
    target.data("ajaxRequest", request);

    return request; // in case needed externally
}
