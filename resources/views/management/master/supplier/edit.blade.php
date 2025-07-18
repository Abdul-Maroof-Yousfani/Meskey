 <form action="{{ route('supplier.update', $supplier->id) }}" method="POST" id="ajaxSubmit" autocomplete="off"
     enctype="multipart/form-data">
     @csrf
     @method('PUT')
     <input type="hidden" id="listRefresh" value="{{ route('get.supplier') }}" />

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Company Detail
             </h6>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Company Name:</label>
                 <input type="text" name="company_name" value="{{ old('company_name', $supplier->company_name) }}"
                     placeholder="Company Name" class="form-control" autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>NTN#: <small>(Optional)</small></label>
                 <input type="text" name="ntn" value="{{ old('ntn', $supplier->ntn) }}" placeholder="NTN No"
                     class="form-control" autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>STN#: <small>(Optional)</small></label>
                 <input type="text" name="stn" value="{{ old('stn', $supplier->stn) }}" placeholder="STN No"
                     class="form-control" />
             </div>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div id="card-container" class="mb-4">
                 @if (count($supplier->companyBankDetails) > 0)
                     @foreach ($supplier->companyBankDetails as $index => $bank)
                         <div class="clonecard border-1">
                             <hr>
                             <div class="row">
                                 <div class="col-xs-6 col-sm-6 col-md-6">
                                     <div class="form-group">
                                         <label>Bank Name:</label>
                                         <input type="text" name="company_bank_name[]"
                                             value="{{ old('company_bank_name.' . $index, $bank->bank_name) }}"
                                             placeholder="Bank Name" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-6 col-sm-6 col-md-6">
                                     <div class="form-group">
                                         <label>Branch Name:</label>
                                         <input type="text" name="company_branch_name[]"
                                             value="{{ old('company_branch_name.' . $index, $bank->branch_name) }}"
                                             placeholder="Branch Name" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-6 col-sm-6 col-md-6">
                                     <div class="form-group">
                                         <label>Branch Code:</label>
                                         <input type="text" name="company_branch_code[]"
                                             value="{{ old('company_branch_code.' . $index, $bank->branch_code) }}"
                                             placeholder="Branch Code" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-6 col-sm-6 col-md-6">
                                     <div class="form-group">
                                         <label>Account Title:</label>
                                         <input type="text" name="company_account_title[]"
                                             value="{{ old('company_account_title.' . $index, $bank->account_title) }}"
                                             placeholder="Account Title" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-12 col-sm-12 col-md-12">
                                     <div class="form-group">
                                         <label>Account Number:</label>
                                         <input type="text" name="company_account_number[]"
                                             value="{{ old('company_account_number.' . $index, $bank->account_number) }}"
                                             placeholder="Account number" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                                     <div>
                                         @if ($index === 0)
                                             <button type="button" class="btn btn-warning btn-icon add-more mr-1"><i
                                                     class="fa fa-plus"></i></button>
                                         @endif
                                         <button type="button" class="btn btn-danger btn-icon remove-card mr-1"><i
                                                 class="fa fa-trash"></i></button>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     @endforeach
                 @else
                     {{-- <div class="clonecard border-1">
                           <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                             <div>
                                 <button type="button" class="btn btn-warning btn-icon add-more mr-1">
                                     <i class="fa fa-plus"></i>
                                 </button>
                                 <button type="button" class="btn btn-danger btn-icon remove-card mr-1"
                                     style="display:none;">
                                     <i class="fa fa-trash"></i>
                                 </button>
                             </div>
                         </div> 
                     </div> --}}
                 @endif
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Link Existing Account:</label>
                 <select name="account_id" class="form-control">
                     <option value="">-- Create New Account --</option>
                     @foreach ($accounts as $account)
                         <option value="{{ $account->id }}"
                             {{ $supplier->account_id == $account->id ? 'selected' : '' }}>
                             {{ $account->name }} ({{ $account->unique_no }})
                         </option>
                     @endforeach
                 </select>
                 <small class="text-muted">Select an existing account or leave blank to keep current/new one</small>
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Owner Detail
             </h6>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Owner Name:</label>
                 <input type="text" name="owner_name" value="{{ old('owner_name', $supplier->owner_name) }}"
                     placeholder="Owner Name" class="form-control" autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Owner Mobile No:</label>
                 <input type="text" name="owner_mobile_no" placeholder="03001234567"
                     value="{{ old('owner_mobile_no', $supplier->owner_mobile_no) }}" class="form-control"
                     autocomplete="off" maxlength="11" />
                 <small class="text-muted">Enter 11 digit mobile number</small>
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Owner CNIC:</label>
                 <input type="text" name="owner_cnic_no" placeholder="12345-1234567-1"
                     value="{{ old('owner_cnic_no', $supplier->owner_cnic_no) }}" class="form-control cnic-input"
                     autocomplete="off" maxlength="15" />
                 <small class="text-muted">Format: 12345-1234567-1</small>
             </div>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div id="card-container2" class="mb-4">
                 @if (count($supplier->ownerBankDetails) > 0)
                     @foreach ($supplier->ownerBankDetails as $index => $bank)
                         <div class="clonecard2 border-1">
                             <hr>
                             <div class="row">
                                 <div class="col-xs-6 col-sm-6 col-md-6">
                                     <div class="form-group">
                                         <label>Bank Name:</label>
                                         <input type="text" name="owner_bank_name[]"
                                             value="{{ old('owner_bank_name.' . $index, $bank->bank_name) }}"
                                             placeholder="Bank Name" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-6 col-sm-6 col-md-6">
                                     <div class="form-group">
                                         <label>Branch Name:</label>
                                         <input type="text" name="owner_branch_name[]"
                                             value="{{ old('owner_branch_name.' . $index, $bank->branch_name) }}"
                                             placeholder="Branch Name" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-6 col-sm-6 col-md-6">
                                     <div class="form-group">
                                         <label>Branch Code:</label>
                                         <input type="text" name="owner_branch_code[]"
                                             value="{{ old('owner_branch_code.' . $index, $bank->branch_code) }}"
                                             placeholder="Branch Code" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-6 col-sm-6 col-md-6">
                                     <div class="form-group">
                                         <label>Account Title:</label>
                                         <input type="text" name="owner_account_title[]"
                                             value="{{ old('owner_account_title.' . $index, $bank->account_title) }}"
                                             placeholder="Account Title" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-12 col-sm-12 col-md-12">
                                     <div class="form-group">
                                         <label>Account Number:</label>
                                         <input type="text" name="owner_account_number[]"
                                             value="{{ old('owner_account_number.' . $index, $bank->account_number) }}"
                                             placeholder="Account number" class="form-control" autocomplete="off" />
                                     </div>
                                 </div>
                                 <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                                     <div>
                                         @if ($index === 0)
                                             <button type="button" class="btn btn-warning btn-icon add-more2 mr-1"><i
                                                     class="fa fa-plus"></i></button>
                                         @endif
                                         <button type="button" class="btn btn-danger btn-icon remove-card2 mr-1"><i
                                                 class="fa fa-trash"></i></button>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     @endforeach
                 @else
                     {{-- <div class="clonecard2 border-1">
                         <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                             <div>
                                 <button type="button" class="btn btn-warning btn-icon add-more2 mr-1">
                                     <i class="fa fa-plus"></i>
                                 </button>
                                 <button type="button" class="btn btn-danger btn-icon remove-card2 mr-1"
                                     style="display:none;">
                                     <i class="fa fa-trash"></i>
                                 </button>
                             </div>
                         </div> 
                     </div> --}}
                 @endif
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Next Of Kin
             </h6>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Name:</label>
                 <input type="text" name="next_to_kin" value="{{ old('next_to_kin', $supplier->next_to_kin) }}"
                     placeholder="Name" class="form-control" autocomplete="off" />
             </div>
         </div>
         <div class="col-xs-6 col-sm-6 col-md-6">
             <div class="form-group">
                 <label>Mobile No:</label>
                 <input type="text" name="next_to_kin_mobile_no" placeholder="03001234567" class="form-control"
                     value="{{ old('next_to_kin_mobile_no', $supplier->next_to_kin_mobile_no) }}" autocomplete="off"
                     maxlength="11" pattern="[0-9]{11}" />
                 <small class="text-muted">Enter 11 digit mobile number</small>
             </div>
         </div>
     </div>
     <div class="row form-mar">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Locations
             </h6>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             @foreach ($companyLocations as $location)
                 <div class="checkbox">
                     <input name="company_location_ids[]" type="checkbox" id="location_{{ $location->id }}"
                         value="{{ $location->id }}"
                         {{ in_array($location->id, $selectedLocations) ? 'checked' : '' }}>
                     <label for="location_{{ $location->id }}"><span>{{ $location->name }}</span></label>
                 </div>
             @endforeach
         </div>
     </div>

     <div class="d-none row form-mar">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Broker Option
             </h6>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="checkbox">
                 <input name="create_as_broker" type="checkbox" id="create_as_broker" value="1"
                     {{ $supplier->broker ? 'checked' : '' }}>
                 <label for="create_as_broker"><span>Create/Update this supplier as a broker too</span></label>
             </div>
         </div>
     </div>

     <div class="row">
         <div class="col-12">
             <h6 class="header-heading-sepration">
                 Other
             </h6>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Status:</label>
                 <select name="status" class="form-control">
                     <option value="active" {{ old('status', $supplier->status) == 'active' ? 'selected' : '' }}>
                         Active</option>
                     <option value="inactive" {{ old('status', $supplier->status) == 'inactive' ? 'selected' : '' }}>
                         Inactive</option>
                 </select>
             </div>
         </div>
         <div class="col-xs-12 col-sm-12 col-md-12">
             <div class="form-group">
                 <label>Address:</label>
                 <textarea name="address" rows="2" class="form-control" placeholder="Supplier Address">{{ old('address', $supplier->address) }}</textarea>
             </div>
         </div>
     </div>

     <div class="row bottom-button-bar">
         <div class="col-12">
             <a href="{{ route('supplier.index') }}"
                 class="btn btn-danger position-relative top-1 closebutton">Close</a>
             <button type="submit" class="btn btn-primary submitbutton">Update</button>
         </div>
     </div>
 </form>

 <script>
     $(document).ready(function() {

         $(document).on('input', '.cnic-input', function() {
             let value = $(this).val().replace(/\D/g, '');
             let formattedValue = '';

             if (value.length > 0) {
                 formattedValue = value.substring(0, 5);
             }
             if (value.length > 5) {
                 formattedValue += '-' + value.substring(5, 12);
             }
             if (value.length > 12) {
                 formattedValue += '-' + value.substring(12, 13);
             }

             $(this).val(formattedValue);
         });

         if ($('#card-container .clonecard').length === 0) {
             var newCard = $(`
            <div class="clonecard border-1">
                <hr>
                <div class="row">
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group">
                            <label>Bank Name:</label>
                            <input type="text" name="company_bank_name[]" placeholder="Bank Name" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group">
                            <label>Branch Name:</label>
                            <input type="text" name="company_branch_name[]" placeholder="Branch Name" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group">
                            <label>Branch Code:</label>
                            <input type="text" name="company_branch_code[]" placeholder="Branch Code" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group">
                            <label>Account Title:</label>
                            <input type="text" name="company_account_title[]" placeholder="Account Title" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label>Account Number:</label>
                            <input type="text" name="company_account_number[]" placeholder="Account number" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                        <div>
                            <button type="button" class="btn btn-warning btn-icon add-more mr-1"><i class="fa fa-plus"></i></button>
                            <button type="button" class="btn btn-danger btn-icon remove-card mr-1" style="display:none;"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        `);
             $('#card-container').append(newCard);
         }

         if ($('#card-container2 .clonecard2').length === 0) {
             var newCard2 = $(`
            <div class="clonecard2 border-1">
                <hr>
                <div class="row">
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group">
                            <label>Bank Name:</label>
                            <input type="text" name="owner_bank_name[]" placeholder="Bank Name" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group">
                            <label>Branch Name:</label>
                            <input type="text" name="owner_branch_name[]" placeholder="Branch Name" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group">
                            <label>Branch Code:</label>
                            <input type="text" name="owner_branch_code[]" placeholder="Branch Code" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-6 col-sm-6 col-md-6">
                        <div class="form-group">
                            <label>Account Title:</label>
                            <input type="text" name="owner_account_title[]" placeholder="Account Title" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-12 col-sm-12 col-md-12">
                        <div class="form-group">
                            <label>Account Number:</label>
                            <input type="text" name="owner_account_number[]" placeholder="Account number" class="form-control" autocomplete="off"/>
                        </div>
                    </div>
                    <div class="col-xs-2 col-sm-2 col-md-2 d-flex align-items-end">
                        <div>
                            <button type="button" class="btn btn-warning btn-icon add-more2 mr-1"><i class="fa fa-plus"></i></button>
                            <button type="button" class="btn btn-danger btn-icon remove-card2 mr-1" style="display:none;"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        `);
             $('#card-container2').append(newCard2);
         }

         $(document).on('click', '.add-more', function() {
             var newCard = $('#card-container .clonecard:first').clone();
             newCard.find('input').val('');
             $('#card-container').append(newCard);
             toggleRemoveButton();
         });

         $(document).on('click', '.remove-card', function() {
             if ($('#card-container .clonecard').length > 1) {
                 $(this).closest('.clonecard').remove();
                 toggleRemoveButton();
             }
         });

         $(document).on('click', '.add-more2', function() {
             var newCard = $('#card-container2 .clonecard2:first').clone();
             newCard.find('input').val('');
             $('#card-container2').append(newCard);
             toggleRemoveButton2();
         });

         $(document).on('click', '.remove-card2', function() {
             if ($('#card-container2 .clonecard2').length > 1) {
                 $(this).closest('.clonecard2').remove();
                 toggleRemoveButton2();
             }
         });

         function toggleRemoveButton() {
             $('.clonecard').each(function(index) {
                 $(this).find('.remove-card').toggle($('#card-container .clonecard').length > 1);
             });
         }

         function toggleRemoveButton2() {
             $('.clonecard2').each(function(index) {
                 $(this).find('.remove-card2').toggle($('#card-container2 .clonecard2').length > 1);
             });
         }

         toggleRemoveButton();
         toggleRemoveButton2();
     });
 </script>
