<div class="sidenav-overlay"></div>
<div class="drag-target"></div>

<!-- BEGIN: Footer-->
    <!-- <footer class="footer footer-static footer-light">
        <p class="clearfix mb-0"><span class="float-md-left d-block d-md-inline-block mt-25">COPYRIGHT &copy; 2023<a class="ml-25" href="#" target="_blank">INPL</a><span class="d-none d-sm-inline-block">, All rights Reserved</span></span>    </footer>
    <button class="btn btn-primary btn-icon scroll-top" type="button"><i data-feather="arrow-up"></i></button> -->
<!-- END: Footer-->

<!-- Edit Column Modal -->
<div class="modal fade" id="new-folder-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Field</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mab" placeholder="Search" />
                <button type="button" class="btn btn-black mr-1">Lead Fields</button>
                <div class="mt">

                    <div class="title-wrapper bor-b">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="customCheck1">
                            <label class="custom-control-label" for="customCheck1"></label>
                            <span class="todo-title">Account</span>
                        </div>
                    </div>

                    <div class="title-wrapper bor-b">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="customCheck2">
                            <label class="custom-control-label" for="customCheck2"></label>
                            <span class="todo-title">Account Name</span>
                        </div>
                    </div>

                    <div class="title-wrapper bor-b">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="customCheck3">
                            <label class="custom-control-label" for="customCheck3"></label>
                            <span class="todo-title">Address</span>
                        </div>
                    </div>


                    <div class="title-wrapper bor-b">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="customCheck4">
                            <label class="custom-control-label" for="customCheck4"></label>
                            <span class="todo-title">Address Type</span>
                        </div>
                    </div>

                    <div class="title-wrapper bor-b">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="customCheck5">
                            <label class="custom-control-label" for="customCheck5"></label>
                            <span class="todo-title">Annual Revnue</span>
                        </div>
                    </div>

                    <div class="title-wrapper bor-b">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="customCheck6">
                            <label class="custom-control-label" for="customCheck6"></label>
                            <span class="todo-title">budget</span>
                        </div>
                    </div>


                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary mr-1" data-dismiss="modal">Select</button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Edit Column Modal Ends -->


<!-- Add Campaign Title Modal --> 
<div class="modal fade" id="add-campaign-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Campaign Title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="form-group">
                            <label for="account-name">CRM Email Campaign</label>
                            <input type="text" class="form-control" id="account-name" name="name" placeholder="CRM Email Campaign">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <!-- <button type="button" class="btn btn-primary mr-1" >Create Campaign</button> -->
                <a class="btn btn-primary mr-1" href="{{route('emailcampaign')}}">Create Campaign</a>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Add Campaign Title Modal End -->




<!-- Add Contect Modal -->
<div class="modal fade" id="add-contact-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Contact</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="account-username">First Name</label>
                            <input type="text" class="form-control" id="account-username" name="username" placeholder="First Name">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="account-name">Last Name</label>
                            <input type="text" class="form-control" id="account-name" name="name" placeholder="Last Name">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="account-username">Contact No:</label>
                            <input type="text" class="form-control" id="account-username" name="username" placeholder="Contact No:">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="account-name">Email</label>
                            <input type="email" class="form-control" id="account-name" name="name" placeholder="Email">
                        </div>
                    </div>
                    <div class="col-12 col-sm-12">
                        <div class="form-group">
                            <label for="account-name">Company</label>
                            <input type="email" class="form-control" id="account-name" name="name" placeholder="Company">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary mr-1" data-dismiss="modal">Save</button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Add Contect Modal End -->

<!-- Import Leads Modal -->
<div class="modal fade" id="import-leads-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Leads</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <ul class="import-lead-ul">
                            <li class="first">
                                <img src="../theme/assets/images/a1.png" alt="">
                            </li>
                            <li>
                                <h1>Import</h1>
                                <p>Import contact, company, deal, ticket, or product information into CRM</p>
                            </li>
                            <li class="text-right">
                                <a href="#" class="btn-a">Start on import</a>
                            </li>
                        </ul>
                        <ul class="import-lead-ul">
                            <li class="first">
                                <img src="../theme/assets/images/a2.png" alt="">
                            </li>
                            <li>
                                <h1>Sync</h1>
                                <p>Sync data between CRM and dozens of other apps.</p>
                            </li>
                            <li class="text-right">
                                <a href="#" class="btn-a">Connect an app</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Import Leads Modal End -->

<!-- Facebook Campaign Modal --> 
<div class="modal fade" id="facebook-campaign-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body bg-blue">
                <button type="button" class="close cust-close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="row align-items-center c-pad">
                    <div class="col-md-5">
                        <img src="../theme/assets/images/face-popup.png" alt="">
                    </div>
                    <div class="col-md-7 text-w">
                        <h1>Track your Facebook campaigns performance and capture leads in one place</h1>
                        <p>Connecting your Facebook Ads account will enable you to: 
                            </br>
                            <span>1- Import your existing campaign data and automatically sync it to your board on a regular basis.</span>
                            </br>
                            <span>2- capture leads directly from your lead generation campaigns into your boards.</span>
                        </p>
                        <p>Please note: your Facebook Ads account must be a business account in order to use this integration.</p>
                        <button type="button" class="btn btn-cus" data-dismiss="modal">Connect</button>
                    </div>    
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Facebook Campaign Modal End -->

<!-- Add Note Modal --> 
<div class="modal fade" id="add-note-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Note</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-sm-12">
                                <div id="snow-wrapper">
                                    <div id="snow-container">
                                        <div class="quill-toolbar">
                                            <span class="ql-formats">
                                                <select class="ql-header">
                                                    <option value="1">Heading</option>
                                                    <option value="2">Subheading</option>
                                                    <option selected>Normal</option>
                                                </select>
                                                <select class="ql-font">
                                                    <option selected>Sailec Light</option>
                                                    <option value="sofia">Sofia Pro</option>
                                                    <option value="slabo">Slabo 27px</option>
                                                    <option value="roboto">Roboto Slab</option>
                                                    <option value="inconsolata">Inconsolata</option>
                                                    <option value="ubuntu">Ubuntu Mono</option>
                                                </select>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-bold"></button>
                                                <button class="ql-italic"></button>
                                                <button class="ql-underline"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-list" value="ordered"></button>
                                                <button class="ql-list" value="bullet"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-link"></button>
                                                <button class="ql-image"></button>
                                                <button class="ql-video"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-formula"></button>
                                                <button class="ql-code-block"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-clean"></button>
                                            </span>
                                        </div>
                                        <div class="editor">
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Add Note Modal End -->


<!-- Add Activity Modal --> 
<div class="modal fade" id="add-activity-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Activity</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="account-username">Activity Title</label>
                            <input type="text" class="form-control" id="account-username" name="username" placeholder="Activity Title">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="account-name">Activity Type</label>
                            <input type="text" class="form-control" id="account-name" name="name" placeholder="Activity Type">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="account-username">Comments</label>
                            <input type="text" class="form-control" id="account-username" name="username" placeholder="Comments">
                        </div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="form-group">
                            <label for="account-name">Assigned To</label>
                            <input type="email" class="form-control" id="account-name" name="name" placeholder="Assigned To">
                        </div>
                    </div>
                    <div class="col-12 col-sm-12">
                        <div class="form-group">
                            <label for="account-name">Time</label>
                            <input type="time" class="form-control" id="account-name" name="name" placeholder="Time">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary mr-1" data-dismiss="modal">Save</button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Add Activity Modal End -->


<!-- Create Task Modal --> 
<div class="modal fade" id="create-task-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Task</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="form-group">
                            <textarea class="form-control" cols="30" rows="10"></textarea>
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="form-group">
                            <label for="account-name">Assigned To</label>
                            <input type="text" class="form-control" id="account-name" name="name" placeholder="Assigned To">
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="form-group">
                            <label for="account-username">Watcher</label>
                            <input type="text" class="form-control" id="account-username" name="username" placeholder="Watcher">
                        </div>
                    </div>
                    <div class="col-12 col-sm-4">
                        <div class="form-group">
                            <label for="account-name">Deadline</label>
                            <input type="calender" class="form-control" id="account-name" name="name" placeholder="Deadline">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary mr-1" data-dismiss="modal">Save</button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Create Task Modal End -->

<!-- Change theme Modal --> 
<div class="modal fade" id="change-theme-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Themes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <ul class="theme-u">
                        <li id="imageOne">
                            <img src="../theme/assets/images/themes/01.png" alt="">
                        </li>
                        <li id="imageTwo">
                            <img src="../theme/assets/images/themes/02.png" alt="">
                        </li>
                        <li id="imageThree">
                            <img src="../theme/assets/images/themes/03.png" alt="">
                        </li>
                        <li id="imageFour">
                            <img src="../theme/assets/images/themes/04.png" alt="">
                        </li>
                        <li id="imageFive">
                            <img src="../theme/assets/images/themes/05.png" alt="">
                        </li>
                        <li id="imageSix">
                            <img src="../theme/assets/images/themes/06.png" alt="">
                        </li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary mr-1" data-dismiss="modal" onclick="showText()">Save</button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Change theme Modal End -->


<!-- Add Complete Contect Modal -->
<div class="modal fade" id="add-completecontact-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Contact</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="tab-content">
                    <div class="alert alert-success">
                </div>
                <div class="alert alert-danger hide  print-error-msg">
                    <ul></ul>
                </div>
                <span id="response">
                    <div role="tabpanel" class="tab-pane active" id="account-vertical-general" aria-labelledby="account-pill-general" aria-expanded="true">
                        <form id="subm" method="post" action="http://127.0.0.1:8000/usersSetting/profile_update/1" enctype="multipart/form-data" class="validate-form mt-2">
                            <input type="hidden" name="_token" value="Deo1TjpnYVPwUSc9MnYbZO10C9dBl2d1dtS7mSRX" autocomplete="off">                                        <!-- header media -->
                            <div class="media">
                                <a href="javascript:void(0);" class="mr-25">

                                    <img src="http://127.0.0.1:8000" id="account-upload-img" class="rounded mr-50" alt="profile image" height="80" width="80">
                                </a>
                                <!-- upload and reset button -->
                                <div class="media-body mt-75 ml-1">
                                    <label for="account-upload" class="btn btn-sm btn-primary mb-75 mr-75">Upload</label>
                                    <input type="file" id="account-upload" name="file" onchange="previewImage(event)" hidden="" accept="image/*">
                                    <button class="btn btn-sm btn-outline-secondary mb-75" onclick="resetForm()">Reset</button>
                                    <p>Allowed JPG, GIF or PNG. Max size of 800kB</p>
                                </div>
                                <!--/ upload and reset button -->
                            </div>
                            <!--/ header media -->

                            <!-- form -->

                                <div class="row">
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label for="account-username">First Name</label>
                                            <input type="text" class="form-control" id="" name="" value="" placeholder="First Name">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label for="account-name">Last Name</label>
                                            <input type="text" class="form-control" id="" name="" value="" placeholder="Last Name">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label for="account-name">Date of Birth</label>
                                            <input type="date" class="form-control" id="" name="" value="">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label for="account-e-mail">Position / Job Title</label>
                                            <input readonly="" type="email" class="form-control" id="" name="" value="" placeholder="Position / Job Title">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label for="account-name">Phone Number</label>
                                            <input type="text" class="form-control" id="" name="" value="" placeholder="Phone Number">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label for="account-name">Email</label>
                                            <input type="email" class="form-control" id="" name="" value="" placeholder="Email">
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label for="account-name">Contact Type</label>
                                            <select name="" id="" class="form-control">
                                                <option value="">01</option>
                                                <option value="">02</option>
                                                <option value="">03</option>
                                                <option value="">04</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label for="account-name">Company</label>
                                            <select name="" id="" class="form-control">
                                                <option value="">01</option>
                                                <option value="">02</option>
                                                <option value="">03</option>
                                                <option value="">04</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-4">
                                        <div class="form-group">
                                            <label for="account-name">Option</label>
                                            <select name="" id="" class="form-control">
                                                <option value="">01</option>
                                                <option value="">02</option>
                                                <option value="">03</option>
                                                <option value="">04</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <!--/ form -->
                        </div>
                    </span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary mr-1" data-dismiss="modal">Save</button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Add Complete Contect Modal Ends -->



<!-- Advance Setting Modal -->
<div class="modal fade" id="advance-setting-modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Advance Settings</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="demo-inline-spacing">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio1" value="option1" checked="">
                        <label class="form-check-label" for="inlineRadio1">Current Mode</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="inlineRadioOptions" id="inlineRadio2" value="option2">
                        <label class="form-check-label" for="inlineRadio2">Even Mode</label>
                    </div>
                </div>
                <div class="mt">
                    <h3>Automatically Distribute New Leads to:</h3>
                    <div class="col-12 col-sm-12">
                        <div class="form-group">
                            <label for="account-username">Lead Source</label>
                            <input type="text" class="form-control" id="" name="" value="" placeholder="Lead Source">
                        </div>
                    </div>
                    <div class="row">
                        <table class="table table-hover">
                            <tbody>
                                <tr>
                                    <th class="text-center">Users</th>
                                    <th class="text-center">Email ID</th>
                                    <th class="text-center">Action</th>
                                </tr>
                                <tr>
                                    <td class="text-center">Amir</td>
                                    <td class="text-center">amirmurshad@gmail.com</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow waves-effect waves-float waves-light" data-toggle="dropdown" aria-expanded="false">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-vertical"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                            </button>
                                            <div class="dropdown-menu" style="">
                                                <a class="dropdown-item" href="#">
                                                    <i class="fa-regular fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">Amir</td>
                                    <td class="text-center">amirmurshad@gmail.com</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow waves-effect waves-float waves-light" data-toggle="dropdown" aria-expanded="false">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-vertical"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                            </button>
                                            <div class="dropdown-menu" style="">
                                                <a class="dropdown-item" href="#">
                                                    <i class="fa-regular fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">Amir</td>
                                    <td class="text-center">amirmurshad@gmail.com</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow waves-effect waves-float waves-light" data-toggle="dropdown" aria-expanded="false">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-vertical"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                            </button>
                                            <div class="dropdown-menu" style="">
                                                <a class="dropdown-item" href="#">
                                                    <i class="fa-regular fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-center">Amir</td>
                                    <td class="text-center">amirmurshad@gmail.com</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow waves-effect waves-float waves-light" data-toggle="dropdown" aria-expanded="false">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-vertical"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                            </button>
                                            <div class="dropdown-menu" style="">
                                                <a class="dropdown-item" href="#">
                                                    <i class="fa-regular fa-eye"></i>
                                                    <span>View</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary mr-1" data-dismiss="modal">Select</button>
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<!-- Advance Setting Modal Ends -->




<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
    $(document).ready(function(){

var current_fs, next_fs, previous_fs; //fieldsets
var opacity;
var current = 1;
var steps = $("fieldset").length;

setProgressBar(current);

$(".next").click(function(){

current_fs = $(this).parent();
next_fs = $(this).parent().next();

//Add Class Active
$("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active");

//show the next fieldset
next_fs.show();
//hide the current fieldset with style
current_fs.animate({opacity: 0}, {
step: function(now) {
// for making fielset appear animation
opacity = 1 - now;

current_fs.css({
'display': 'none',
'position': 'relative'
});
next_fs.css({'opacity': opacity});
},
duration: 500
});
setProgressBar(++current);
});

$(".previous").click(function(){

current_fs = $(this).parent();
previous_fs = $(this).parent().prev();

//Remove class active
$("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

//show the previous fieldset
previous_fs.show();

//hide the current fieldset with style
current_fs.animate({opacity: 0}, {
step: function(now) {
// for making fielset appear animation
opacity = 1 - now;

current_fs.css({
'display': 'none',
'position': 'relative'
});
previous_fs.css({'opacity': opacity});
},
duration: 500
});
setProgressBar(--current);
});

function setProgressBar(curStep){
var percent = parseFloat(100 / steps) * curStep;
percent = percent.toFixed();
$(".progress-bar")
.css("width",percent+"%")
}

$(".submit").click(function(){
return false;
})

});
</script>
<script>
    function showText(){
        console.log('script working');
        let element = document.getElementsByClassname('defaultBody')[0];
        element.style.backgroundImage = "url('../images/themes/01.png')"
        localstorage.setItem('BgImage', '../images/themes/01.png');
        console.log('till here');
      }
    $(document).ready(function() {
        let defaultBgImage = localStorage.getItem("BgImage");
        if(defaultBgImage) {
            $('body').css("background-image", `url(${defaultBgImage})`)
        }
        $('#imageOne').on('click', function(){
            localStorage.setItem('BgImage', '../theme/assets/images/themes/01.png');
            $('body').css("background-image", "url('../theme/assets/images/themes/01.png')")
        });
        $('#imageTwo').on('click', function(){
            localStorage.setItem('BgImage', '../theme/assets/images/themes/02.png');
            $('body').css("background-image", "url('../theme/assets/images/themes/02.png')")
        });
        $('#imageThree').on('click', function(){
            localStorage.setItem('BgImage', '../theme/assets/images/themes/03.png');
            $('body').css("background-image", "url('../theme/assets/images/themes/03.png')")
        });
        $('#imageFour').on('click', function(){
            localStorage.setItem('BgImage', '../theme/assets/images/themes/04.png');
            $('body').css("background-image", "url('../theme/assets/images/themes/04.png')")
        });
        $('#imageFive').on('click', function(){
            localStorage.setItem('BgImage', '../theme/assets/images/themes/05.png');
            $('body').css("background-image", "url('../theme/assets/images/themes/05.png')")
        });
        $('#imageSix').on('click', function(){
            localStorage.setItem('BgImage', '../theme/assets/images/themes/06.png');
            $('body').css("background-image", "url('../theme/assets/images/themes/06.png')")
        });

});
</script>
<script src="https://polyfill.io/v3/polyfill.js?features=default"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB41DRUbKWJHPxaFjMAwdrzWzbVKartNGg&callback=initAutocomplete&libraries=places&v=weekly"
    defer>
</script>
