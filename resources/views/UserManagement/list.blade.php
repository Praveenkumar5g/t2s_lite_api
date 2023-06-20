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
							<li class="breadcrumb-item active">Students</li>
						</ol>
					</div><!-- /.col -->
				</div><!-- /.row -->
			</div><!-- /.container-fluid -->

			<!-- Main content -->
		    <section class="content">
		      	<div class="container-fluid">
		        	<div class="row">
		          		<div class="col-12">
		            		<div class="card">
		              			<div class="card-header">
		                			<h3 class="card-title">Search</h3>
		                		</div>
		              			<div class="card-body">
		              				<div class="row">
		              					<div class="form-group col-4">
											<label>Name</label>
											<div class="input-group">
												<input type="text" class="form-control form-control-1 input-sm" name="name" id="name" placeholder="Name">
											</div>
										</div>
										<div class="form-group col-4">
											<label>Admission No </label>
						                  	<div class="input-group">
												<input type="text" class="form-control form-control-1 input-sm" name="admission_no" id="admission_no" placeholder="Admission No">
											</div>
										</div>
										<div class="form-group col-4">
											<label>Mobile No </label>
						                  	<div class="input-group">
												<input type="text" class="form-control form-control-1 input-sm" name="mobile_no" id="mobile_no" placeholder="Mobile No">
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
									</div>
									<div class="row">
										<div class="form-group">
											<button type="button" name="search" id="search" class="btn btn-primary">
								                Search
								            </button>
								        </div>&nbsp;&nbsp;
								        <div class="form-group">
											<button type="button" name="reset" id="reset" class="btn btn-success">
								                Reset
								            </button>
								        </div>
									</div><hr/>

									<div class="row">
										<div class="form-group col-12">
											<a href="{{url('usermanagement/addStudents')}}" name="add_student" id="add_student" class="btn btn-info float-sm-right">
								                Add Student
								            </a>
								        </div>
									</div>

		                			<table id="example2" class="table table-bordered table-hover table-striped">
		                  				<thead>
						                  	<tr>
						                  		<th>S.No</th>
							                    <th>Id</th>
							                    <th>Name</th>
							                    <th>Admission No</th>
							                    <th>Class - Section</th>
							                    <th>Father Name</th>
							                    <th>Father Mobile No</th>
							                    <th>Mother Name</th>
							                    <th>Mother Mobile No</th>
							                    <th>Guardian Name</th>
							                    <th>Guardian Mobile No</th>
							                    <th>Action</th>
						                  	</tr>
		                  				</thead>
		                			</table>
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
	<script src="{!! asset('assets/js/dataTables.buttons.min.js') !!}"></script>
	<script src="{!! asset('assets/js/buttons.bootstrap4.min.js') !!}"></script>
	<script src="{!! asset('assets/js/jszip.min.js') !!}"></script>
	<script src="{!! asset('assets/js/pdfmake.min.js') !!}"></script>
	<script src="{!! asset('assets/js/vfs_fonts.js') !!}"></script>
	<script src="{!! asset('assets/js/buttons.html5.min.js') !!}"></script>
	<script src="{!! asset('assets/js/buttons.print.min.js') !!}"></script>
	<script src="{!! asset('assets/js/buttons.colVis.min.js') !!}"></script>
	<script type="text/javascript">
		$(document).ready(function(){
	
	    	$('#example2').DataTable({

	    		processing: true,
		        serverSide: true,
		        "responsive": true, 
		        "lengthChange": false,
		         "autoWidth": false,
   		      	ajax: {
		        	url : "{{url('usermanagement/getStudent_list')}}", 
		        	method: 'POST',
	                'headers': {
	                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
	                }, 
	                data: { admission_no: $('#admission_no').val(),mobile_no:$('#mobile_no').val(),name:$('#name').val(),'class_section':$('#class_section').val() }
                },
		        filter: false,
			    info: false,
			    ordering: false,
			    retrieve: true,
			    deferRender: true,
			    columns: [
		            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
		            {data: 'user_id', name: 'user_id'},
		            {data: 'student_name', name: 'student_name'},
		            {data: 'admission_number', name: 'admission_number'},
		            {data: 'class_section', name: 'class_section'},
		            {data: 'father_name', name: 'father_name'},
		            {data: 'father_mobile_no', name: 'father_mobile_no'},
		            {data: 'mother_name', name: 'mother_name'},
		            {data: 'mother_mobile_no', name: 'mother_mobile_no'},
		            {data: 'guardian_name', name: 'guardian_name'},
		            {data: 'guardian_mobile_no', name: 'guardian_mobile_no'},
		            {data: 'edit_student', name: 'edit_student'}

		        ],

		    });

		    $("#search,#reset").on("click", function (event) {
		    	if($(this).attr('id') == 'reset')
		    	{
		    		$("#admission_no").val('');
		    		$("#mobile_no").val('');
		    		$("#class_section").val('');
		    		$("#name").val('');
		    	}
		    	event.preventDefault();
		    	$('#example2').DataTable().destroy();

			   	$('#example2').DataTable({
		    		processing: true,
			        serverSide: true,
			        "responsive": true, 
			        "lengthChange": false,
			        "autoWidth": false,
			      	ajax: {
			        	url : "{{url('usermanagement/getStudent_list')}}", 
			        	method: 'POST',
		                'headers': {
		                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
		                }, 
		                data: { admission_no: $('#admission_no').val(),mobile_no:$('#mobile_no').val(),name:$('#name').val(),'class_section':$('#class_section').val() }
	                },
			        filter: false,
				    info: false,
				    ordering: false,
				    processing: true,
				    retrieve: true,
				    deferRender: true,
				    columns: [
			            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
			            {data: 'user_id', name: 'user_id'},
			            {data: 'student_name', name: 'student_name'},
			            {data: 'admission_number', name: 'admission_number'},
			            {data: 'class_section', name: 'class_section'},
			            {data: 'father_name', name: 'father_name'},
			            {data: 'father_mobile_no', name: 'father_mobile_no'},
			            {data: 'mother_name', name: 'mother_name'},
			            {data: 'mother_mobile_no', name: 'mother_mobile_no'},
			            {data: 'guardian_name', name: 'guardian_name'},
			            {data: 'guardian_mobile_no', name: 'guardian_mobile_no'},
			            {data: 'edit_student', name: 'edit_student'}
			        ],
			        
			    });
			});
	  	});
	</script>
@endsection

