    <!-- START Notification Sidebar-->
    <aside class="modal-sidebar d-block d-sm-block d-md-block" id="modal-sidebar2">
        <div class="side-nav modal-sidebar-content">
            <div class="row w-100 mx-auto align-items-center">
                {{-- <div class="col-10 ">
                    <h4 class="modal-title my-3">8888</h4>
                </div> --}}
                {{-- <div class="col-2 text-right">
                    <a class="modal-sidebar-close top-wala position-relative top-1">
                        <i class="ft-x font-medium-3  darken-1"></i>
                    </a>
                </div> --}}
                <div class="col-12 modal-tab-content">

                </div>
            </div>
        </div>
    </aside>

    <div class="model d-none" id="settinsgs">
        <div class="model-content">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="modal-title"></h2>
                <button type="button" class="close" data-close="model">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body"></div>
            <div class="row text-center center hide">
                <div class="col-12">
                    <button type="submit" class="btn-theme">Save</button>
                    <button type="button" class="btn-white" data-close="model">Cancel</button>
                </div>
            </div>
        </div>

    </div>



    <div class="model d-none" id="deletemodal">
        <div class="model-content">
            {{--        <h2 class="modal-title">Are you sure</h2> --}}
            <div class="d-flex justify-content-between align-items-center">

                <div></div>
                <button type="button" class="bg-transparent border-0 closed float-end" data-close="model">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @include('management.theme.includes.error_success')

            <div class="modal-body text-center py-4">
                Are you sure you want to delete this entry?
            </div>
            <div class="row text-center center ">
                <div class="col-12">
                    <!-- In your view file -->
                    <form id="subm" class="delete-form" action="" method="POST">
                        <input type="hidden" id="url" value="">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-primary">Confirm</button>
                        <button type="button" class="btn btn-danger" data-close="model">Cancel</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="model d-none" id="modal2">
        <div class="model-content">

            <h5 class="modal-title"></h5>
            <input type="hidden" id="appendClass" value="" />

            <button type="button" class="close" data-close="modeel">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="modal-body"></div>
            <div class="modal-footer hiden">
                <button type="button" class="btn-white" data-close="modeel">Cancel</button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Reset</button>
            </div>
        </div>

    </div>
    <script>
        function submitFormWithSwal(selector) {
            // Loop through all forms matching the selector
            $(document).on('click', `${selector} button`, function(e) {
                e.preventDefault(); // Prevent default button action

                const form = $(this).closest('form'); // Get the closest form element
                const formData = form.serialize(); // Serialize the form data

                // Extract SweetAlert messages from hidden fields, if any
                const swalTitle = form.find('input[name="swal_title"]').val() || 'Are you sure?';
                const swalText = form.find('input[name="swal_text"]').val() || 'You are about to send an email.';
                const swalConfirmButtonText = form.find('input[name="swal_confirm_text"]').val() || 'Yes, send it!';
                const swalCancelButtonText = form.find('input[name="swal_cancel_text"]').val() || 'Cancel';

                Swal.fire({
                    title: swalTitle,
                    text: swalText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: swalConfirmButtonText,
                    cancelButtonText: swalCancelButtonText,
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loader
                        Swal.fire({
                            title: 'Sending...',
                            text: 'Please wait while the email is being sent.',
                            icon: 'info',
                            showConfirmButton: false,
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading(); // Show loading spinner
                            }
                        });

                        // Submit the form via AJAX
                        $.ajax({
                            url: form.attr('action'),
                            method: form.attr('method'),
                            data: formData,
                            success: function(response) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'The email has been sent successfully.',
                                    icon: 'success',
                                    confirmButtonColor: '#3085d6',
                                });
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                let errorMessage =
                                    'Something went wrong. Please try again later.';

                                if (jqXHR.status === 0) {
                                    errorMessage =
                                        'You are offline! Please check your network connection.';
                                } else if (jqXHR.status >= 400 && jqXHR.status < 500) {
                                    errorMessage =
                                        `Client Error: ${jqXHR.responseJSON?.message || jqXHR.statusText}`;
                                } else if (jqXHR.status >= 500) {
                                    errorMessage =
                                        'Server Error: Please contact support or try again later.';
                                } else if (textStatus === 'timeout') {
                                    errorMessage = 'Request timed out. Please try again later.';
                                } else if (textStatus === 'abort') {
                                    errorMessage = 'Request was aborted. Please try again.';
                                }

                                Swal.fire({
                                    title: 'Error!',
                                    text: errorMessage,
                                    icon: 'error',
                                    confirmButtonColor: '#3085d6',
                                });
                            },
                        });
                    }
                });
            });
        }

        // Initialize the function for your forms
        $(document).ready(function() {
            submitFormWithSwal('.send-email-form'); // Replace with your form's class or ID
        });




        function deletemodal(url, redirecturl, reloadtype = null) {
            // Use SweetAlert for confirmation
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                preConfirm: () => {
                    // Show preloader using SweetAlert
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we process your request.',
                        icon: 'question',

                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading(); // Show loading spinner
                        }
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Perform the delete action using jQuery AJAX
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _method: 'DELETE', // Laravel's way of spoofing DELETE request
                            _token: $('meta[name="csrf-token"]').attr(
                                'content') // Assuming you have a CSRF token meta tag
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Your record has been deleted.',
                                'success'
                            ).then(() => {
                                if (reloadtype == 'tab') {
                                    openLeadsChunks(redirecturl);
                                } else {
                                    filterationCommon(redirecturl);
                                }
                            });
                        },
                        error: function(xhr, status, error) {
                            // Handle errors if necessary
                            console.error("Error deleting content:", error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error ' + status,
                                html: `<p>${xhr.responseJSON.error}</p><small>${xhr.responseJSON.details}</small>`
                            });
                        }
                    });
                }
            });
        }




        function QuickAddModal(url, title, classname) {
            $('#modal2 .modal-title').html(title);
            $('#appendClass').val(classname);
            var thisModel = $("#modal2").show();
            $('body').on('click', '[data-close="modeel"]', function() {
                $(thisModel).hide();
            });
            // Use jQuery AJAX to fetch modal content
            $.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    // Inject modal content into the page
                    $('#modal2 .modal-body').html(data);
                    // Scripts in the loaded content will be automatically executed
                },
                error: function(xhr, status, error) {
                    // Handle errors if necessary
                    console.error("Error loading modal2:", error);
                }
            });
        }


        function getListing(url) {
            // Show loader while waiting for response
            $('#listdata').html(`
        <div class="loader-container" id="loader-container-ajax">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
    `);

            // Make AJAX request
            $.ajax({
                url: url,
                method: 'GET',
                success: function(data) {
                    // Inject modal content into the page
                    $('#listdata').html(data);
                    $('#listdata .loader-container').remove();
                    // Scripts in the loaded content will be automatically executed
                },
                error: function(xhr, status, error) {
                    // Remove loader
                    $('#listdata .loader-container').remove();

                    // Handle network errors
                    if (status === 'error' && xhr.status === 0) {
                        $('#listdata').html(
                            '<p class="text-danger">Network error. Please check your internet connection.</p>'
                        );
                    } else {
                        // Handle other errors
                        console.error("Error loading data:", error);
                        // Display generic error message
                        $('#listdata').html('<p class="text-danger">An error occurred while loading data.</p>');
                    }
                }
            });
        }





        $('body').on('click', '[data-close="modeel"]', function() {
            $("#modal2").hide();
        });
    </script>

    @yield('script')
