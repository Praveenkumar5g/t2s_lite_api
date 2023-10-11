<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>LiteChat</title>
		<meta name="csrf-token" content="{{ csrf_token() }}" />
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
		<link rel="stylesheet" href="{!! asset('assets/css/all.min.css') !!}">
		<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
		<link rel="stylesheet" href="{!! asset('assets/css/tempusdominus-bootstrap-4.min.css') !!}">
		<link rel="stylesheet" href="{!! asset('assets/css/icheck-bootstrap.min.css') !!}">
		<link rel="stylesheet" href="{!! asset('assets/css/adminlte.min.css') !!}">
		<link rel="stylesheet" href="{!! asset('assets/css/OverlayScrollbars.min.css') !!}">
		<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker.min.css">
		<link rel="stylesheet" href="{!! asset('assets/css/dataTables.bootstrap4.min.css') !!}">
		<link rel="stylesheet" href="{!! asset('assets/css/responsive.bootstrap4.min.css') !!}">
		<link rel="stylesheet" href="{!! asset('assets/css/buttons.bootstrap4.min.css') !!}">
		<link rel="stylesheet" href="{!! asset('assets/css/custom.min.css') !!}">
		<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/sweetalert2@7.12.15/dist/sweetalert2.min.css'></link>

		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
		<link href="{!! asset('assets/css/select2.min.css') !!}" rel="stylesheet" type="text/css"/>
    	<link href="{!! asset('assets/css/select2-bootstrap.min.css') !!}" rel="stylesheet" type="text/css"/>
	</head>
	<body class="hold-transition sidebar-mini layout-fixed">
		<div class="wrapper">

			<div class="preloader flex-column justify-content-center align-items-center">
				<img class="animation__shake" src="{!! asset('assets/images/AdminLTELogo.png" alt="AdminLTELogo') !!}" height="60" width="60">
			</div>

			@include('layouts.nav')

			@include('layouts.sidebar')

			@yield('content')
			<footer class="main-footer">
				<strong>Copyright &copy; 2022-{{date("Y")}} <a href="javascript:void(0);">TimetoSchool.com</a>.</strong>
				All rights reserved.
			</footer>
			<aside class="control-sidebar control-sidebar-dark">
			</aside>
		</div>

		<script src="{!! asset('assets/js/jquery.min.js') !!}"></script>
		<script src="{!! asset('assets/js/jquery-ui.min.js') !!}"></script>
		<script>
			$.widget.bridge('uibutton', $.ui.button)
		</script>
		<script src="{!! asset('assets/js/bootstrap.bundle.min.js') !!}"></script>
		<script src="{!! asset('assets/js/sparkline.js') !!}"></script>
		<script src="{!! asset('assets/js/jquery.knob.min.js') !!}"></script>
		<script src="{!! asset('assets/js/tempusdominus-bootstrap-4.min.js') !!}"></script>
		<script src="{!! asset('assets/js/jquery.overlayScrollbars.min.js') !!}"></script>
		<script src="{!! asset('assets/js/adminlte.min.js') !!}"></script>
		<script src="{!! asset('assets/js/demo.js') !!}"></script>
		<script src="{!! asset('assets/js/dashboard.js') !!}"></script>
		<script type="text/javascript" src="{!! asset('assets/js/jquery.validate.min.js') !!}"></script>
		<script type="text/javascript" src="{!! asset('assets/js/additional-methods.min.js') !!}"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.min.js"></script>
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@7.12.15/dist/sweetalert2.all.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script> 
		<script  src="{!! asset('assets/js/select2.full.min.js') !!}"  type="text/javascript"></script>
		<script type="text/javascript">
			$.ajaxSetup({
			    headers: {
			        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			    }
			});
		</script>
		@yield('script')
	</body>
</html>