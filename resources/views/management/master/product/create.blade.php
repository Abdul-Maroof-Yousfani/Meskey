<form action="{{ route('product.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.product') }}" />

    <div class="row form-mar">

    <div class="col-md-12 mb-4">
        <div class="avatar-upload">
            <div class="avatar-edit">
                <input type='file' id="imageUpload" name="image" accept=".png, .jpg, .jpeg" />
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
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Category:</label>
                <select class="form-control" name="category_id">
                    <option value="">Select Category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Unit of Measure:</label>
                <select class="form-control" name="unit_of_measure_id">
                    <option value="">Select Unit of Measure</option>
                    @foreach ($units as $unitOfMeasure)
                        <option value="{{ $unitOfMeasure->id }}">{{ $unitOfMeasure->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" placeholder="Name" class="form-control"  />
            </div>
        </div>

        <!-- Description -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control"></textarea>
            </div>
        </div>

        <!-- Barcode -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Barcode:</label>
                <input type="text" name="barcode" placeholder="Barcode" class="form-control" />
            </div>
        </div>


        <!-- Price -->
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Price:</label>
                <input type="text" name="price" placeholder="Price" class="form-control" />
            </div>
        </div>

        <!-- Status -->
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status" >
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row bottom-button-bar">
        <div class="col-12">
            <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton">Close</a>
            <button type="submit" class="btn btn-primary submitbutton">Save</button>
        </div>
    </div>
</form>