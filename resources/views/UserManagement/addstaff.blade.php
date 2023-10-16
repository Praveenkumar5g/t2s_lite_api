@extends('layouts.master')

@section('content')
	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-6">
						<h1 class="m-0">User Management</h1>
					</div><!-- /.col -->
					<div class="col-sm-6">
						<ol class="breadcrumb float-sm-right">
							<li class="breadcrumb-item"><a href="javascript:void(0);">User Management</a></li>
							<li class="breadcrumb-item active"><a href="{{url('usermanagement/staffs')}}">Staffs</a></li>
						</ol>
					</div><!-- /.col -->
				</div><!-- /.row -->
			</div><!-- /.container-fluid -->

			<!-- Main content -->
		    <section class="content">
		      	<div class="container-fluid">
		      		@if ($message = Session::get('error'))
						<div class="alert alert-danger alert-block">
							<button type="button" class="close" data-dismiss="alert">×</button>	
						        <strong>{{$message}}</strong>
						</div>
					@endif

					@if ($message = Session::get('success'))
						<div class="alert alert-success alert-block">
							<button type="button" class="close" data-dismiss="alert">×</button>	
						        <strong>{{$message}}</strong>
						</div>
					@endif
		        	<div class="row">
		          		<div class="col-12">
		            		<div class="card">
		              			<div class="card-header">
		                			<h3 class="card-title">Add Staff</h3>
		                		</div>
		              			<div class="card-body">
		              				<form name="addstaff" id="addstaff" method="post" action="{{url('usermanagement/storeStaff')}}" enctype="multipart/form-data">
									@csrf
		              					<div class="row">
			              					<div class="form-group col-4">
												<label>Name <span class="mandatory_field">*</span> </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="staff_name" id="staff_name" placeholder="Staff Name">
												</div>	
											</div>

											<div class="form-group col-4">
												<label>User Category <span class="mandatory_field">*</span></label>
							                  	<select class="custom-select input-group" id="user_category" name="user_category">
							                  		<option value=''>Select User Category</option>
								                    @foreach($user_category as $user_category_key => $user_category_value)
								                    	<option value="{{$user_category_value['id']}}">{{$user_category_value['category_name']}}</option>
								                    @endforeach
							                  	</select>
											</div>
											
											<div class="form-group col-4 teaching">
												<label>Division Name <span class="mandatory_field">*</span></label>
							                  	<select class="custom-select input-group" id="division_name" name="division_name">
							                  		<option value=''>Select Division Name</option>
								                    @foreach($division as $division_key => $division_value)
								                    	<option value="{{$division_value['id']}}">{{$division_value['division_name']}}</option>
								                    @endforeach
							                  	</select>
											</div>


											<div class="form-group col-4">
												<label>Mobile No <span class="mandatory_field">*</span></label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="mobile_number" id="mobile_number" placeholder="Mobile No">
												</div>
											</div>

											<div class="form-group col-4">
												<label>Email Address </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="email_address" id="email_address" placeholder="Email Address">
												</div>
											</div>

											<div class="form-group col-4 teaching">
												<label>Specialized In</label>
							                  	<select class="custom-select input-group" id="specialized_in" name="specialized_in">
							                  		<option value=''>Select Specialized In</option>
								                    
							                  	</select>
											</div>

											<div class="form-group col-4 teaching">
												<label>Department</label>
							                  	<select class="custom-select input-group" id="department" name="department">
							                  		<option value=''>Select Department</option>
								                    
							                  	</select>
											</div>

											<div class="form-group col-4">
												<label>Employee No <span class="mandatory_field">*</span></label>
												<div class="input-group">
													<input type="text" id="employee_no" class="form-control form-control-1 input-sm" name="employee_no" placeholder="Employee No" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Select DOB: <span class="mandatory_field">*</span></label>
												<div class="input-group">
													<input type="text" id="dob" class="form-control form-control-1 input-sm dob" name="dob" placeholder="DOB" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Select DOJ: <span class="mandatory_field">*</span></label>
												<div class="input-group">
													<input type="text" id="doj" class="form-control form-control-1 input-sm doj" name="doj" placeholder="DOJ" >
												</div>
											</div>
																					
											<div class="form-group col-4">
												<label>Religion</label>
												<div class="input-group">
													<input type="text" id="religion" class="form-control form-control-1 input-sm" name="religion" placeholder="Religion" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Caste & Community</label>
												<div class="input-group">
													<input type="text" id="caste_community" class="form-control form-control-1 input-sm" name="caste_community" placeholder="Caste & Community" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Native</label>
												<div class="input-group">
													<input type="text" id="native" class="form-control form-control-1 input-sm" name="native" placeholder="Native" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Bank Branch</label>
												<div class="input-group">
													<input type="text" id="bank_branch" class="form-control form-control-1 input-sm" name="bank_branch" placeholder="Bank Branch" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>ESI No</label>
												<div class="input-group">
													<input type="text" id="esi_no" class="form-control form-control-1 input-sm" name="esi_no" placeholder="ESI" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Oasis No</label>
												<div class="input-group">
													<input type="text" id="oasis_no" class="form-control form-control-1 input-sm" name="oasis_no" placeholder="Oasis No" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>EMIS No</label>
												<div class="input-group">
													<input type="text" id="emis_no" class="form-control form-control-1 input-sm" name="emis_no" placeholder="EMIS No" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Profile Image:</label>
												<div class="input-group">
													<input class="form-control" name="profile_image" type="file" id="profile_image">
												</div>
												<div class="holder" style="display:none;">
									                <img id="imgPreview" src="#" alt="pic"  style="max-width: 100px;max-height: 100px;min-width: 100px;min-height: 100px;" />
									            </div>
											</div>

											<div class="form-group col-4">
												<label>Aadhar No</label>
												<div class="input-group">
													<input type="text" id="aadhar_no" class="form-control form-control-1 input-sm" name="aadhar_no" placeholder="Aadhar No" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Aadhar:</label>
												<div class="input-group">
													<input class="form-control" name="aadhar" type="file" id="aadhar">
												</div>
												<div class="aadharholder" style="display:none;">
									                <img id="aadharimgPreview" src="#" alt="pic"  style="max-width: 100px;max-height: 100px;min-width: 100px;min-height: 100px;" />
									            </div>
											</div>

											<div class="form-group col-4">
												<label>PanCard No</label>
												<div class="input-group">
													<input type="text" id="pan_card_no" class="form-control form-control-1 input-sm" name="pan_card_no" placeholder="Pancard No" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Pan Card:</label>
												<div class="input-group">
													<input class="form-control" name="pan_card" type="file" id="pan_card">
												</div>
												<div class="pan_cardholder" style="display:none;">
									                <img id="pan_cardimgPreview" src="#" alt="pic"  style="max-width: 100px;max-height: 100px;min-width: 100px;min-height: 100px;" />
									            </div>
											</div>

											<div class="form-group col-4">
												<label>Account No</label>
												<div class="input-group">
													<input type="text" id="account_no" class="form-control form-control-1 input-sm" name="account_no" placeholder="Account No" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Bank PassBook:</label>
												<div class="input-group">
													<input class="form-control" name="bank_passbook" type="file" id="bank_passbook">
												</div>
												<div class="bank_passbookholder" style="display:none;">
									                <img id="bank_passbookimgPreview" src="#" alt="pic"  style="max-width: 100px;max-height: 100px;min-width: 100px;min-height: 100px;" />
									            </div>
											</div>

											<div class="form-group col-4 teaching">
												<label>Class Teacher <span class="mandatory_field">*</span></label>
												<div class="input-group">
													<div class="form-check">
							                          	<input class="form-check-input classteacher" type="radio" name="classteacher" value='yes'>
							                          	<label class="form-check-label"> Yes </label>
							                        </div>&nbsp;&nbsp;
							                        <div class="form-check">
							                          	<input class="form-check-input classteacher" type="radio" name="classteacher" value='no'>
							                          	<label class="form-check-label"> No </label>
							                        </div>
												</div>
											</div>

											<div class="form-group col-4 classteacher_classsection" style="display:none;">
												<label>Class Teacher For <span class="mandatory_field">*</span></label>
							                  	<select class="custom-select input-group" id="class_section" name="class_section">
							                  		<option value=''>Select Class - Section</option>
								                    
							                  	</select>
											</div>
											
										</div>
										<hr>
										<div class="row teaching">
											<div class="form-group col-4 rowcount">
												<label>Subject </label>
							                  	<select class="custom-select input-group staffsubject" id="staffsubject" name="staffsubject[0]">
							                  		<option value=''>Select Subject</option>			                   
							                  	</select>
											</div>

											<div class="form-group col-4">
												<label>Subject Teacher For </label>
							                  	<select class="custom-select input-group select2 subjectteacher" id="subjectteacher" data-id=0 name="subjectteacher[0][]" multiple>
							                  		<option value=''>Select Class - Section</option>	                    
							                  	</select>
											</div>

											<div class="form-group col-2">
												<label style="color: white;"> add more</label>
												<button type="button" name="addmore" id="addmore" class="btn btn-success input-group">+ Add More</button>
											</div>

										</div>
										<div class="row dynamicstaffclass">
											
										</div>
										<div class="row">
											<div class="form-group">
												<button type="submit" name="add" id="add" class="btn btn-primary ">
									                Add
									            </button>
									        </div>&nbsp;&nbsp;
									        <div class="form-group">
												<a href="{{url('usermanagement/staffs')}}" class="btn btn-success">
									                Cancel
									            </a>
									        </div>
										</div>
		              				</form>
		              			</div>
		            		</div>
		          		</div>
		        	</div>
		      	</div>
		    </section>
		</div>
	</div>
@endsection
@section('script')
	<script src="{!! asset('assets/js/jquery.dataTables.min.js') !!}"></script>
	<script src="{!! asset('assets/js/dataTables.bootstrap4.min.js') !!}"></script>
	<script src="{!! asset('assets/js/dataTables.responsive.min.js') !!}"></script>
	<script src="{!! asset('assets/js/responsive.bootstrap4.min.js') !!}"></script>
	<script type="text/javascript">
		$(document).ready(function(){
			$('.select2').select2();
			$('.teaching').hide();
			$('#user_category').change(function(){
				if($('#user_category :selected').val() == 3)
					$('.teaching').show();
				else
					$('.teaching').hide();
			});
			var class_section = subjects = '';
			$('#division_name').change(function() {
				$.get("{{url('usermanagement/subject_classes?id=')}}"+$(this).val(),function(response){ 
					var data = JSON.parse(response);
					if(response)
					{
						$("#specialized_in option,#department option,#class_section option,#subjectteacher option,#staffsubject option").remove();
						$('#specialized_in').append("<option value=''>Select Specialized In</option>");
						$('#department').append("<option value=''>Select Department</option>");
						$('#class_section').append("<option value=''>Select Class - Section</option>");
						$('#subjectteacher').append("<option value=''>Select Class - Section</option>");
						$('#staffsubject').append("<option value=''>Select Subject</option>");
						class_section = data.class_configs;
						subjects = data.subjects;
						$(data.subjects).each(function(  index, value ) {
						  	option = $("<option></option>");
						  	$(option).val(value.id);
						  	$(option).html(value.subject_name);
						  	$('#specialized_in,#department,#staffsubject').append(option);
						});
						$(data.class_configs).each(function(  index, value ) {
						  	option = $("<option></option>");
						  	$(option).val(value.id);
						  	$(option).html(value.class_section);
						  	$('#class_section,#subjectteacher').append(option);
						});
						// Initialize select2
 						initailizeSelect2();
					}
				})
			})
			var i = 0;
			$("#addmore").click(function(){
   				var staffhtml = '';
   				i++;
   				staffhtml+='<div class="form-group col-4 rowcount remove_'+i+'"><label>Subject </label>';
   				staffhtml+='<select class="custom-select input-group staffsubject" id="staffsubject['+i+']" name="staffsubject['+i+']"><option value="">Select Subject</option>';
   				$(subjects).each(function(  index, value ) {
   					staffhtml+='<option value='+value.id+'>'+value.subject_name+'</option>';
   				})
   				staffhtml+='</select></div>';
   				staffhtml+='<div class="form-group col-4 remove_'+i+'"><label>Subject Teacher For </label>';
   				staffhtml+='<select class="custom-select input-group select2 subjectteacher" data-id='+i+' id="subjectteacher['+i+']" name="subjectteacher['+i+'][]" multiple><option value="">Select Class - Section</option>';
   				$(class_section).each(function(  index, value ) {
   					staffhtml+='<option value='+value.id+'>'+value.class_section+'</option>';
   				})
   				staffhtml+='</select></div><div class="form-group col-2 remove_'+i+'"><label style="color: white;"> add more</label><button type="button" name="addmore" data-attr='+i+' id="addmore" class="btn btn-danger remove-tr input-group">Remove</button></div>';
							              
       			$(".dynamicstaffclass").append(staffhtml);
       			// Initialize select2
 				initailizeSelect2();
    		});

			$('#profile_image').change(function(){
		        const file = this.files[0];
		        if (file){
		          	let reader = new FileReader();
		          	reader.onload = function(event){
		            	$('#imgPreview').attr('src', event.target.result);
		            	$('.holder').css('display','block')
		          	}
		          	reader.readAsDataURL(file);
        		}
      		});

      		$('.classteacher').click(function(){
      			if($(this).val() == 'yes')
      				$('.classteacher_classsection').show();
      			else
      				$('.classteacher_classsection').hide();	
      		});

      		$('#bank_passbook').change(function(){
		        const file = this.files[0];
		        if (file){
		          	let reader = new FileReader();
		          	reader.onload = function(event){
		            	$('#bank_passbookimgPreview').attr('src', event.target.result);
		            	$('.bank_passbookholder').css('display','block')
		          	}
		          	reader.readAsDataURL(file);
        		}
      		});

      		$('#aadhar').change(function(){
		        const file = this.files[0];
		        if (file){
		          	let reader = new FileReader();
		          	reader.onload = function(event){
		            	$('#aadharimgPreview').attr('src', event.target.result);
		            	$('.aadharholder').css('display','block')
		          	}
		          	reader.readAsDataURL(file);
        		}
      		});

      		$('#pan_card').change(function(){
		        const file = this.files[0];
		        if (file){
		          	let reader = new FileReader();
		          	reader.onload = function(event){
		            	$('#pan_cardimgPreview').attr('src', event.target.result);
		            	$('.pan_cardholder').css('display','block')
		          	}
		          	reader.readAsDataURL(file);
        		}
      		});

			$('.dob,.doj').datepicker({
			    autoclose: true,
			    setDate: new Date(),
			    format: 'yyyy-mm-dd'
			});

			$("#addstaff").validate({
				rules: {
					staff_name: {
						required: true,
					},
					user_category: {
						required: true,
					},
					division_name: {
						required: function(element) {
				        	return $('#user_category').val() == 3;
				      	},
					},
					mobile_number: {
						required: true,
						digits:true,
						maxlength:10,
						minlength:10,
						remote: {
		                    url: "{{url('usermanagement/checkStaffMobilenoexists')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        mobile_number: function() {
		                            return $("#mobile_number").val();
		                        },
		                    },		     
		                }
					},
					specialized_in: {
						required: function(element) {
				        	return $('#user_category').val() == 3;
				      	},
					},
					class_section: {
						required: function(element) {
				        	return $('input[name="classteacher"]:checked').val() == 'yes';
				      	}
					},
					classteacher: {
						required: true,
					},
					employee_no: {
						required: true,
						remote: {
		                    url: "{{url('usermanagement/checkEmployeeno')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        employee_no: function() {
		                            return $("#employee_no").val();
		                        },
		                    },		     
		                }
					},
					esi_no: {
						remote: {
		                    url: "{{url('usermanagement/checkuseraccountdetails')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        esi_no: function() {
		                            return $("#esi_no").val();
		                        },
		                    },		     
		                }
					},
					oasis_no: {
						remote: {
		                    url: "{{url('usermanagement/checkuseraccountdetails')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        oasis_no: function() {
		                            return $("#oasis_no").val();
		                        },
		                    },		     
		                }
					},
					emis_no: {
						remote: {
		                    url: "{{url('usermanagement/checkuseraccountdetails')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        emis_no: function() {
		                            return $("#emis_no").val();
		                        },
		                    },		     
		                }
					},
					aadhar_no: {
						remote: {
		                    url: "{{url('usermanagement/checkuseraccountdetails')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        aadhar_no: function() {
		                            return $("#aadhar_no").val();
		                        },
		                    },		     
		                }
					},
					pan_card_no: {
						remote: {
		                    url: "{{url('usermanagement/checkuseraccountdetails')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        pan_card_no: function() {
		                            return $("#pan_card_no").val();
		                        },
		                    },		     
		                }
					},
					account_no: {
						remote: {
		                    url: "{{url('usermanagement/checkuseraccountdetails')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        account_no: function() {
		                            return $("#account_no").val();
		                        },
		                    },		     
		                }
					},
					dob: {
						required: true,
					},
					doj: {
						required: true,
					},					
				    profile_image: {
				    	extension: "png|jpg|jpeg"
				    }
				},
				messages: {
					staff_name: {
						required: 'Staff Name field is required',
					},
					division_name: {
						required: 'Division Name field is required',
					},
					classteacher: {
						required: "Class Teacher field is required",
					},
					class_section: {
						required: 'Class Teacher For field is required',
					},
					employee_no: {
						required: 'Employee no field is required',
						remote: "Given Employee number already exists",
					},
					esi_no: {
						remote: "Given ESI number already exists",
					},
					oasis_no: {
						remote: "Given Oasis number already exists",
					},
					pan_card_no: {
						remote: "Given Pan Card number already exists",
					},
					account_no: {
						remote: "Given Account number already exists",
					},
					emis_no: {
						remote: "Given EMIS number already exists",
					},
					aadhar_no: {
						remote: "Given Aadhar number already exists",
					},
					dob: {
						required: 'DOB is required',
					},
					doj: {
						required: 'DOJ is required',
					},
					mobile_number: {
						required: 'Mobile No is required',
						remote: 'Given mobile number already exists',
					},
					specialized_in: {
						required: 'Specialized In field is required',
					},
					user_category: {
						required: 'User Category field is required',
					},
				},
				errorPlacement: function(error, element) {
			      	var placement = $(element).data('error');
			      	element.closest('.input-group').after(error);
			      	// // if (placement) {
			        // // 	// $(placement).insertAfter(error)
			      	// error.insertAfter($('element').parents('div'));
			      	// // } else {
			        // // 	error.insertAfter(element);
			      	// }
			    },
			    submitHandler: function(form) {
			    	form.submit();			    
			    }
			})
			return false;
		});

	

	    $(document).on('click', '.remove-tr', function(){  
	    	var i = $(this).attr('data-attr');
	        $('.remove_'+i).remove();
	    }); 

	    function initailizeSelect2(){
	    	$('.select2').select2();
	    } 


	   	$(document).on('change', '.subjectteacher', function(){ 
	    	var staffsubject ='';
	    	var id =$(this).attr('data-id');
	    	let class_section = $(this ).val();
	    	var all_values = [];
			var current_instance = $(this);
	    	$("#subjectteacher option:selected").each(function(index){
	    		if($(this).val() !='')
	    			all_values.push($(this).val());	    		
			});
			$(".staffsubject").each(function(index){
				if(id == index)
			    	staffsubject = $(this).val();
			});
	    	if(staffsubject != '' && class_section!='')
	    	{	    		
		    	$.post("{{url('usermanagement/checksubjectaccess')}}", {class_section:class_section,staffsubject:staffsubject}, function(response){ 
			      	if(response != 'true')
			      	{
				    	swal({
						  	title: "Are you sure?",
						  	text: "Selected Class was already mapped with another staff, do you want to continue?",
						  	type: "warning",
						  	showCancelButton: true,
						  	confirmButtonClass: "btn-danger",
						  	confirmButtonText: "Yes",
						  	cancelButtonText: "No",
						}).then((result) => {
						  	if (result.value) {
						    	
						  	} else {
						  		all_values.splice($.inArray(response,all_values),1);
						  		current_instance.val(all_values).trigger('change');
						  		swal({
								  	title: "Warning!",
								  	text: "Please Select Subject",
								  	type: "warning",
								  	confirmButtonClass: "btn-danger",
								  	confirmButtonText: "OK",
								});
						  	}
						});
					}
				});
	    	}
	    	else
	    	{
	    		swal({
				  	title: "Warning!",
				  	text: "Please Select Subject",
				  	type: "warning",
				  	confirmButtonClass: "btn-danger",
				  	confirmButtonText: "OK",
				});
	    	}
	    });

	    $(document).on('change', '#class_section', function(){ 
			$.post("{{url('usermanagement/checkClassteacherexists')}}", {class_section:$('#class_section').val()}, function(response){
		      	if(response == 'false')
		      	{
		      		swal({
					  	title: "Are you sure?",
					  	text: $('#class_section :selected').text()+" already mapped with another staff as class teacher, do you want to continue?",
					  	type: "warning",
					  	showCancelButton: true,
					  	confirmButtonClass: "btn-danger",
					  	confirmButtonText: "Yes",
					  	cancelButtonText: "No",
					}).then((result) => {
						console.log(result);
					  	if (result.value) {
					    	// form.submit();
					  	} else {
					  		$('#class_section').val(null);
					  	}
					});
		     	}
			});
	    	
	    });  
	</script>

@endsection

