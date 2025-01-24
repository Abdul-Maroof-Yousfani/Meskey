<form action="{{ route('menu.store') }}" method="POST" id="ajaxSubmit" autocomplete="off">
    @csrf
    <input type="hidden" id="listRefresh" value="{{ route('get.menu') }}" />

    <div class="row form-mar">
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Parent:</label>
                <select name="parent_id" class="form-control">
                    <option value="">Select Parent Menu</option>
                    @foreach ($menus as $menu)
                        @if ($menu->parent_id == null) {{-- Check for top-level menu --}}
                            <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                            @foreach ($menus as $submenu)
                                @if ($submenu->parent_id == $menu->id) {{-- Check for child menus --}}
                                    <option value="{{ $submenu->id }}">-- {{ $submenu->name }}</option>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Permission:</label>
                <select name="permission_id" class="form-control">
                    <option value="">Assign Permission </option>
                    @foreach ($permissions as $permission)
                        <option value="{{ $permission->id }}">{{ strtoupper($permission->name) }}</option>
                    @endforeach

                </select>
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Name:</label>
                <input type="text" name="name" placeholder="Name" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Route:</label>
                <input type="text" name="route" placeholder="i.e acl/menu" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>icon Code (Font Awesome):</label>
                <input type="text" name="icon" placeholder="i.e ft-home" class="form-control" autocomplete="off" />
            </div>
        </div>
        <div class="col-xs-6 col-sm-6 col-md-6">
            <div class="form-group ">
                <label>Status:</label>
                <select name="status" class="form-control">
                    <option value="1">Active</option>
                    <option value="0">In-Active</option>
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