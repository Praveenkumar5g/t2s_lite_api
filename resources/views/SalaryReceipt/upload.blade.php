@extends('layouts.master')

@section('content')
	<div class="content-wrapper">
		<section class="content-header">
			<div class="container-fluid">
				<div class="row mb-2">
					<div class="col-sm-6">
						<h1>Upload Salary Details</h1>
					</div>
					<div class="col-sm-6">
						<ol class="breadcrumb float-sm-right">
							<li class="breadcrumb-item"><a href="javascript:void(0);">Salary Details</a></li>
							<li class="breadcrumb-item active">Salary Upload</li>
						</ol>
					</div>
				</div>
			</div><!-- /.container-fluid -->
	    </section>
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
				<form action="{{url('employee/salaryUpload')}}" name="uploadform" id="uploadform" method="post" enctype="multipart/form-data">
					@csrf
					<div class="row">
							<div class="col-md-12">
								<div class="card card-primary">
									<div class="callout callout-info">
						              	<h5><i class="fas fa-info"></i> Note:</h5>
						              	<a href="{{url('employee/sample_excel')}}">Click Here </a>to get the sample excel. 
						            </div>
								  	<div class="card-header">
								    	<h3 class="card-title">Salary upload</h3>
								  	</div>
									<div class="card-body">
										<div class="form-group">
											<label>Select Month:</label>
											<div class="input-group">
												<input type="text" class="form-control form-control-1 input-sm month" name="month" placeholder="Select Month" >
											</div>
										</div>
										<div class="form-group">
											<label>File:</label>
											<div class="input-group">
												<input class="form-control" name="salary" type="file" id="formFile">
											</div>
										</div>
									</div>
									<div class="card-footer">
										<button type="submit" name="submit" class="btn btn-primary">
							                Upload Files
							            </button>
									</div>
								</div>
							</div>
					</div>
				</form>
			</div>
			
    	</section>
	</div>
@endsection

@section('script')
<script>
$(document).ready(function(){
	$('.month').datepicker({
	    autoclose: true,
	    minViewMode: 1,
	    format: 'mm/yyyy'
	});

	$('#uploadform').validate({
		rules: {
			month:{
				required: true,
			},
			salary: {
				required: true, 
				extension: "xlsx|xls"
			},
		},
		messages: {
			month: {
				required: "Select the month",
			},
			salary: {
				required: "File is required",
				extension: "File must be XLSX or XLS"
			},
		},
		errorElement: 'span',
		errorPlacement: function (error, element) {
			error.addClass('invalid-feedback');
			element.closest('.form-group').append(error);
		},
		highlight: function (element, errorClass, validClass) {
			$(element).addClass('is-invalid');
		},
		unhighlight: function (element, errorClass, validClass) {
			$(element).removeClass('is-invalid');
		}
	});

	//validate file extension custom  method.
    jQuery.validator.addMethod("extension", function (value, element, param) {
        param = typeof param === "string" ? param.replace(/,/g, '|') : "xlsx|xls";
        alert(param);
        return this.optional(element) || value.match(new RegExp(".(" + param + ")$", "i"));
    }, jQuery.format("File must be XLSX or XLS."));

	
});
</script>
@endsection