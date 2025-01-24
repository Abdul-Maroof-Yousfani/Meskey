{!! Form::open(['route' => 'roles.store', 'method' => 'POST', 'id' => 'ajaxSubmit']) !!}
<input type="hidden" id="listRefresh" value="{{ route('get.roles') }}" />

<div class="row form-mar">
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group ">
            <label>Name:</label>
            {{-- {!! Form::text('name[]', null, array('placeholder' => 'Name','class' => 'form-control')) !!} --}}
            {!! Form::text('name', null, ['placeholder' => 'Name', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="col-xs-12 col-sm-12 col-md-12">
        <div class="form-group errorappend">
            <label>Description:</label>
            {!! Form::textarea('description', null, [
                'placeholder' => 'Description',
                'class' => 'form-control',
                'rows' => 3,
            ]) !!}
        </div>
    </div>
<div class="col-xs-12 col-sm-12 col-md-12">
    <div class="form-group">
        <label>Permission:</label>
        <br />
        @foreach ($permission as $parent)
            @if ($parent->parent_id === null)
                <div class="permission-container">
                    <!-- Parent Permission -->
                    <div class="custom-accordion row w-100 mx-auto py-0">
                        <label class="col-11 d-flex align-items-center py-2 text-uppercase" style="font-size:14px;gap:4px">
                            {{ Form::checkbox('permission[]', $parent->name, false, ['class' => 'parent']) }}
                            <span>{{ $parent->name }}</span>
                        </label>
                        @if ($permission->where('parent_id', $parent->id)->count() > 0)
                            <span class="material-symbols-outlined pl-3 col-1 text-right d-flex align-items-center border-left">
                                <i class="ft-chevron-down"></i>
                            </span>
                        @endif
                    </div>
                    <!-- Sub-Permissions -->
                    <div class="sub-permissions" style="display: none;">
                        @foreach ($permission->where('parent_id', $parent->id) as $child)
                            <div class="child-container">
                                <label class="pl-4 py-2">
                                    {{ Form::checkbox('permission[]', $child->name, false, ['class' => 'child']) }}
                                    <span>{{ $child->name }}</span>
                                </label>
                                
                                <!-- Recursive Check for Nested Permissions -->
                                @if ($permission->where('parent_id', $child->id)->count() > 0)
                                    <div class="sub-permissions sub" style="display: ;">
                                        @foreach ($permission->where('parent_id', $child->id) as $subChild)
                                            <div class="child-container">
                                                <label class="pl-5 py-2">
                                                    {{ Form::checkbox('permission[]', $subChild->name, false, ['class' => 'child']) }}
                                                    <span>{{ $subChild->name }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>


</div>
<div class="row bottom-button-bar">
    <div class="col-12">
        <a type="button" class="btn btn-danger modal-sidebar-close position-relative top-1 closebutton" >Close</a>
        <button type="submit" class="btn btn-primary submitbutton">Save</button>
    </div>
</div>
{!! Form::close() !!}

<script>
$(document).ready(function () {
    // Toggle sub-permissions visibility
   $(document).on('click', '.custom-accordion .material-symbols-outlined', function () {
        $(this).closest('.custom-accordion').next('.sub-permissions').slideToggle();
        $(this).find('i').toggleClass('ft-chevron-down ft-chevron-up');
    });

    // Handle parent checkbox changes
    $('.parent').change(function () {
        var isChecked = $(this).prop('checked');
        $(this).closest('.permission-container')
            .find('.sub-permissions .child')
            .prop('checked', isChecked)
            .trigger('change'); // Update child states
    });

    // Handle child checkbox changes
    $('.child').change(function () {
        var $container = $(this).closest('.permission-container');
        var $parentCheckbox = $container.find('.parent:first');

        // Update parent checkbox state
        var anyChecked = $container.find('.sub-permissions .child:checked').length > 0;
        $parentCheckbox.prop('checked', anyChecked);

        // Traverse up to ensure all ancestor parents are checked
        updateParentCheckboxes($parentCheckbox);
    });

    // Recursive function to update ancestor parents
    function updateParentCheckboxes($checkbox) {
        var $subPermissions = $checkbox.closest('.sub-permissions');
        var $higherParentCheckbox = $subPermissions.closest('.permission-container').find('.parent:first');

        if ($higherParentCheckbox.length) {
            $higherParentCheckbox.prop('checked', true);
            updateParentCheckboxes($higherParentCheckbox);
        }
    }
});






   // $(document).ready(function() {
 //       $('.parent').change(function() {
    //        $(this).parents('.permission-container').find('.sub-permissions').find('.child').prop(
      //          'checked', $(this).prop('checked'));
    //    });
  //      $('.child').change(function() {
      //      var parentCheckbox = $(this).closest('.permission-container').find('.parent');
    //        parentCheckbox.prop('checked', $(this).closest('.sub-permissions').find('.child:checked')
    //            .length > 0);
  //      });
 //   });
   // $('.custom-accordion > span').on('click', function() {
   //     $(this).parents('.custom-accordion').siblings('.sub-permissions').toggle('slow');
   // })
</script>
