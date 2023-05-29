<!DOCTYPE html>
<html lang="en">
<head>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> -->
  <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script> -->
  <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script> -->
  <style>
  	.styled-table {
    border-collapse: collapse;
    margin: 25px 0;
    font-size: 0.9em;
    font-family: sans-serif;
    min-width: 400px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
}
.styled-table thead tr {
    background-color: #009879;
    color: #ffffff;
    text-align: left;
}
.styled-table th,
.styled-table td {
    padding: 12px 15px;
}
.styled-table tbody tr {
    border-bottom: 1px solid #dddddd;
}

.styled-table tbody tr:nth-of-type(even) {
    background-color: #f3f3f3;
}

.styled-table tbody tr:last-of-type {
    border-bottom: 2px solid #009879;
}
.styled-table tbody tr.active-row {
    font-weight: bold;
    color: #009879;
}
  </style>
</head>
<body>
<div style="text-align:center;font-family: sans-serif;">
	<h3>Salary</h3>
</div>
<table class="styled-table">
    <thead>
        <tr>
            <th>S.No</th>
            <th>User ID</th>
            <th>Employee No</th>
            <th>Employee Name</th>
            <th>Month-Year</th>
            <th>PF Acc No</th>
            <th>Salary</th>
        </tr>
    </thead>
    <tbody>
    	<?php
    		$index = 1;
    		foreach($data as $row) {

    			$time=strtotime($row['month']);
		        $month=date("F",$time).' - '.date("Y",$time);

		        $user_role = App\Models\SchoolUsers::where('user_id',$row['user_id'])->pluck('user_role')->first();

		        //fetch id from user all table to store notification triggered user
		        if($user_role == Config::get('app.Admin_role'))//check role and get current user id
		            $user_data = App\Models\UserAdmin::where(['user_id'=>$row['user_id']])->get()->first();
		        else if($user_role == Config::get('app.Management_role'))
		            $user_data = App\Models\UserManagements::where(['user_id'=>$row['user_id']])->get()->first();
		        else if($user_role == Config::get('app.Staff_role'))
		            $user_data = App\Models\UserStaffs::where(['user_id'=>$row['user_id']])->get()->first();

		        $school_name =App\Models\SchoolProfile::where('id',Session::get('user_data')->school_profile_id)->pluck('school_name')->first();
    	?>
    		<tr>
    			<td>{{$index}}</td>
    			<td>{{$row['user_id']}}</td>
    			<td>{{$row['employee_no']}}</td>
    			<td>{{ucfirst($user_data->first_name)}}</td>
    			<td>{{$month}}</td>
    			<td>{{$row['pfacc_no']}}</td>
    			<td>{{number_format($row['actual'],2)}}</td>

    		</tr>
        <?php $index++; } ?>
    </tbody>
</table>

</body>
</html>
