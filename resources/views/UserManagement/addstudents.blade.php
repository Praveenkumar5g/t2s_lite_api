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
							<li class="breadcrumb-item active"><a href="{{url('usermanagement/students')}}">Students</a></li>
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
		                			<h3 class="card-title">Add Students</h3>
		                		</div>
		              			<div class="card-body">
		              				<form name="addstudent" id="addstudent" method="post" action="{{url('usermanagement/storeStudents')}}" enctype="multipart/form-data">
									@csrf
		              					<div class="row">
			              					<div class="form-group col-4">
												<label>Student Name <span class="mandatory_field">*</span> </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="student_name" id="student_name" placeholder="Student Name">
												</div>	
											</div>
											<div class="form-group col-4">
												<label>Class - Section <span class="mandatory_field">*</span></label>
							                  	<select class="custom-select input-group" id="class_section" name="class_section">
							                  		<option value=''>Select Class - Section</option>
								                    @foreach($class_configs as $classconfig_key => $classconfig_value)
								                    	<option value="{{$classconfig_value['id']}}">{{$classconfig_value['class_section']}}</option>
								                    @endforeach
							                  	</select>
											</div>
											<div class="form-group col-4">
												<label>Roll No</label>
												<div class="input-group">
													<input type="text" id="roll_no" class="form-control form-control-1 input-sm month" name="roll_no" placeholder="Roll No" >
												</div>
											</div>
											<div class="form-group col-4">
												<label>Admission No <span class="mandatory_field">*</span></label>
												<div class="input-group">
													<input type="text" id="admission_no" class="form-control form-control-1 input-sm" name="admission_no" placeholder="Admission No" >
												</div>
											</div>

											<div class="form-group col-4">
												<label>Select DOB: <span class="mandatory_field">*</span></label>
												<div class="input-group">
													<input type="text" id="dob" class="form-control form-control-1 input-sm dob" name="dob" placeholder="DOB" >
												</div>
											</div>
											
											<div class="form-group col-4">
												<label>Gender <span class="mandatory_field">*</span></label>
							                  	<select class="custom-select input-group" id="gender" name="gender">
							                  		<option value=''>Select Gender</option>
								                    <option value="1">Male</option>
								                    <option value="2">Female</option>
								                    <option value="3">Others</option>
							                  	</select>
											</div>
											<div class="form-group col-4">
												<label>Father Name </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="father_name" id="father_name" placeholder="Father Name">
												</div>
											</div>
											<div class="form-group col-4">
												<label>Father Mobile No </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="father_mobile_number" id="father_mobile_number" placeholder="Father Mobile No">
												</div>
											</div>
											<div class="form-group col-4">
												<label>Father Email Address </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="father_email" id="father_email" placeholder="Father Email Address">
												</div>
											</div>
											<div class="form-group col-4">
												<label>Mother Name</label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="mother_name" id="mother_name" placeholder="Mother Name">
												</div>
											</div>
											<div class="form-group col-4">
												<label>Mother Mobile No </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="mother_mobile_number" id="mother_mobile_number" placeholder="Mother Mobile No">
												</div>
											</div>
											<div class="form-group col-4">
												<label>Mother Email Address </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="mother_email" id="mother_email" placeholder="Mother Email Address">
												</div>
											</div>
											<div class="form-group col-4">
												<label>Guardian Name</label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="guardian_name" id="guardian_name" placeholder="Guardian Name">
												</div>
											</div>
											<div class="form-group col-4">
												<label>Guardian Mobile No </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="guardian_mobile_number" id="guardian_mobile_number" placeholder="Guardian Mobile No">
												</div>
											</div>
											<div class="form-group col-4">
												<label>Guardian Email Address </label>
												<div class="input-group">
													<input type="text" class="form-control form-control-1 input-sm" name="guardian_email" id="guardian_email" placeholder="Guardian Email Address">
												</div>
											</div>
											<div class="form-group col-4">
												<label>Profile Image:</label>
												<div class="input-group">
													<input class="form-control" name="profile_image" type="file" id="profile_image">
												</div>
												<div class="holder">
									                <img id="imgPreview" src="#" alt="pic"  style="max-width: 100px;max-height: 100px;min-width: 100px;min-height: 100px;" />
									            </div>
											</div>
											<!-- <div class="form-group col-4">
												<label>Temporary Student</label>
												<div class="input-group">
													<div class="form-check">
							                          	<input class="form-check-input" type="radio" name="temporary_student" id="temporary_student" value='yes'>
							                          	<label class="form-check-label"> Yes </label>
							                        </div>&nbsp;&nbsp;
							                        <div class="form-check">
							                          	<input class="form-check-input" type="radio" name="temporary_student" id="temporary_student" checked value='no'>
							                          	<label class="form-check-label"> No </label>
							                        </div>
												</div>
											</div> -->
										</div>
										<div class="row">
											<div class="form-group">
												<button type="submit" name="submit" id="submit" class="btn btn-primary">
									                Add
									            </button>
									        </div>&nbsp;&nbsp;
									        <div class="form-group">
												<a href="{{url('usermanagement/students')}}" class="btn btn-success">
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
			$('#profile_image').change(function(){
		        const file = this.files[0];
		        if (file){
		          	let reader = new FileReader();
		          	reader.onload = function(event){
		            	console.log(event.target.result);
		            	$('#imgPreview').attr('src', event.target.result);
		          	}
		          	reader.readAsDataURL(file);
        		}
      		});

			$('.dob').datepicker({
			    autoclose: true,
			    setDate: new Date(),
			    format: 'yyyy-mm-dd'
			});

			$("#addstudent").validate({
				rules: {
					student_name: {
						required: true,
					},
					class_section: {
						required: true,
					},
					admission_no: {
						required: true,
						remote: {
		                    url: "{{url('usermanagement/checkAdmissionno')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        admission_no: function() {
		                            return $("#admission_no").val();
		                        },
		                    },		     
		                }
					},
					dob: {
						required: true,
					},
					gender: {
						required: true,
					},
					father_name: {
				      	required: function(element) {
				        	return $('#father_mobile_number').val() != '';
				      	}
				    },
				    father_mobile_number: {
				      	required: function(element) {
				      		return $('#father_mobile_number').val() == "" && $('#mother_mobile_number').val() == "" && $('#guardian_mobile_number').val() == "";
				      	},
				      	digits: true,
				      	minlength: 10,
				      	maxlength: 10,
				      	remote: {
		                    url: "{{url('usermanagement/checkMobileno')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        mobile_no: function() {
		                            return $("#father_mobile_number").val();
		                        },
		                        status:'father',
		                    },		     
		                }
				    },
				    mother_name: {
				      	required: function(element) {
				        	return $('#mother_mobile_number').val() != '';
				      	}
				    },
				    mother_mobile_number: {
				      	required: function(element) {
				      		return $('#father_mobile_number').val() == "" && $('#mother_mobile_number').val() == "" && $('#guardian_mobile_number').val() == "";
				      	},
				      	digits: true,
				      	minlength: 10,
				      	maxlength: 10,
				      	remote: {
		                    url: "{{url('usermanagement/checkMobileno')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        mobile_no: function() {
		                            return $("#mother_mobile_number").val();
		                        },
		                        status:'mother',
		                    },		     
		                }
				    },
				    guardian_name: {
				      	required: function(element) {
				        	return $('#guardian_mobile_number').val() != '';
				      	}
				    },
				    guardian_mobile_number: {
				      	required: function(element) {
				      		return $('#father_mobile_number').val() == "" && $('#mother_mobile_number').val() == "" && $('#guardian_mobile_number').val() == "";
				      	},
				      	digits: true,
				      	minlength: 10,
				      	maxlength: 10,
				      	remote: {
		                    url: "{{url('usermanagement/checkMobileno')}}",
		                    type: "post",
		                    'headers': {
			                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
			                },
		                    data: {
		                        mobile_no: function() {
		                            return $("#guardian_mobile_number").val();
		                        },
		                        status:'guardian',
		                    },		     
		                }
				    },
				    profile_image: {
				    	extension: "png|jpg|jpeg"
				    }
				},
				messages: {
					student_name: {
						required: 'Student Name field is required',
					},
					class_section: {
						required: 'Class and section field is required',
					},
					admission_no: {
						required: 'Admission no field is required',
						remote: "Given Admission number already exists",
					},
					dob: {
						required: 'DOB is required',
					},
					gender: {
						required: 'Gender is required',
					},
					father_name: {
						required: 'Father name is required',
					},
					father_mobile_number: {
						required: 'Father Mobile No is required(Any one of the parent mobile no is required)',
						remote: 'Given mobile number already exists',
					},
					mother_name: {
						required: 'Mother name is required',
					},
					mother_mobile_number: {
						required: 'Mother Mobile No is required(Any one of the parent mobile no is required)',
						remote: 'Given mobile number already exists',
					},
					guardian_name: {
						required: 'Guardian name is required',
					},
					guardian_mobile_number: {
						required: 'Guardian Mobile No is required(Any one of the parent mobile no is required)',
						remote: 'Given mobile number already exists',
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
			    }
			})
			return false;
		});
	</script>

@endsection

