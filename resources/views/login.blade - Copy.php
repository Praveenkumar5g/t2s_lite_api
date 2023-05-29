<!DOCTYPE html>
<html lang="en">
<head>
	<title>Chat</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">	
	<link rel="icon" type="image/png" href="images/icons/favicon.ico"/>
	<link rel="stylesheet" type="text/css" href="{!! asset('public/assets/css/bootstrap.min.css') !!}">
	<link rel="stylesheet" type="text/css" href="{!! asset('public/assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css') !!}">
	<link rel="stylesheet" type="text/css" href="{!! asset('public/assets/css/animate.css') !!}">
	<link rel="stylesheet" type="text/css" href="{!! asset('public/assets/css/hamburgers.min.css') !!}">
	<link rel="stylesheet" type="text/css" href="{!! asset('public/assets/css/select2.min.css') !!}">
	<link rel="stylesheet" type="text/css" href="{!! asset('public/assets/css/util.css') !!}">
	<link rel="stylesheet" type="text/css" href="{!! asset('public/assets/css/main.css') !!}">
	<style type="text/css">
		.alert{
			padding-top: 5% !important;
		}
	</style>
</head>
<body>
	
	<div class="limiter">
		<div class="container-login100">
			
			<div class="wrap-login100">
				<div class="login100-pic js-tilt" data-tilt>
					<img src="{!! asset('public/assets/images/img-01.png') !!}" alt="IMG">
				</div>
				<form class="login85-form validate-form" method="post" action="{{url('login')}}" >
      				@csrf
					<span class="login100-form-title">
						Login
					</span>
					@if ($message = Session::get('error'))
					<div class="alert alert-danger alert-block">
						<button type="button" class="close" data-dismiss="alert">×</button>	
					        <strong>{{ $message }}</strong>
					</div>
					@endif

					<div class="wrap-input100 validate-input" data-validate = "Mobile Number is required">
						<input class="input100" type="text" name="user_mobile_number" placeholder="Mobile Number">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-phone" aria-hidden="true"></i>
						</span>
					</div>

					<div class="wrap-input100 validate-input" data-validate = "Password is required">
						<input class="input100" type="password" name="password" placeholder="Password">
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
					</div>
					<input type="hidden" name="user_role" value=1 >
					<!-- <div class="wrap-input100 validate-input" data-validate = "Password is required">
						<select name="user_role">
							<option value=Config::get('app.Admin'></option>
						</select>
						<span class="focus-input100"></span>
						<span class="symbol-input100">
							<i class="fa fa-lock" aria-hidden="true"></i>
						</span>
					</div>
					 -->
					<div class="container-login100-form-btn">
						<button class="login100-form-btn">
							Login
						</button>
					</div>

					<div class="text-center p-t-12">
						<span class="txt1">
							Forgot
						</span>
						<a class="txt2" href="#">
							Username / Password?
						</a>
					</div>

					<div class="text-center p-t-70">
						
					</div>
				</form>
			</div>
		</div>
	</div>	
	<script src="{!! asset('public/assets/js/jquery-3.2.1.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/popper.js') !!}"></script>
	<script src="{!! asset('public/assets/js/bootstrap.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/select2.min.js') !!}"></script>
	<script src="{!! asset('public/assets/js/tilt.jquery.min.js') !!}"></script>
	<script >
		$('.js-tilt').tilt({
			scale: 1.1
		})
	</script>
	<script src="{!! asset('public/assets/js/main.js') !!}"></script>

</body>
</html>