<div class="row">

    <div class="col-md-12">
        <label>City Name:</label>
        <input type="text" class="form-control" value="{{ $city->name }}" disabled />
    </div>

    <div class="col-md-12 mt-3">
        <label>Country:</label>
        <select name="country_id" class="form-control" disabled>
            <option value="">-- Select Country --</option>
            @foreach ($countries as $country)
                <option value="{{ $country->id }}" data-code="{{ $country->alpha_2_code }}"
                    {{ $city->country_id == $country->id ? 'selected' : '' }}>
                    {{ $country->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-12 mt-3">
        <label>Country Code:</label>
        <input type="text" class="form-control" value="{{ $city->country_code }}" disabled />
    </div>

    <div class="text-center mt-3">
        <div class="col-12">
            <button type="button" class="btn btn-danger modal-sidebar-close">Close</button>
        </div>
    </div>

</div>
