    {!! Form::open(['route' => 'users-test.store', 'method' => 'POST', 'id' => 'ajaxSubmitSecond']) !!}
    <input type="hidden" id="url" value="{{ route('users-test.index') }}" />

    <div class="row">
        <div class="col-md-12 -auto">
            <div class="avatar-upload">
                <div class="avatar-edit">
                    <input type='file' id="imageUpload" name="profile_image" accept=".png, .jpg, .jpeg" />
                    <label for="imageUpload">
                        <i class="ft-camera"></i>
                    </label>
                </div>
                <div class="avatar-preview">
                    <div id="imagePreview" style="background-image: url('{{ image_path('') }}');">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                {!! Form::text('name', null, [
                    'placeholder' => 'Name',
                    'class' => 'form-control',
                    'id' => 'name',
                ]) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Username:</label>
                {!! Form::text('username', null, [
                    'placeholder' => 'Username',
                    'class' => 'form-control',
                    'id' => 'username',
                ]) !!}
                <small id="username-help" class="form-text text-muted">Will be generated automatically from name</small>
            </div>
        </div>

        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Email:</label>
                {!! Form::text('email', null, [
                    'placeholder' => 'Email',
                    'class' => 'form-control',
                    'id' => 'email',
                ]) !!}
            </div>
        </div>

        <!-- Password Fields (existing) -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Password:</label>
                {!! Form::password('password', ['placeholder' => 'Password', 'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Confirm Password:</label>
                {!! Form::password('password_confirmation', ['placeholder' => 'Confirm Password', 'class' => 'form-control']) !!}
            </div>
        </div>

        <!-- New User Type Field -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>User Type:</label>
                <select name="user_type" id="user_type" class="form-control">
                    <option value="user">User</option>
                    <option value="super-admin">Super Admin</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Parent User:</label>
                <select name="parent_user_id" id="parent_user_id" class="form-control">
                    <option value="">Select Parent User</option>
                    @foreach ($users as $parentUser)
                        <option value="{{ $parentUser->id }}">{{ $parentUser->name }} ({{ $parentUser->username }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">

            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save & Continue</button>
        </div>
    </div>
    {!! Form::close() !!}

    <script>
        $(document).ready(function() {
            $('#name').on('keyup', function() {
                // if (!$('#username').val()) {
                const name = $(this).val().trim();
                const username = name.toLowerCase().replace(/\s+/g, '').replace(/[^a-z0-9-]/g, '');
                $('#username').val(username);
                checkUsernameAvailability(username);
                // }
            });

            $('#username').on('keyup', function() {
                checkUsernameAvailability($(this).val());
            });

            function checkUsernameAvailability(username) {
                // if (username) {
                $.get('/acl/check-username', {
                    username: username
                }, function(data) {
                    if (data.available) {
                        $('#username').removeClass('is-invalid').addClass('is-valid');
                        $('#username-help').text('Username is available').removeClass('text-danger')
                            .addClass('text-success');
                    } else {
                        $('#username').removeClass('is-valid').addClass('is-invalid');
                        $('#username-help').text('Username is not available').removeClass(
                            'text-success').addClass('text-danger');
                    }
                });
                // }
            }

            // Toggle location field based on user type
            $('#user_type').on('change', function() {
                if ($(this).val() === 'super-admin') {
                    $('#company_location').val('').prop('disabled', true);
                } else {
                    $('#company_location').prop('disabled', false);
                }
            }).trigger('change');

            function toggleRemoveButton() {
                if ($('#card-container .clonecard').length === 1) {
                    $('#card-container .clonecard .remove-card').hide(); // Hide Remove button
                } else {
                    $('#card-container .clonecard .remove-card').show(); // Show Remove button
                }
            }

            toggleRemoveButton();
        });

        $('#ajaxSubmitSecond').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let url = form.attr('action');

            $.ajax({
                url: url,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.redirect) {
                        window.location.href = response.redirect; // <-- Redirect here
                    }
                },
                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });
        });
    </script>
