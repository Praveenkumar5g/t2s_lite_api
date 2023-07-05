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
		                			<h3 class="card-title">Send Welcome Message</h3>
		                		</div>
		              			<div class="card-body">
		              				<form name="send_welcome_message" id="send_welcome_message" method="post" action="{{url('usermanagement/send_welcome_message')}}">
									@csrf
		              					<div class="row">
			              					<div class="form-group col-4">
												<label>Role <span class="mandatory_field">*</span> </label>
							                  	<select class="custom-select input-group" id="role" name="role">
							                  		<option value=''>Select Role</option>
							                  		<option value="all">All</option>
								                    <option value="{{Config::get('app.Management_role')}}">Management</option>
								                    <option value="{{Config::get('app.Staff_role')}}">Staff</option>
								                    <option value="{{Config::get('app.Parent_role')}}">Parent</option>
							                  	</select>
											</div>
											<div class="form-group col-4" id="display_distribution">
												<label>Distrubution Type<span class="mandatory_field">*</span></label>
							                  	<select class="custom-select input-group" id="distribution_type" name="distribution_type">
							                  		<option value=''>Select Distribution Type</option>
								                    <option value="1">New Users</option>
								                    <option value="2">Not Installed Users</option>
								                    <option value="3">Individual User</option>
								                    <option value="4">All</option>
							                  	</select>
											</div>
											<div class="form-group col-4" id="display_managements">
												<label>Management</label>
							                  	<select class="form-control input-group" id="managements" name="managements[]" multiple>
							                  		<option value=''>Select Management</option>
								                    @foreach($managements as $management_key => $management_value)
								                    	<option value="{{$management_value['id']}}">{{$management_value['first_name']}}</option>
								                    @endforeach
							                  	</select>
											</div>
											

											<div class="form-group col-4" id="display_staffs">
												<label>Staff</label>
							                  	<select class="select form-control form-control-1 input-sm input-group" id="staffs" name="staffs[]" multiple>
							                  		<option value=''>Select Staff</option>
								                    @foreach($staffs as $staff_key => $staff_value)
								                    	<option value="{{$staff_value['id']}}">{{$staff_value['first_name']}}</option>
								                    @endforeach
							                  	</select>
											</div>
											<div class="form-group col-4" id="display_students">
												<label>Student</label>
							                  	<select class="select form-control form-control-1 input-sm input-group" id="students" name="students[]" multiple>
							                  		<option value=''>Select Students</option>
								                    @foreach($parents as $parent_key => $parent_value)
								                    	<option value="{{$parent_value['id']}}">{{$parent_value['first_name']}}</option>
								                    @endforeach
							                  	</select>
											</div>
							
										</div>
										<div class="row">
											<div class="form-group">
												<button type="submit" name="add" id="add" class="btn btn-primary">
									                Send Message
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
			$('#display_managements, #display_students, #display_staffs').hide();
			$('#managements, #staffs, #students').selectpicker();
			$('#distribution_type, #role').change(function(){
				$('#display_students, #display_staffs, #display_managements').prop("selected", false);
				var distribution_type = $('#distribution_type').val();
				var role = $('#role').val();
				if(distribution_type == 3 && role == 5)
				{
					$('#display_managements,#display_distribution').show();
					$('#display_students, #display_staffs').hide();
				}
				else if(distribution_type == 3 && role == 2)
				{
					$('#display_staffs,#display_distribution').show();
					$('#display_managements, #display_students').hide();
				}
				else if(distribution_type == 3 && role == 3)
				{
					$('#display_students,#display_distribution').show();
					$('#display_managements, #display_staffs').hide();
				}
				else if(role == 'all')
				{
					$('#display_managements, #display_staffs,#display_distribution,#display_students').hide();
				}
			});
			$("#send_welcome_message").validate({
				rules: {
					role: {
						required: true,
					},
					distribution_type: {
						required: true,
					},
					"managements[]": {
						required: function(element) {
				      		return $('#distribution_type').val() == 3 && $('#role').val() == 5;
				      	},
					},
					"staffs[]": {
						required: function(element) {
				      		return $('#distribution_type').val() == 3 && $('#role').val() == 2;
				      	},
					},
					"students[]": {
						required: function(element) {
				      		return $('#distribution_type').val() == 3 && $('#role').val() == 3;
				      	},
					},
					
				},
				messages: {
					role: {
						required: 'Role is required',
					},
					distribution_type: {
						required: 'Distrubution type is required',
					},	
					"managements[]": {
						required: 'Please select management',
					},
					"staffs[]": {
						required: 'Please select staffs',
					},				
					"students[]": {
						required: 'Please select students',
					},
				},
				errorPlacement: function(error, element) {
			      	var placement = $(element).data('error');
			      	if($(element)[0].id == 'managements')
			      	{
			        	element.closest('.dropdown').after(error);
			      	} else {
			      		element.closest('.input-group').after(error);
			        // // 	error.insertAfter(element);
			      	}
			    },
			    submitHandler: function(form) {
			    	form.submit();
			    }
			})
			return false;
		});
	</script>

@endsection

