
<div class="row">
    
        <div class="col-md-12 mt-4">
            <h4 class="card-heading text-center mt-4">{{ __('Setup Google Authenticator') }} </h4>
            <div class="card-body" style="text-align: center;">
                <p>Scan the QR code with the Google Authenticator app and enter the 6-digit code below.</p>
            <div>    
            <?php print_r($QR_Image) ?>    
            {{--  <?php 
            header('Content-Type: image/svg+xml');
            echo '<?xml version="1.0" encoding="UTF-8"?>' . $QR_Image;
            ?> --}}
        </div>
        <p>You must set up your Google Authenticator app before continuing. You will be unable to login otherwise</p>
<div>
    <form id="googleAuthForm" method="POST" action="{{ url('/setup-google-authenticator') }}">
        @csrf

        <div class="form-group row">
            <label for="2fa_code" class="col-md-4 col-form-label text-md-right">{{ __('6-digit Code') }}</label>

            <div class="col-md-6">
                <input id="2fa_code" type="text" class="form-control @error('2fa_code') is-invalid @enderror" name="2fa_code" required autofocus>
                <span style="color:red;">
                    <strong id="msg"></strong>
                </span>
                <span style="color:green;">
                    <strong id="success_msg"></strong>
                </span>
            </div>
        </div>

        <div class="form-group row mb-0">
            <div class="col-md-6 offset-md-4">
                <button  id="verifyBtn" type="button" class="btn btn-primary">
                    {{ __('Verify And Save') }}
                </button>
            </div>
        </div>
    </form>


 
                

<script>
    
    $(document).ready(function () {
        var encryptedSecretKey= '<?php echo $encryptedSecretKey ?>';
        
        $('#verifyBtn').on('click', function () {
            $('#msg').html('');
            // Serialize the form data
            var formData = $('#googleAuthForm').serialize();
            formData += '&encryptedSecretKey=' + encodeURIComponent(encryptedSecretKey);
            // Send an AJAX request
            $.ajax({
                url: '{{ url('/setup-google-authenticator') }}',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if(response.error != ''){
                        $('#success_msg').html('')
                        $('#msg').html(response.error);
                    }
                    if(response.success != ''){
                        $('#msg').html('');
                        $('#success_msg').html(response.success)
                        // window.location.href = '{{ route('dashboard.index') }}';     
                    }
                
                },
                error: function (xhr) {
                    // Handle error response
                    $('#msg').html(xhr.responseJSON.error);
                    // alert(xhr.responseJSON.error); // or update the UI accordingly
                }
            });
        });
    });
</script>

