{!! Form::model($user, ['route' => ['users.update', $user->id], 'method' => 'PUT', 'id' => 'ajaxSubmit']) !!}
<input type="hidden" id="url" value="{{ route('users.index') }}" />

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
                <div id="imagePreview" style="background-image: url('{{ image_path($user->profile_image ?? '') }}');">
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

    <!-- Password Fields -->
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Password:</label>
            {!! Form::password('password', ['placeholder' => 'Leave blank to keep current', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Confirm Password:</label>
            {!! Form::password('password_confirmation', ['placeholder' => 'Confirm Password', 'class' => 'form-control']) !!}
        </div>
    </div>

    <!-- User Type Field -->
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>User Type:</label>
            <input type="hidden" name="user_type" value="{{ $user->user_type }}" class="form-control">
            <select name="user_type_d" disabled id="user_type" class="form-control">
                <option value="user" {{ $user->user_type === 'user' ? 'selected' : '' }}>User</option>
                <option value="super-admin" {{ $user->user_type === 'super-admin' ? 'selected' : '' }}>Super Admin
                </option>
            </select>
        </div>
    </div>

    <!-- Location Field -->
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Location:</label>
            <select name="company_location_id" id="company_location" class="form-control"
                {{ $user->user_type === 'super-admin' ? 'disabled' : '' }}>
                <option value="">Select Location</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}"
                        {{ $user->company_location_id == $location->id ? 'selected' : '' }}>
                        {{ $location->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Arrival Location:</label>
            <select name="arrival_location_id" id="arrival_location" class="form-control">
                {{-- <option value="">Select Arrival Location</option> --}}
                @if (isset($user) && $user->company_location_id && $user->companyLocation->arrivalLocations)
                    @foreach ($user->companyLocation->arrivalLocations as $location)
                        <option value="{{ $location->id }}"
                            {{ $user->arrival_location_id == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>
</div>

<div id="card-container" class="mb-4">
    @foreach ($user->companies as $index => $company)
        <div class="clonecard border-1 {{ $index > 0 ? '' : 'original-card' }}">
            <hr>
            <div class="row justify-content-center">
                <div class="col-xs-5 col-sm-5 col-md-5">
                    <div class="form-group m-0">
                        <label>Company:</label>
                        <select name="company[]" class="form-control">
                            @foreach (getAllCompanies() as $comp)
                                <option value="{{ $comp->id }}" {{ $company->id == $comp->id ? 'selected' : '' }}>
                                    {{ $comp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-xs-5 col-sm-5 col-md-5">
                    <div class="form-group m-0">
                        <label>Role:</label>
                        <select name="role[]" class="form-control">
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}"
                                    {{ $user->roles->contains($role->id) ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                    <div>
                        @if ($index === 0)
                            <button type="button" class="btn btn-warning btn-icon add-more mr-1">
                                <i class="fa fa-plus"></i>
                            </button>
                        @endif
                        <button type="button" class="btn btn-danger btn-icon remove-card mr-1">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row bottom-button-bar">
    <div class="col-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
        <button type="submit" class="btn btn-primary submitbutton">Save</button>
    </div>
</div>
{!! Form::close() !!}

<script>
    $(document).ready(function() {
        // Username generation and validation
        $('#name').on('keyup', function() {
            const name = $(this).val().trim();
            const username = name.toLowerCase().replace(/\s+/g, '').replace(/[^a-z0-9-]/g, '');
            $('#username').val(username);
            checkUsernameAvailability(username);
        });

        $('#username').on('keyup', function() {
            checkUsernameAvailability($(this).val());
        });

        $('#company_location').on('change', function() {
            const companyLocationId = $(this).val();
            const arrivalLocationSelect = $('#arrival_location');

            arrivalLocationSelect.empty().append('<option value="">Select Arrival Location</option>');

            if (companyLocationId) {
                $.get(`/acl/get-arrival-locations/${companyLocationId}`, function(data) {
                    data.forEach(location => {
                        arrivalLocationSelect.append(
                            `<option value="${location.id}">${location.name}</option>`
                        );
                    });
                });
            }
        });

        function checkUsernameAvailability(username) {
            if (username) {
                $.get('/acl/check-username', {
                    username: username,
                    user_id: {{ $user->id ?? 'null' }}
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
            }
        }

        // Toggle location field based on user type
        $('#user_type').on('change', function() {
            if ($(this).val() === 'super-admin') {
                $('#company_location').val('').prop('disabled', true);
            } else {
                $('#company_location').prop('disabled', false);
            }
        }).trigger('change');

        // Card management functions
        function toggleRemoveButton() {
            if ($('#card-container .clonecard').length === 1) {
                $('#card-container .clonecard .remove-card').hide();
            } else {
                $('#card-container .clonecard .remove-card').show();
            }
        }

        toggleRemoveButton();

        $('body').on('click', '.add-more', function() {
            var newCard = $('#card-container .original-card').clone();
            newCard.removeClass('original-card');
            newCard.find('select').val('');
            $('#card-container').append(newCard);
            toggleRemoveButton();
        });

        $(document).on('click', '.remove-card', function() {
            if ($('#card-container .clonecard').length > 1) {
                $(this).closest('.clonecard').remove();
                toggleRemoveButton();
            }
        });
    });
</script>
