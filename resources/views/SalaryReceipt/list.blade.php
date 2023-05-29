@extends('layouts.master')

@section('content')
	<div class="content-wrapper">
		<div class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-6">
						<h1 class="m-0">Salary Details</h1>
					</div><!-- /.col -->
					<div class="col-sm-6">
						<ol class="breadcrumb float-sm-right">
							<li class="breadcrumb-item"><a href="javascript:void(0);">Salary Details</a></li>
							<li class="breadcrumb-item active">Salary Listing</li>
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
		                			<h3 class="card-title">Salary Pay Slip</h3>
		                		</div>
		              			<div class="card-body">
		              				<div class="row">
		              					<div class="form-group col-4">
											<label>Employee No</label>
											<div class="input-group">
												<input type="text" class="form-control form-control-1 input-sm" name="employee_no" id="employee_no" placeholder="Employee No">
											</div>
										</div>
										<div class="form-group col-4">
											<label>Role</label>
						                  	<select class="custom-select" id="role">
						                  		<option value=''>Select Role</option>
							                    <option value="{{Config::get('app.Admin_role')}}">Admin</option>
							                    <option value="{{Config::get('app.Management_role')}}">Management</option>
							                    <option value="{{Config::get('app.Staff_role')}}">Staff</option>
						                  	</select>
										</div>
										<div class="form-group col-4">
											<label>Select Month:</label>
											<div class="input-group">
												<input type="text" id="month" class="form-control form-control-1 input-sm month" name="month" placeholder="Month" >
											</div>
										</div>
										<div class="form-group col-4">
											<label>P.F Account No</label>
											<div class="input-group">
												<input type="text" id="pfaccno" class="form-control form-control-1 input-sm" name="pfaccno" placeholder="P.F Account No" >
											</div>
										</div>
									</div>
									<div class="row">
										<div class="form-group col-4">
											<button type="button" name="search" id="search" class="btn btn-primary">
								                Search
								            </button>
								        </div>
									</div><hr/>

									<div class="row">
										<div class="form-group col-12">
											<a href="javascript:void(0);" target="_blank" name="pdf_download" id="pdf_download" class="btn btn-info float-sm-right">
								                Export PDF
								            </a>
								        </div>
									</div>

		                			<table id="example2" class="table table-bordered table-hover table-striped">
		                  				<thead>
						                  	<tr>
						                  		<th>S.No</th>
							                    <th>UserId</th>
							                    <th>Employee No</th>
							                    <th>Employee Name</th>
							                    <th>Month - Year</th>
							                    <th>P.F Account No</th>
							                    <th>Actual Salary</th>
							                    <th>Pay Slip</th>
						                  	</tr>
		                  				</thead>
		                  				<tfoot>
		                  					<tr>
		                  						<th>S.No</th>
							                    <th>UserId</th>
							                    <th>Employee No</th>
							                    <th>Employee Name</th>
							                    <th>Month - Year</th>
							                    <th>P.F Account No</th>
							                    <th>Actual Salary</th>
							                    <th>Pay Slip</th>
		                  					</tr>
		                  				</tfoot>
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
	<script src="{!! asset('public/assets/js/jquery.dataTables.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/dataTables.bootstrap4.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/dataTables.responsive.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/responsive.bootstrap4.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/dataTables.buttons.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/buttons.bootstrap4.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/jszip.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/pdfmake.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/vfs_fonts.js') !!}"></script>
	<script src="{!! asset('public/assets/js/buttons.html5.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/buttons.print.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/buttons.colVis.min.js') !!}"></script>
	<script type="text/javascript">
		$(document).ready(function(){
			var url = "{{url('employee/download_report')}}";
			$('#pdf_download').attr("href", url); // Set herf value

			$('#employee_no,#role,#pfaccno,#month').change(function(){
				var url = "{{url('employee/download_report')}}";
				var employee_no = $('#employee_no').val();
				var role = $('#role').val();
				var month = $('#month').val();
				var pfaccno = $('#pfaccno').val();
				var newurl = url+'?employee_no='+employee_no+'&role='+role+'&month='+month+'&pfaccno='+pfaccno;		
				
				$('#pdf_download').attr("href", newurl);
			})
			$('.month').datepicker({
			    autoclose: true,
			    minViewMode: 1,
			    format: 'mm/yyyy'
			});
		
	    	$('#example2').DataTable({

	    		processing: true,
		        serverSide: true,
		        "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"],
		        "responsive": true, 
		        "lengthChange": false,
		         "autoWidth": false,
   		      	ajax: {
		        	url : "{{url('employee/getSalary_list')}}", 
		        	method: 'POST',
	                'headers': {
	                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
	                }, 
	                data: { employee_no: $('#employee_no').val(),month:$('#month').val(),role:$('#role').val(),pfaccno:$('#pfaccno').val() }
                },
		        filter: false,
			    info: false,
			    ordering: false,
			    retrieve: true,
			    deferRender: true,
			    columns: [
		            {data: 'DT_RowIndex', name: 'DT_RowIndex'},
		            {data: 'user_id', name: 'user_id'},
		            {data: 'employee_no', name: 'employee_no'},
		            {data: 'employee_name', name: 'employee_name'},
		            {data: 'month', name: 'month'},
		            {data: 'pfacc_no', name: 'pfacc_no'},
		            {data: 'actual', name: 'actual'},
		            {
		                data: 'payslip', 
		                name: 'payslip', 
		                orderable: true, 
		                searchable: true
		            },
		        ],

		    }).buttons().container().appendTo('#example2_wrapper .col-md-6:eq(0)');

		    $("#search").on("click", function (event) {
		    	event.preventDefault();
		    	$('#example2').DataTable().destroy();
		    	var employee_no = $('#employee_no').val();
		    	var month = $('#month').val();
		    	var role = $('#role').val();
		    	var pfaccno = $('#pfaccno').val();

			   	$('#example2').DataTable({
		    		processing: true,
			        serverSide: true,
			      	ajax: {
			        	url : "{{url('employee/getSalary_list')}}", 
			        	method: 'POST',
		                'headers': {
		                   'X-CSRF-TOKEN': '{{ csrf_token() }}'
		                }, 
		                data: { employee_no: $('#employee_no').val(),month:$('#month').val(),role:$('#role').val(),pfaccno:$('#pfaccno').val() }
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
			            {data: 'employee_no', name: 'employee_no'},
			            {data: 'employee_name', name: 'employee_name'},
			            {data: 'month', name: 'month'},
			            {data: 'pfacc_no', name: 'pfacc_no'},
			            {data: 'actual', name: 'actual'},
			            {
			                data: 'payslip', 
			                name: 'payslip', 
			                orderable: true, 
			                searchable: true
			            },
			        ],
			        
			    });
			});
	  	});
	</script>
@endsection

