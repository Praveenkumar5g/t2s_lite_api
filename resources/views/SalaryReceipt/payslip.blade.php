<html>
<head>
	<style>
		table{
			width: 100%;
			border-collapse:collapse;
			border: 1px solid black;
		}
		table td{line-height:25px;padding-left:15px;}
/*		table th{background-color:#fbc403; color:#363636;}*/
	</style>

</head>
<body>
	<table border="1">
		<tr  style="text-align:center;font-size:24px; font-weight:600;height:1000px">
			<td style="line-height: 100px !important;"><img class="animation__shake" src="{!! asset('assets/images/AdminLTELogo.png" alt="AdminLTELogo') !!}" height="60" width="60"></td>
			<td style="line-height: 80px !important;" colspan='3'><p style="margin-top: 1%">{{$school_name}}</p></td>
		</tr>
		<tr height="90px" style="text-align:center;font-size:12px; font-weight:200">
			<td colspan='4'>PaySlip for month {{$month}}</td>
		</tr>
		<tr height="200px" style="text-align:left;font-size:16px;margin-top: 1px;">
			<td colspan="2"><strong>Name  : </strong><span style="text-align:center;">{{$name}}</span></td>
			<td colspan="2"><strong>Emp ID : </strong><span style="text-align:center;">{{$employee_no}} </span></td>
		</tr>
		<tr>
			<th>Department:</th>
			<td>{{$department}}</td>
			<th>Bank A/C No</th>
			<td>{{$account_no}}</td>
		</tr>
		<tr>
			<th>Designation</th>
			<td>{{$designation}}</td>
			<th>PF UAN No.</th>
			<td>{{$uan}}</td>
		</tr>
		<tr>
			<th>DOJ</th>
			<td>{{$doj}}</td>
			<th>ESI</th>
			<td>{{$esi}}</td>
		</tr>
		<tr>
			<th>CTC</th>
			<td>{{number_format($ctc,2)}}</td>
			<th></th>
			<td></td>
		</tr>
		<tr>
			<th>W.Days CTC</th>
			<td>{{number_format($working_ctc,2)}}</td>
			<th></th>
			<td></td>
		</tr>
	</table>
	<table style="margin-top: 2px;" >
		<tr height="110px" style="text-align:left;font-size:16px; border:1">
			<td colspan='2'><strong>Total Number of Working  : </strong> - </td>
			<td colspan='2'><strong>Paid Number of Days :</strong> {{$paid_days}} </td>
		</tr>
	</table>
	<table border="1" style="margin-top: 2px;">
		<tr>
			<th colspan="2">Earnings</th>
			<th colspan="2">Deductions</th>
		</tr>
		<tr>
			<td>Basic + DA</td>
			<td>{{number_format($basic_da,2)}}</td>
			<td>LOP</td>
			<td>{{number_format($lop,2)}}</td>
		</tr>
		<tr>
			<td>HRA</td>
			<td>{{number_format($hra,2)}}</td>
			<td>LLP</td>
			<td>{{number_format($llp,1)}}</td>
		</tr>
		<tr>
			<td>OT</td>
			<td>{{number_format($ot,2)}}</td>
			<td>Employee ( PF + ESI)</td>
			<td>{{number_format($employee,2)}}</td>
		</tr>
		<tr>
			<td></td>
			<td></td>
			<td>Employer ( PF + ESI)</td>
			<td>{{number_format($employer,2)}}</td>
		</tr>		
		<tr>
			<td></td>
			<td></td>
			<td>Salary Advance</td>
			<td>{{number_format($advance,2)}}</td>
		</tr>
		<tr>
			<th>Gross Earnings </th>
			<td>{{number_format($working_ctc,2)}}</td>
			<th>Gross Deductions</th>
			<td>{{number_format(($working_ctc - $net),2)}}</td>
		</tr>
		<tr>
			<td colspan="3" style="text-align: center;">Reimbursement </td>
			<td style="border: 1px;">0.0</td>
		</tr>
		<tr>
			<td >Net Pay </td>
			<td colspan="2">{{$net_words}}</td>
			<td >{{number_format($net,2)}}</td>
		</tr>
	</table>
</body>
</html>