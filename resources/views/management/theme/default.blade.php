<!DOCTYPE html>
<html lang="en">
<head>
    @include('theme.includes.compatibility')
    <meta name="description" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ env('APP_NAME') }}</title>
    <style>
        .toast-message{
            color:white;
            font-weight:bold;
            font-size:16px;
        }
        .toast {
            opacity: 1 !important;
        }
        .toast-close-button{
            color:white !important;
        }

    </style>
    @include('theme.includes.style')
</head>
<body class="vertical-layout vertical-menu-modern  navbar-floating footer-static defaultBody " data-open="click" data-menu="vertical-menu-modern" data-col="" onload="startTime()">

@include('theme.includes.header')
@include('theme.includes.right_sidebar')
@include('theme.includes.sidebar')
@include('theme.includes.modals')

@yield('content')
<div style="display: none" class="loader-container" id="loader-container">
    <div  class="loader"></div>
</div>

@include('theme.includes.footer')
@include('theme.includes.scripts')
@yield('script')
@stack('scripts')

<script>

    $( document ).ready(function() {
        @php
            $pusherCfg = function_exists('getPusherConfig') ? getPusherConfig() : null;
        @endphp
        @if(!empty($pusherCfg['enabled']) && !empty($pusherCfg['key']))
        var pusher = new Pusher("{{ $pusherCfg['key'] }}", {
            cluster: "{{ $pusherCfg['cluster'] ?? 'ap2' }}"
        });

        var channel = pusher.subscribe('notify-channel');
        channel.bind('notify-event', function(data) {
            var currentUserId = Number({{ auth()->user()->id ?? 0 }});
            var currentCompanyId = Number({{ getCurrentCompany()?->id ?? 0 }});

            if (data.company_id && currentCompanyId && Number(data.company_id) !== currentCompanyId) {
                return;
            }

            var isRecipient = !data.user_ids || data.user_ids.length === 0 || data.user_ids.map(Number).includes(currentUserId);
            if (isRecipient || data.user_id == currentUserId) {
                toastr.options = {
                    closeButton: true,
                    progressBar: true,
                    showMethod: 'slideDown',
                    timeOut: 0,
                    extendedTimeOut: 0
                };
                toastr.info(data.message);
            }
        });
        @endif
    });

    var quill = new Quill('#email-message-editor', {
        theme: 'snow'  // or 'bubble'
    });

    function appendTemplates() {
        var selectedOption = $('#templateSelect option:selected');
        if (selectedOption.length > 0) {
            var selectedHtml = selectedOption.data('html');
            quill.clipboard.dangerouslyPasteHTML(selectedHtml);
        } else {
            console.log("No option selected!");
        }
    }

    $("#submitEmail").on("submit", function (event) {
        var quill = new Quill("#message-editor .editor");
        var textContent = quill.getText().trim();

        if (textContent === "") {
            // If there is no text content, set the value to an empty string
            document.getElementById("emailMessage").value = "";
        } else {
            // If there is text content, set the value to the Quill editor's HTML
            document.getElementById("emailMessage").value = quill.root.innerHTML;
        }

        // The rest of your form submission logic...
    });

</script>
</body>
</html>
