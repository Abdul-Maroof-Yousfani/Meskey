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
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Product Type:</label>
                <select class="form-control" onchange="check(this.value)" name="product_type">
                    <option value="">Select Product Type</option>
                    <option value="raw_material">Raw Material</option>
                    <option value="finish_good">Finish Good</option>
                    <option value="general_items">General Items</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Category:</label>
                <select class="form-control" name="category_id" id="category_id">
                    <option value="">Select Category</option>

                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Parent Product:</label>
                <select class="form-control" name="parent_id" id="parent_id">
                    <option value="">Select Parent Product</option>
                    @foreach ($parentProducts as $parentProduct)
                        <option value="{{$parentProduct->id}}">{{$parentProduct->name}}</option>

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
                <input type="text" name="name" placeholder="Name" class="form-control" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" placeholder="Description" class="form-control"></textarea>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6 showhide">
            <div class="form-group">
                <label>Bag Weight for Purchasing:</label>
                <input type="text" name="bag_weight_for_purchasing" placeholder="Bag Weight for Purchasing"
                    class="form-control" />
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Barcode:</label>
                <input type="text" name="barcode" placeholder="Barcode" class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6 showhide">
            <div class="form-group">
                <label>Price:</label>
                <input type="text" name="price" placeholder="Price" class="form-control" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Status:</label>
                <select class="form-control" name="status">
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