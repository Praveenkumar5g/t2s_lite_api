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
							<li class="breadcrumb-item active">Staffs</li>
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
											<label>Employee No </label>
						                  	<div class="input-group">
												<input type="text" class="form-control form-control-1 input-sm" name="employee_no" id="employee_no" placeholder="Employee No">
											</div>
										</div>
										<div class="form-group col-4">
											<label>Mobile No </label>
						                  	<div class="input-group">
												<input type="text" class="form-control form-control-1 input-sm" name="mobile_no" id="mobile_no" placeholder="Mobile No">
											</div>
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
											<a href="{{url('usermanagement/addStaff')}}" name="add_staff" id="add_staff" class="btn btn-info float-sm-right">
								                Add Staff
								            </a>
								        </div>
									</div>

		                			<table id="stafflist" class="table table-bordered table-hover table-striped">
		                  				<thead>
						                  	<tr>
						                  		<th>S.No</th>
							                    <th>Id</th>
							                    <th>Name</th>
							                    <th>Mobile No</th>
							                    <th>Employee No</th>
							                    <th>Class Teacher</th>
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
			$('#stafflist').DataTable({

	    		processing: true,
		        serverSide: true,
		        "responsive": true, 
		        "lengthChange": false,
		         "autoWidth": false,
   		      	ajax: {
		        	url : "{{url('usermanagement/getStaff_list')}}", 
		        	method: 'POST',
	                'headers': {
	                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
	                }, 
	                data: { admission_no: $('#employee_no').val(),mobile_no:$('#mobile_no').val(),name:$('#name').val()}
                },
		        filter: false,
			    info: false,
			    ordering: false,
			    retrieve: true,
			    deferRender: true,
			    columns: [
		            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
		            {data: 'user_id', name: 'user_id'},
		            {data: 'first_name', name: 'first_name'},
		            {data: 'mobile_number', name: 'mobile_number'},
		            {data: 'employee_no', name: 'employee_no'},
		            {data: 'class_section', name: 'class_section'},
		            {data: 'edit_staff', name: 'edit_staff'},
		        ],

		    });

		    $("#search,#reset").on("click", function (event) {
		    	if($(this).attr('id') == 'reset')
		    	{
		    		$("#employee_no").val('');
		    		$("#mobile_no").val('');
		    		$("#name").val('');
		    	}
		    	event.preventDefault();
		    	$('#stafflist').DataTable().destroy();

			   	$('#stafflist').DataTable({
		    		processing: true,
			        serverSide: true,
			        "responsive": true, 
			        "lengthChange": false,
			        "autoWidth": false,
			      	ajax: {
			        	url : "{{url('usermanagement/getStaff_list')}}", 
			        	method: 'POST',
		                'headers': {
		                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
		                }, 
		                data: { admission_no: $('#employee_no').val(),mobile_no:$('#mobile_no').val(),name:$('#name').val() }
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
			            {data: 'first_name', name: 'first_name'},
			            {data: 'mobile_number', name: 'mobile_number'},
			            {data: 'employee_no', name: 'employee_no'},
			            {data: 'class_section', name: 'class_section'},
			            {data: 'edit_staff', name: 'edit_staff'},
			        ],
			        
			    });
			});
	  	});
	</script>
@endsection

