<div class="row form-mar">
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group ">
            <label>Name:</label>
            {{ $role->name }}
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group">
            <label>Permissions:</label>
            @foreach($permission as $value)
                @if($value->parent_id === null)
                    <div class="permission-container">


                        <label>{{ Form::checkbox('permission[]', $value->name, in_array($value->id, $rolePermissions) ? true : false, array('class' => 'parent','disabled' => 'disabled')) }}
                            {{ $value->name }}</label>
                        <br>
                        <div class="sub-permissions">
                            @foreach($permission as $subPermission)
                                @if($subPermission->parent_id == $value->id)
                                    <label class="pl-3">{{ Form::checkbox('permission[]', $subPermission->name, in_array($subPermission->id, $rolePermissions) ? true : false, array('class' => 'child','disabled' => 'disabled')) }}
                                        {{ $subPermission->name }}</label>
                                    <br/>

                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>
<div class="row text-center center">
    <div class="col-12">
        <button type="button" class="btn btn-danger" data-close="model">Cancel</button>
    </div>
</div>
