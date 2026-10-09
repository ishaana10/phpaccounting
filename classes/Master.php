<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_group(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `group_list` set {$data} ";
		}else{
			$sql = "UPDATE `group_list` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `group_list` where `name` = '{$name}' and delete_flag = 0 ".($id > 0 ? " and id != '{$id}'" : ""));
		if($check->num_rows > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = " Account's Group Name already exists.";
		}else{
			$save = $this->conn->query($sql);
			if($save){
				$gid = !empty($id) ? $id : $this->conn->insert_id;
				$resp['status'] = 'success';
				if(empty($id))
					$resp['msg'] = " Account's Group has successfully added.";
				else
					$resp['msg'] = " Account's Group details has been updated successfully.";
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured.";
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_group(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `group_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Account's Group has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_account(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `account_list` set {$data} ";
		}else{
			$sql = "UPDATE `account_list` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `account_list` where `name` ='{$name}' and delete_flag = 0 ".($id > 0 ? " and id != '{$id}' " : ""))->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = " Account's Name already exists.";
		}else{
			$save = $this->conn->query($sql);
			if($save){
				$rid = !empty($id) ? $id : $this->conn->insert_id;
				$resp['status'] = 'success';
				if(empty($id))
					$resp['msg'] = " Account has successfully added.";
				else
					$resp['msg'] = " Account has been updated successfully.";
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured.";
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
			if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		}
		return json_encode($resp);
	}
	function delete_account(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `account_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Account has been deleted successfully.");

		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_journal(){
		if(empty($_POST['id'])){
			$prefix = date("Ym-");
			$code = sprintf("%'.05d",1);
			while(true){
				$check = $this->conn->query("SELECT * FROM `journal_entries` where `code` = '{$prefix}{$code}' ")->num_rows;
				if($check > 0){
					$code = sprintf("%'.05d",ceil($code) + 1);
				}else{
					break;
				}
			}
			$_POST['code'] = $prefix.$code;
			$_POST['user_id'] = $this->settings->userdata('id');
		}
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))  && !is_array($_POST[$k])){
				if(!is_numeric($v) && !is_null($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				if(!is_null($v))
				$data .= " `{$k}`='{$v}' ";
				else
				$data .= " `{$k}`= NULL ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `journal_entries` set {$data} ";
		}else{
			$sql = "UPDATE `journal_entries` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$jid = !empty($id) ? $id : $this->conn->insert_id;
			$data = "";
			$this->conn->query("DELETE FROM `journal_items` where journal_id = '{$jid}'");
			foreach($account_id as $k=>$v){
				if(!empty($data)) $data .=", ";
				$data .= "('{$jid}','{$v}','{$group_id[$k]}','{$amount[$k]}')";
			}
			if(!empty($data)){
				$sql = "INSERT INTO `journal_items` (`journal_id`,`account_id`,`group_id`,`amount`) VALUES {$data}";
				$save2 = $this->conn->query($sql);
				if($save2){
					$resp['status'] = 'success';
					if(empty($id)){
						$resp['msg'] = " Journal Entry has successfully added.";
					}else
						$resp['msg'] = " Journal Entry has been updated successfully.";
				}else{
					$resp['status'] = 'failed';
					if(empty($id)){
						$resp['msg'] = " Journal Entry has failed to save.";
						$this->conn->query("DELETE FROM `journal_entries` where id = '{$jid}'");
					}else
						$resp['msg'] = " Journal Entry has failed to update.";
					$resp['error'] = $this->conn->error;
				}
			}else{
				$resp['status'] = 'failed';
				if(empty($id)){
					$resp['msg'] = " Journal Entry has failed to save.";
					$this->conn->query("DELETE FROM `journal_entries` where id = '{$jid}'");
				}else
					$resp['msg'] = " Journal Entry has failed to update.";
				$resp['error'] = "Journal Items is empty";
			}
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_journal(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `journal_entries` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Journal Entry has been deleted successfully.");

		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function cancel_journal(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `journal_entries` set `status` = '3' where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," journaling has successfully cancelled.");

		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function save_reservation(){
		$_POST['journal'] = $_POST['date'] ." ".$_POST['time'];
		extract($_POST);
		$capacity = $this->conn->query("SELECT `".($seat_type == 1 ? "first_class_capacity" : "economy_capacity")."` FROM group_list where id in (SELECT group_id FROM `journal_entries` where id ='{$journal_id}') ")->fetch_array()[0];
		$reserve = $this->conn->query("SELECT * FROM `reservation_list` where journal_id = '{$journal_id}' and journal='{$journal}' and seat_type='$seat_type'")->num_rows;
		$slot = $capacity - $reserve;
		if(count($firstname) > $slot){
			$resp['status'] = "failed";
			$resp['msg'] = "This journal has only [{$slot}] left for the selected seat type/group";
			return json_encode($resp);
		}
		$data = "";
		$sn = [];
		$prefix = $seat_type == 1 ? "FC-" : "E-";
		$seat = sprintf("%'.03d",1);
		foreach($firstname as $k=>$v){
			while(true){
				$check = $this->conn->query("SELECT * FROM `reservation_list` where journal_id = '{$journal_id}' and journal='{$journal}' and seat_num = '{$prefix}{$seat}' and seat_type='$seat_type'")->num_rows;
				if($check > 0){
					$seat = sprintf("%'.03d",ceil($seat) + 1);
				}else{
					break;
				}
			}
			$seat_num = $prefix.$seat;
			$seat = sprintf("%'.03d",ceil($seat) + 1);
			$sn[] = $seat_num;
			if(!empty($data)) $data .= ", ";
			$data .= "('{$seat_num}','{$journal_id}','{$journal}','{$v}','{$middlename[$k]}','{$lastname[$k]}','{$seat_type}','{$fare_amount}')";
		}
		if(!empty($data)){
			$sql = "INSERT INTO `reservation_list` (`seat_num`,`journal_id`,`journal`,`firstname`,`middlename`,`lastname`,`seat_type`,`fare_amount`) VALUES {$data}";
			$save_all = $this->conn->query($sql);
			if($save_all){
				$resp['status'] = 'success';
				$resp['msg'] = "Reservation successfully submitted.";
				$get_ids = $this->conn->query("SELECT id from `reservation_list` where `journal_id` = '{$journal_id}' and `journal` = '{$journal}' and seat_type='{$seat_type}' and seat_num in ('".(implode("','",$sn))."') ");
				$res = $get_ids->fetch_all(MYSQLI_ASSOC);
				$ids = array_column($res,'id');
				$ids = implode(",",$ids);
				$resp['ids'] = $ids;
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occured while saving the data. Error: ".$this->conn->error;
				$resp['sql'] = $sql;
			}
		}else{
			$resp['status'] = "failed";
			$resp['msg'] = "No Data to save.";
		}
		

		if($resp['status'] =='success')
		$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}
	function delete_reservation(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `reservation_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Reservation Details has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
	function update_reservation_status(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `reservation_list` set `status` = '{$status}' where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"reservation Request status has successfully updated.");

		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	/* Employee Management Functions */
	function save_employee(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `employee_list` set {$data} ";
		}else{
			$sql = "UPDATE `employee_list` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `employee_list` where `employee_code` = '{$employee_code}' and delete_flag = 0 ".($id > 0 ? " and id != '{$id}'" : ""))->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = " Employee Code already exists.";
		}else{
			$save = $this->conn->query($sql);
			if($save){
				$resp['status'] = 'success';
				if(empty($id))
					$resp['msg'] = " Employee has been successfully added.";
				else
					$resp['msg'] = " Employee details have been updated successfully.";
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occurred.";
				$resp['err'] = $this->conn->error."[{$sql}]";
			}
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}

	function delete_employee(){
		extract($_POST);
		$del = $this->conn->query("UPDATE `employee_list` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Employee has been deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	/* Attendance Management Functions */
	function save_attendance(){
		extract($_POST);
		$employee_id = $this->conn->real_escape_string($employee_id);
		$attendance_date = $this->conn->real_escape_string($attendance_date);
		$status = $this->conn->real_escape_string($status);

		$check = $this->conn->query("SELECT * FROM `attendance_list` where `employee_id` = '{$employee_id}' and `attendance_date` = '{$attendance_date}' ".(!empty($id) && $id > 0 ? " and id != '{$id}'" : ""))->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = " Attendance record for this employee on the selected date already exists.";
			return json_encode($resp);
		}
		$check_in = !empty($check_in) ? "'".$this->conn->real_escape_string($check_in)."'" : "NULL";
		$check_out = !empty($check_out) ? "'".$this->conn->real_escape_string($check_out)."'" : "NULL";

		if(empty($id)){
			$sql = "INSERT INTO `attendance_list` (`employee_id`, `attendance_date`, `check_in`, `check_out`, `status`) VALUES ('{$employee_id}', '{$attendance_date}', {$check_in}, {$check_out}, '{$status}')";
		}else{
			$sql = "UPDATE `attendance_list` SET `employee_id`='{$employee_id}', `attendance_date`='{$attendance_date}', `check_in`={$check_in}, `check_out`={$check_out}, `status`='{$status}' WHERE id = '{$id}'";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			$resp['msg'] = empty($id) ? " Attendance record saved successfully." : " Attendance record updated successfully.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occurred while saving attendance.";
			$resp['err'] = $this->conn->error;
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}

	function delete_attendance(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `attendance_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Attendance record deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	/* Leave Management Functions */
	function save_leave(){
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(!in_array($k,array('id'))){
				if(!is_numeric($v))
					$v = $this->conn->real_escape_string($v);
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `leave_list` set {$data} ";
		}else{
			$sql = "UPDATE `leave_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			$resp['msg'] = empty($id) ? " Leave request submitted successfully." : " Leave request updated successfully.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occurred while saving leave request.";
			$resp['err'] = $this->conn->error;
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}

	function update_leave_status(){
		extract($_POST);
		$update = $this->conn->query("UPDATE `leave_list` set `status` = '{$status}' where id = '{$id}'");
		if($update){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Leave status updated successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	function delete_leave(){
		extract($_POST);
		$del = $this->conn->query("DELETE FROM `leave_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Leave request deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}

	/* Payroll & Journal Integration Functions */
	function save_payroll(){
		extract($_POST);
		$employee_id = $this->conn->real_escape_string($employee_id);
		$salary_month = $this->conn->real_escape_string($salary_month);
		$basic_salary = $this->conn->real_escape_string($basic_salary);
		$allowances = $this->conn->real_escape_string($allowances);
		$deductions = $this->conn->real_escape_string($deductions);
		$net_salary = $this->conn->real_escape_string($net_salary);

		// Find or default account for Salaries Expense (Debit) and Cash/Payroll Payable (Credit)
		$salary_acc = $this->conn->query("SELECT id FROM `account_list` WHERE (`name` LIKE '%salary%' OR `name` LIKE '%expense%') AND delete_flag = 0 LIMIT 1");
		$salary_acc_id = ($salary_acc->num_rows > 0) ? $salary_acc->fetch_assoc()['id'] : 1;

		$cash_acc = $this->conn->query("SELECT id FROM `account_list` WHERE `name` LIKE '%cash%' AND delete_flag = 0 LIMIT 1");
		$cash_acc_id = ($cash_acc->num_rows > 0) ? $cash_acc->fetch_assoc()['id'] : 1;

		$group_debit = $this->conn->query("SELECT id FROM `group_list` WHERE `type` = '1' AND delete_flag = 0 LIMIT 1");
		$group_debit_id = ($group_debit && $group_debit->num_rows > 0) ? $group_debit->fetch_assoc()['id'] : 1;

		$group_credit = $this->conn->query("SELECT id FROM `group_list` WHERE `type` = '2' AND delete_flag = 0 LIMIT 1");
		$group_credit_id = ($group_credit && $group_credit->num_rows > 0) ? $group_credit->fetch_assoc()['id'] : 2;

		// Create Journal Entry
		$prefix = date("Ym-");
		$code = sprintf("%'.05d",1);
		while(true){
			$check = $this->conn->query("SELECT * FROM `journal_entries` where `code` = '{$prefix}{$code}' ")->num_rows;
			if($check > 0){
				$code = sprintf("%'.05d",ceil($code) + 1);
			}else{
				break;
			}
		}
		$journal_code = $prefix.$code;
		$user_id = $this->settings->userdata('id');
		$journal_date = date("Y-m-d");

		// Fetch employee name
		$emp_qry = $this->conn->query("SELECT *, concat(firstname, ' ', lastname) as name FROM `employee_list` WHERE id = '{$employee_id}'");
		$emp_name = ($emp_qry->num_rows > 0) ? $emp_qry->fetch_assoc()['name'] : "Employee #{$employee_id}";

		$description = $this->conn->real_escape_string("Payroll Disbursement for {$emp_name} for the period {$salary_month}");

		if(empty($id)){
			// Insert new journal entry
			$j_sql = "INSERT INTO `journal_entries` (`code`, `journal_date`, `description`, `user_id`, `status`) VALUES ('{$journal_code}', '{$journal_date}', '{$description}', '{$user_id}', 1)";
			$this->conn->query($j_sql);
			$journal_id = $this->conn->insert_id;

			// Insert journal items: Debit Salary Expense, Credit Cash
			$j_items = "INSERT INTO `journal_items` (`journal_id`, `account_id`, `group_id`, `amount`) VALUES
				('{$journal_id}', '{$salary_acc_id}', '{$group_debit_id}', '{$net_salary}'),
				('{$journal_id}', '{$cash_acc_id}', '{$group_credit_id}', '{$net_salary}')";
			$this->conn->query($j_items);

			$sql = "INSERT INTO `payroll_list` (`employee_id`, `journal_id`, `salary_month`, `basic_salary`, `allowances`, `deductions`, `net_salary`) VALUES ('{$employee_id}', '{$journal_id}', '{$salary_month}', '{$basic_salary}', '{$allowances}', '{$deductions}', '{$net_salary}')";
		} else {
			$existing = $this->conn->query("SELECT journal_id FROM `payroll_list` WHERE id = '{$id}'")->fetch_assoc();
			$journal_id = $existing['journal_id'];
			if($journal_id){
				$this->conn->query("UPDATE `journal_entries` SET `description` = '{$description}' WHERE id = '{$journal_id}'");
				$this->conn->query("DELETE FROM `journal_items` WHERE journal_id = '{$journal_id}'");
				$j_items = "INSERT INTO `journal_items` (`journal_id`, `account_id`, `group_id`, `amount`) VALUES
					('{$journal_id}', '{$salary_acc_id}', '{$group_debit_id}', '{$net_salary}'),
					('{$journal_id}', '{$cash_acc_id}', '{$group_credit_id}', '{$net_salary}')";
				$this->conn->query($j_items);
			}
			$sql = "UPDATE `payroll_list` SET `employee_id`='{$employee_id}', `salary_month`='{$salary_month}', `basic_salary`='{$basic_salary}', `allowances`='{$allowances}', `deductions`='{$deductions}', `net_salary`='{$net_salary}' WHERE id = '{$id}'";
		}

		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			$resp['msg'] = empty($id) ? " Payroll processed and Journal Entry created successfully." : " Payroll updated successfully.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occurred while saving payroll.";
			$resp['err'] = $this->conn->error;
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}

	function delete_payroll(){
		extract($_POST);
		$existing = $this->conn->query("SELECT journal_id FROM `payroll_list` WHERE id = '{$id}'")->fetch_assoc();
		if(!empty($existing['journal_id'])){
			$this->conn->query("DELETE FROM `journal_entries` WHERE id = '{$existing['journal_id']}'");
		}
		$del = $this->conn->query("DELETE FROM `payroll_list` WHERE id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Payroll record deleted successfully.");
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);
	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_reservation':
		echo $Master->save_reservation();
	break;
	case 'delete_reservation':
		echo $Master->delete_reservation();
	break;
	case 'update_reservation_status':
		echo $Master->update_reservation_status();
	break;
	case 'save_message':
		echo $Master->save_message();
	break;
	case 'delete_message':
		echo $Master->delete_message();
	break;
	case 'save_group':
		echo $Master->save_group();
	break;
	case 'delete_group':
		echo $Master->delete_group();
	break;
	case 'save_account':
		echo $Master->save_account();
	break;
	case 'delete_account':
		echo $Master->delete_account();
	break;
	case 'save_journal':
		echo $Master->save_journal();
	break;
	case 'delete_journal':
		echo $Master->delete_journal();
	break;
	case 'cancel_journal':
		echo $Master->cancel_journal();
	break;
	case 'save_employee':
		echo $Master->save_employee();
	break;
	case 'delete_employee':
		echo $Master->delete_employee();
	break;
	case 'save_attendance':
		echo $Master->save_attendance();
	break;
	case 'delete_attendance':
		echo $Master->delete_attendance();
	break;
	case 'save_leave':
		echo $Master->save_leave();
	break;
	case 'update_leave_status':
		echo $Master->update_leave_status();
	break;
	case 'delete_leave':
		echo $Master->delete_leave();
	break;
	case 'save_payroll':
		echo $Master->save_payroll();
	break;
	case 'delete_payroll':
		echo $Master->delete_payroll();
	break;
	default:
		// echo $sysset->index();
		break;
}