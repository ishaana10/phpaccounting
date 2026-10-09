<?php if($_settings->chk_flashdata('success')): ?>
<script>
	alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>
<div class="card card-outline card-primary">
	<div class="card-header">
		<h3 class="card-title">Payroll Records</h3>
		<div class="card-tools">
			<a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Process Payroll</a>
		</div>
	</div>
	<div class="card-body">
		<div class="container-fluid">
			<table class="table table-hover table-striped table-bordered" id="list">
				<colgroup>
					<col width="5%">
					<col width="15%">
					<col width="20%">
					<col width="12%">
					<col width="12%">
					<col width="12%">
					<col width="12%">
					<col width="12%">
				</colgroup>
				<thead>
					<tr>
						<th>#</th>
						<th>Salary Month</th>
						<th>Employee</th>
						<th>Basic Salary</th>
						<th>Allowances</th>
						<th>Deductions</th>
						<th>Net Salary</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$i = 1;
						$qry = $conn->query("SELECT p.*, concat(e.firstname, ' ', e.lastname) as emp_name, e.employee_code from `payroll_list` p inner join `employee_list` e on p.employee_id = e.id order by p.id desc ");
						while($row = $qry->fetch_assoc()):
					?>
						<tr>
							<td class="text-center"><?php echo $i++; ?></td>
							<td><b><?php echo $row['salary_month'] ?></b></td>
							<td>
								<p class="m-0"><?php echo $row['emp_name'] ?></p>
								<small class="text-muted"><?php echo $row['employee_code'] ?></small>
							</td>
							<td class="text-right"><?php echo number_format($row['basic_salary'], 2) ?></td>
							<td class="text-right text-success">+<?php echo number_format($row['allowances'], 2) ?></td>
							<td class="text-right text-danger">-<?php echo number_format($row['deductions'], 2) ?></td>
							<td class="text-right font-weight-bold"><?php echo number_format($row['net_salary'], 2) ?></td>
							<td align="center">
								 <button type="button" class="btn btn-flat p-1 btn-default btn-sm dropdown-toggle dropdown-icon" data-toggle="dropdown">
							Action
				                    <span class="sr-only">Toggle Dropdown</span>
				                  </button>
				                  <div class="dropdown-menu" role="menu">
				                    <a class="dropdown-item view_payslip" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-file-invoice-dollar text-info"></span> Payslip</a>
				                    <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-edit text-primary"></span> Edit</a>
				                    <div class="dropdown-divider"></div>
				                    <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?php echo $row['id'] ?>"><span class="fa fa-trash text-danger"></span> Delete</a>
				                  </div>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
<script>
	$(document).ready(function(){
		$('.delete_data').click(function(){
			_conf("Are you sure to delete this payroll record? Associated journal entries will also be removed.","delete_payroll",[$(this).attr('data-id')])
		})
		$('#create_new').click(function(){
			uni_modal("<i class='fa fa-plus'></i> Process Payroll","payroll/manage_payroll.php", 'mid-large')
		})
		$('.edit_data').click(function(){
			uni_modal("<i class='fa fa-edit'></i> Edit Payroll","payroll/manage_payroll.php?id="+$(this).attr('data-id'), 'mid-large')
		})
		$('.view_payslip').click(function(){
			uni_modal("<i class='fa fa-file-alt'></i> Employee Payslip","payroll/view_payslip.php?id="+$(this).attr('data-id'), 'large')
		})
		$('#list').dataTable({
			columnDefs: [
					{ orderable: false, targets: [7] }
			],
			order: [0, 'asc']
		});
	})
	function delete_payroll($id){
		start_loader();
		$.ajax({
			url:_base_url_+"classes/Master.php?f=delete_payroll",
			method:"POST",
			data:{id: $id},
			dataType:"json",
			error:err=>{
				console.log(err)
				alert_toast("An error occurred.",'error');
				end_loader();
			},
			success:function(resp){
				if(typeof resp== 'object' && resp.status == 'success'){
					location.reload();
				}else{
					alert_toast("An error occurred.",'error');
					end_loader();
				}
			}
		})
	}
</script>
