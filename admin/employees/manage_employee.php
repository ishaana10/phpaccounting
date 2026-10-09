<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT * from `employee_list` where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<div class="container-fluid">
	<form action="" id="employee-form">
		<input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
		<div class="form-group">
			<label for="employee_code" class="control-label">Employee Code</label>
			<input type="text" name="employee_code" id="employee_code" class="form-control form-control-sm rounded-0" value="<?php echo isset($employee_code) ? $employee_code : ''; ?>" required/>
		</div>
		<div class="row">
			<div class="col-md-6 form-group">
				<label for="firstname" class="control-label">First Name</label>
				<input type="text" name="firstname" id="firstname" class="form-control form-control-sm rounded-0" value="<?php echo isset($firstname) ? $firstname : ''; ?>" required/>
			</div>
			<div class="col-md-6 form-group">
				<label for="lastname" class="control-label">Last Name</label>
				<input type="text" name="lastname" id="lastname" class="form-control form-control-sm rounded-0" value="<?php echo isset($lastname) ? $lastname : ''; ?>" required/>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 form-group">
				<label for="phone" class="control-label">Phone</label>
				<input type="text" name="phone" id="phone" class="form-control form-control-sm rounded-0" value="<?php echo isset($phone) ? $phone : ''; ?>" />
			</div>
			<div class="col-md-6 form-group">
				<label for="salary" class="control-label">Monthly Salary</label>
				<input type="number" step="any" name="salary" id="salary" class="form-control form-control-sm rounded-0 text-right" value="<?php echo isset($salary) ? $salary : 0; ?>" required/>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 form-group">
				<label for="department" class="control-label">Department</label>
				<input type="text" name="department" id="department" class="form-control form-control-sm rounded-0" value="<?php echo isset($department) ? $department : ''; ?>" />
			</div>
			<div class="col-md-6 form-group">
				<label for="designation" class="control-label">Designation</label>
				<input type="text" name="designation" id="designation" class="form-control form-control-sm rounded-0" value="<?php echo isset($designation) ? $designation : ''; ?>" />
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 form-group">
				<label for="date_of_joining" class="control-label">Date of Joining</label>
				<input type="date" name="date_of_joining" id="date_of_joining" class="form-control form-control-sm rounded-0" value="<?php echo isset($date_of_joining) ? $date_of_joining : date('Y-m-d'); ?>" />
			</div>
			<div class="col-md-6 form-group">
				<label for="status" class="control-label">Status</label>
				<select name="status" id="status" class="form-control form-control-sm rounded-0" required>
					<option value="1" <?php echo isset($status) && $status == 1 ? 'selected' : '' ?>>Active</option>
					<option value="0" <?php echo isset($status) && $status == 0 ? 'selected' : '' ?>>Inactive</option>
				</select>
			</div>
		</div>
	</form>
</div>
<script>
	$(document).ready(function(){
		$('#employee-form').submit(function(e){
			e.preventDefault();
            var _this = $(this)
			 $('.err-msg').remove();
			start_loader();
			$.ajax({
				url:_base_url_+"classes/Master.php?f=save_employee",
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
