<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `payroll_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<div class="container-fluid">
	<form action="" id="payroll-form">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
		<div class="form-group">
			<label for="employee_id" class="control-label">Employee</label>
			<select name="employee_id" id="employee_id" class="form-control form-control-sm rounded-0 select2" required>
				<option value="" disabled <?php echo !isset($employee_id) ? "selected" : "" ?>></option>
				<?php
				$emp_qry = $conn->query("SELECT *, concat(firstname, ' ', lastname) as name FROM `employee_list` where status = 1 and delete_flag = 0 order by firstname asc");
				while($row = $emp_qry->fetch_assoc()):
				?>
				<option value="<?php echo $row['id'] ?>" data-salary="<?php echo $row['salary'] ?>" <?php echo isset($employee_id) && $employee_id == $row['id'] ? 'selected' : '' ?>><?php echo $row['employee_code'] . ' - ' . $row['name'] ?></option>
				<?php endwhile; ?>
			</select>
		</div>
		<div class="form-group">
			<label for="salary_month" class="control-label">Salary Period / Month</label>
			<input type="text" name="salary_month" id="salary_month" placeholder="e.g. September 2024" class="form-control form-control-sm rounded-0" value="<?php echo isset($salary_month) ? $salary_month : date('F Y'); ?>" required/>
		</div>
		<div class="row">
			<div class="col-md-6 form-group">
				<label for="basic_salary" class="control-label">Basic Salary</label>
				<input type="number" step="any" name="basic_salary" id="basic_salary" class="form-control form-control-sm rounded-0 text-right calc-net" value="<?php echo isset($basic_salary) ? $basic_salary : 0; ?>" required/>
			</div>
			<div class="col-md-6 form-group">
				<label for="allowances" class="control-label">Allowances</label>
				<input type="number" step="any" name="allowances" id="allowances" class="form-control form-control-sm rounded-0 text-right calc-net" value="<?php echo isset($allowances) ? $allowances : 0; ?>" required/>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 form-group">
				<label for="deductions" class="control-label">Deductions</label>
				<input type="number" step="any" name="deductions" id="deductions" class="form-control form-control-sm rounded-0 text-right calc-net" value="<?php echo isset($deductions) ? $deductions : 0; ?>" required/>
			</div>
			<div class="col-md-6 form-group">
				<label for="net_salary" class="control-label">Net Salary</label>
				<input type="number" step="any" name="net_salary" id="net_salary" class="form-control form-control-sm rounded-0 text-right bg-light" value="<?php echo isset($net_salary) ? $net_salary : 0; ?>" readonly required/>
			</div>
		</div>
	</form>
</div>
<script>
	function calculate_net(){
		var basic = parseFloat($('#basic_salary').val()) || 0;
		var allowances = parseFloat($('#allowances').val()) || 0;
		var deductions = parseFloat($('#deductions').val()) || 0;
		var net = basic + allowances - deductions;
		$('#net_salary').val(net.toFixed(2));
	}

	$(document).ready(function(){
		$('.select2').select2({
			placeholder:"Please select here",
			width:'100%',
			dropdownParent:$('#uni_modal')
		})

		$('#employee_id').change(function(){
			var sal = $(this).find(':selected').attr('data-salary');
			if(sal && !$('#basic_salary').val() || $('#basic_salary').val() == 0){
				$('#basic_salary').val(sal);
				calculate_net();
			}
		});

		$('.calc-net').on('input change', function(){
			calculate_net();
		});

		$('#payroll-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			 $('.err-msg').remove();
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_payroll",
				data: new FormData($(this)[0]),
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
				error:err=>{
					console.log(err)
					alert_toast("An error occurred",'error');
					end_loader();
				},
				success:function(resp){
					if(typeof resp =='object' && resp.status == 'success'){
						location.reload()
					}else if(resp.status == 'failed' && !!resp.msg){
                        var el = $('<div>')
                            el.addClass("alert alert-danger err-msg").text(resp.msg)
                            _this.prepend(el)
                            el.show('slow')
                            end_loader()
                    }else{
						alert_toast("An error occurred",'error');
						end_loader();
                        console.log(resp)
					}
				}
			})
		})
	})
</script>
