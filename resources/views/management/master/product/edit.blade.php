<form method="POST" action="{{ route('product.update', $product->id) }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    @method('PUT')
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
                    <div id="imagePreview" style="background-image: url('{{ image_path($product->image) }}');">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-6 col-sm-6 col-md-6">

            <fieldset>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <button class="btn btn-primary" type="button">Product Code#</button>
                    </div>
                    <input type="text" disabled class="form-control" value="{{ $product->unique_no }}"
                        placeholder="Button on left">
                </div>
            </fieldset>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Product Type:</label>
                <select class="form-control" onchange="check(this.value)" name="product_type">
                    <option value="">Select Product Type</option>
                    <option value="raw_material" {{ $product->product_type == 'raw_material' ? 'selected' : '' }}>Raw
                        Material</option>
                    <option value="finish_good" {{ $product->product_type == 'finish_good' ? 'selected' : '' }}>Finish
                        Good</option>
                    <option value="general_items" {{ $product->product_type == 'general_items' ? 'selected' : '' }}>
                        General Items</option>
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Category:</label>
                <select class="form-control" name="category_id">
                    <option value="">Select Category</option>
                    @foreach ($categories as $category)
                        <option {{ $category->id == $product->category_id ? 'selected' : '' }}
                            value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
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
                        <option {{ $unitOfMeasure->id == $product->unit_of_measure_id ? 'selected' : '' }}
                            value="{{ $unitOfMeasure->id }}">{{ $unitOfMeasure->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" value="{{ $product->name }}" placeholder="Name"
                    class="form-control" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" value="{{ $product->description }}" placeholder="Description" class="form-control"></textarea>
            </div>
        </div>

        <div class="col-xs-6 col-sm-6 col-md-6 showhide" style="{{ $product->product_type == 'general_items' ? 'display:none' : '' }}">
            <div class="form-group">
                <label>Bag Weight for Purchasing:</label>
                <input type="text" name="bag_weight_for_purchasing"
                    value="{{ $product->bag_weight_for_purchasing }}" placeholder="Bag Weight for Purchasing"
                    class="form-control" />
            </div>
        </div>
        
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group">
                <label>Barcode:</label>
                <input type="text" name="barcode" value="{{ $product->barcode }}" placeholder="Barcode"
                    class="form-control" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6 showhide" style="{{ $product->product_type == 'general_items' ? 'display:none' : '' }}">
            <div class="form-group">
                <label>Price:</label>
                <input type="text" name="price" value="{{ $product->price }}" placeholder="Price"
                    class="form-control" />
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option {{ $product->name == 'active' ? 'selected' : '' }} value="active">Active</option>
                    <option {{ $product->name == 'inactive' ? 'selected' : '' }} value="inactive">Inactive</option>
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
