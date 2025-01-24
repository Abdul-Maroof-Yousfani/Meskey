    {!! Form::open(['route' => 'users.store', 'method' => 'POST', 'id' => 'ajaxSubmit']) !!}
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
                    <div id="imagePreview" style="background-image: url('{{ image_path('') }}');">
                    </div>
                </div>
            </div>
            <p class="text-center mt-2">{{ '@' . auth()->user()->username }}</p>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                {!! Form::text('name', null, ['placeholder' => 'Name', 'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Email:</label>
                {!! Form::text('email', null, ['placeholder' => 'Email', 'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Password:</label>
                {!! Form::password('password', ['placeholder' => 'Password', 'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Confirm Password:</label>
                {!! Form::password('confirm-password', ['placeholder' => 'Confirm Password', 'class' => 'form-control']) !!}
            </div>
        </div>
        {{--        <div class="col-xs-12 col-sm-12 col-md-12"> --}}
        {{--            <div class="form-group"> --}}
        {{--                <label>Role:</label> --}}
        {{--                {!! Form::select('roles[]', $roles,[], array('class' => 'form-control','multiple')) !!} --}}
        {{--            </div> --}}
        {{--        </div> --}}





    </div>

    <div id="card-container" class="mb-4">
        <div class="clonecard border-1">
            <hr>
            <div class="row justify-content-center">
                <div class="col-xs-5 col-sm-5 col-md-5">
                    <div class="form-group m-0">
                        <label>Company:</label>
                        <select name="company[]" class="form-control">
                            @foreach (getAllCompanies() as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-xs-5 col-sm-5 col-md-5">
                    <div class="form-group m-0">
                        <label>Role:</label>
                        <select name="role[]" class="form-control">
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                    <div>

                        <button type="button" class="btn btn-warning btn-icon add-more   mr-1 "><i
                                class="fa fa-plus"></i></button>
                        <button type="button" class="btn btn-danger btn-icon remove-card  mr-1 "><i
                                class="fa fa-trash"></i></button>

                    </div>
                    {{-- <button id="add-more" type="button" class="btn btn-primary mt-3 ">Add More</button> --}}

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
        // Function to add more cards
        // Function to toggle visibility of the Remove button
        function toggleRemoveButton() {
            if ($('#card-container .clonecard').length === 1) {
                $('#card-container .clonecard .remove-card').hide(); // Hide Remove button
            } else {
                $('#card-container .clonecard .remove-card').show(); // Show Remove button
            }
        }

        // Initialize Remove button visibility
        toggleRemoveButton();

        // Add More button click event
        $('body').on('click', '.add-more', function() {
            var newCard = $('#card-container .clonecard:first').clone(); // Clone the first card
            newCard.find('select').val(''); // Clear select values
            $('#card-container').append(newCard); // Append the new card
            toggleRemoveButton(); // Toggle Remove button visibility
        });

        // Remove button click event
        $(document).on('click', '.remove-card', function() {
            if ($('#card-container .clonecard').length > 1) {
                $(this).closest('.clonecard').remove(); // Remove the specific card
                toggleRemoveButton(); // Toggle Remove button visibility
            }
        });
    </script>
