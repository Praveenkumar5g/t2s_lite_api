<aside class="main-sidebar sidebar-dark-primary elevation-4">
	<a href="javascript:void(0);" class="brand-link">
		<img src="{!! asset('assets/images/AdminLTELogo.png') !!}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
		<span class="brand-text font-weight-light">LiteChat</span>
	</a>

	<div class="sidebar">
		<div class="user-panel mt-3 pb-3 mb-3 d-flex">
			<div class="image">
				<img src="{!! asset('assets/images/user2-160x160.jpg') !!}" class="img-circle elevation-2" alt="User Image">
			</div>
			<div class="info">
				<?php $user_data = Session::get('user_data');
					if($user_data->user_role == 1)
	            		$user_admin = App\Models\UserAdmin::where(['user_id'=>$user_data->user_id])->pluck('first_name')->first();
				?>
				<a href="#" class="d-block">{{$user_admin}}</a>
			</div>
		</div>

		<nav class="mt-2">
			<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
<!-- 				<li class="nav-item menu-open">
					<a href="#" class="nav-link active">
						<i class="nav-icon fas fa-tachometer-alt"></i>
						<p>
							Dashboard
							<i class="right fas fa-angle-left"></i>
						</p>
					</a>
					<ul class="nav nav-treeview">
						<li class="nav-item">
							<a href="./index.html" class="nav-link active">
								<i class="far fa-circle nav-icon"></i>
								<p>Dashboard v1</p>
							</a>
						</li>
					</ul>
				</li>  -->
				<li class="nav-item {{ Request::segment(count(request()->segments())-1) == 'usermanagement' ? 'menu-open' : ''}}">
					<a href="#" class="{{ Request::segment(count(request()->segments())-1) == 'usermanagement' ? 'nav-link active' : 'nav-link'}}">
						<i class="nav-icon fas fa-money-bill"></i>
						<p>
							User Management
							<i class="fas fa-angle-left right"></i>
							<span class="badge badge-info right">2</span>
						</p>
					</a>
					<ul class="nav nav-treeview">
						<li class="nav-item">
							<a href="{{ url('usermanagement/students') }}" class="{{ Request::segment(count(request()->segments())) == 'students' ? 'nav-link active' : 'nav-link'}}">
								<i class="fas fa-user nav-icon"></i>
								<p>Students</p>
							</a>
						</li>
					</ul>
					<ul class="nav nav-treeview">
						<li class="nav-item">
							<a href="{{ url('usermanagement/unmappedstudents') }}" class="{{ Request::segment(count(request()->segments())) == 'unmappedstudents' ? 'nav-link active' : 'nav-link'}}">
								<i class="fas fa-user nav-icon"></i>
								<p>Unmapped Students</p>
							</a>
						</li>
					</ul>
					<ul class="nav nav-treeview">
						<li class="nav-item">
							<a href="{{ url('usermanagement/parents') }}" class="{{ Request::segment(count(request()->segments())) == 'parents' ? 'nav-link active' : 'nav-link'}}">
								<i class="fas fa-user nav-icon"></i>
								<p>Parents</p>
							</a>
						</li>
					</ul>
					<ul class="nav nav-treeview">
						<li class="nav-item">
							<a href="{{ url('usermanagement/welcome_message') }}" class="{{ Request::segment(count(request()->segments())) == 'welcome_message' ? 'nav-link active' : 'nav-link'}}">
								<i class="fas fa-user nav-icon"></i>
								<p>Send Welcome Message</p>
							</a>
						</li>
					</ul>
				</li>
				<li class="nav-item {{ Request::segment(count(request()->segments())-1) == 'employee' ? 'menu-open' : ''}}">
					<a href="#" class="{{ Request::segment(count(request()->segments())-1) == 'employee' ? 'nav-link active' : 'nav-link'}}">
						<!-- <i class="nav-icon fa fa-money"></i> -->
						<i class="nav-icon fas fa-money-bill"></i>
						<p>
							Salary
							<i class="fas fa-angle-left right"></i>
							<span class="badge badge-info right">2</span>
						</p>
					</a>
					<ul class="nav nav-treeview">
						<li class="nav-item">
							<a href="{{ url('employee/uploadsalarydetails') }}" class="{{ Request::segment(count(request()->segments())) == 'uploadsalarydetails' ? 'nav-link active' : 'nav-link'}}">
								<i class="fas fa-upload nav-icon"></i>
								<p>Upload Salary Details</p>
							</a>
						</li>
						<li class="nav-item">
							<a href="{{ url('employee/salarydetails') }}" class="{{ Request::segment(count(request()->segments())) == 'salarydetails' ? 'nav-link active' : 'nav-link'}}">
								<i class="fas fa-list nav-icon"></i>
								<p>List</p>
							</a>
						</li>
					</ul>
				</li>
				<li class="nav-item">
		            <a href="{{ url('logout') }}" class="nav-link">
		              <i class="nav-icon fas fa-window-close"></i>
		              	<p>
		                	Logout
		              	</p>
		            </a>
		        </li>
			</ul>
		</nav>
	</div>
</aside>