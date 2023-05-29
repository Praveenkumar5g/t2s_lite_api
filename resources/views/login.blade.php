<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>LiteChat</title>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
		<link rel="stylesheet" href="{!! asset('assets/css/all.min.css') !!}">
		<link rel="stylesheet" href="{!! asset('assets/css/icheck-bootstrap.min.css') !!}">
		<link rel="stylesheet" href="{!! asset('assets/css/adminlte.min.css') !!}">
	</head>
	<body class="hold-transition login-page">

		<div class="login-box">
			@if ($message = Session::get('error'))
				<div class="alert alert-danger alert-block">
					<button type="button" class="close" data-dismiss="alert">×</button>	
				        <strong>{{ $message }}</strong>
				</div>
			@endif
			<div class="card card-outline card-primary">
				<div class="card-header text-center">
					<a href="../../index2.html" class="h1"><b>Lite</b>Chat</a>
				</div>
				<div class="card-body">
					<p class="login-box-msg">Sign in</p>

					<form class="login85-form validate-form" name="loginform" id="loginform" method="post" action="{{url('login')}}" >
						@csrf
						
						<div class="input-group mb-3">
							<input type="text" class="form-control" name="user_mobile_number" placeholder="Mobile Number">
							<div class="input-group-append">
								<div class="input-group-text">
									<span class="fas fa-phone"></span>
								</div>
							</div>
						</div>
						<div class="input-group mb-3">
							<input type="password" class="form-control" name="password" placeholder="Password">
							<div class="input-group-append">
								<div class="input-group-text">
									<span class="fas fa-lock"></span>
								</div>
							</div>
							<input type="hidden" class="form-control" name="user_role" value=1>
						</div>
						<div class="row">
							<div class="col-8">

							</div>
							<div class="col-4">
								<button type="submit" class="btn btn-primary btn-block">Sign In</button>
							</div>
						</div>
					</form>

					<p class="mb-1">
						<a href="forgot-password.html">I forgot my password</a>
					</p>

				</div>
			</div>
		</div>
		<script src="{!! asset('assets/js/jquery.min.js') !!}"></script>
		<script src="{!! asset('assets/js/bootstrap.bundle.min.js') !!}"></script>
		<script src="{!! asset('assets/js/adminlte.min.js') !!}"></script>
		<script type="text/javascript" src="{!! asset('assets/js/jquery.validate.min.js') !!}"></script>
		<script type="text/javascript" src="{!! asset('assets/js/additional-methods.min.js') !!}"></script>
		<script>
			$(document).ready(function(){

				$('#loginform').validate({
					rules: {
						user_mobile_number:{
							required: true,
						},
						password: {
							required: true, 
						},
					},
					messages: {
						user_mobile_number: {
							required: "Mobile Number field is required",
						},
						password: {
							required: "Password field is required",
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

			});
		</script>
	</body>
</html>