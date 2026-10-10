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
		$tenant_id = $this->settings->active_tenant_id();
		if(empty($id)){
			$sql = "INSERT INTO `group_list` set `tenant_id`='{$tenant_id}', {$data} ";
		}else{
			$sql = "UPDATE `group_list` set {$data} where id = '{$id}' and tenant_id = '{$tenant_id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `group_list` where `name` = '{$name}' and tenant_id = '{$tenant_id}' and delete_flag = 0 ".($id > 0 ? " and id != '{$id}'" : ""));
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
		$tenant_id = $this->settings->active_tenant_id();
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
			$sql = "INSERT INTO `account_list` set `tenant_id`='{$tenant_id}', {$data} ";
		}else{
			$sql = "UPDATE `account_list` set {$data} where id = '{$id}' and tenant_id = '{$tenant_id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `account_list` where `name` ='{$name}' and tenant_id = '{$tenant_id}' and delete_flag = 0 ".($id > 0 ? " and id != '{$id}' " : ""))->num_rows;
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
		$tenant_id = $this->settings->active_tenant_id();
		if(empty($_POST['id'])){
			$prefix = date("Ym-");
			$code = sprintf("%'.05d",1);
			while(true){
				$check = $this->conn->query("SELECT * FROM `journal_entries` where `code` = '{$prefix}{$code}' and tenant_id = '{$tenant_id}' ")->num_rows;
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
			$sql = "INSERT INTO `journal_entries` set `tenant_id`='{$tenant_id}', {$data} ";
		}else{
			$sql = "UPDATE `journal_entries` set {$data} where id = '{$id}' and tenant_id = '{$tenant_id}' ";
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
		$tenant_id = $this->settings->active_tenant_id();
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
			$sql = "INSERT INTO `employee_list` set `tenant_id`='{$tenant_id}', {$data} ";
		}else{
			$sql = "UPDATE `employee_list` set {$data} where id = '{$id}' and tenant_id = '{$tenant_id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `employee_list` where `employee_code` = '{$employee_code}' and tenant_id = '{$tenant_id}' and delete_flag = 0 ".($id > 0 ? " and id != '{$id}'" : ""))->num_rows;
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
		$id = $this->conn->real_escape_string($id);
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
		$tenant_id = $this->settings->active_tenant_id();
		$employee_id = $this->conn->real_escape_string($employee_id);
		$attendance_date = $this->conn->real_escape_string($attendance_date);
		$status = $this->conn->real_escape_string($status);

		$check = $this->conn->query("SELECT * FROM `attendance_list` where `employee_id` = '{$employee_id}' and `attendance_date` = '{$attendance_date}' and tenant_id = '{$tenant_id}' ".(!empty($id) && $id > 0 ? " and id != '{$id}'" : ""))->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = " Attendance record for this employee on the selected date already exists.";
			return json_encode($resp);
		}
		$check_in = !empty($check_in) ? "'".$this->conn->real_escape_string($check_in)."'" : "NULL";
		$check_out = !empty($check_out) ? "'".$this->conn->real_escape_string($check_out)."'" : "NULL";

		if(empty($id)){
			$sql = "INSERT INTO `attendance_list` (`tenant_id`, `employee_id`, `attendance_date`, `check_in`, `check_out`, `status`) VALUES ('{$tenant_id}', '{$employee_id}', '{$attendance_date}', {$check_in}, {$check_out}, '{$status}')";
		}else{
			$sql = "UPDATE `attendance_list` SET `employee_id`='{$employee_id}', `attendance_date`='{$attendance_date}', `check_in`={$check_in}, `check_out`={$check_out}, `status`='{$status}' WHERE id = '{$id}' and tenant_id = '{$tenant_id}'";
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
		$id = $this->conn->real_escape_string($id);
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
		$tenant_id = $this->settings->active_tenant_id();
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
			$sql = "INSERT INTO `leave_list` set `tenant_id`='{$tenant_id}', {$data} ";
		}else{
			$sql = "UPDATE `leave_list` set {$data} where id = '{$id}' and tenant_id = '{$tenant_id}' ";
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
		$id = $this->conn->real_escape_string($id);
		$status = $this->conn->real_escape_string($status);
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
		$id = $this->conn->real_escape_string($id);
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
	function calculate_payroll_ajax(){
		require_once(base_app.'classes/FijiPayroll.php');
		$calc = FijiPayroll::calculate($_POST);
		return json_encode(['status' => 'success', 'data' => $calc]);
	}

	function save_payroll(){
		require_once(base_app.'classes/FijiPayroll.php');
		extract($_POST);
		$tenant_id = $this->settings->active_tenant_id();

		$payroll_run_ref = !empty($payroll_run_ref) ? $this->conn->real_escape_string($payroll_run_ref) : 'RUN-'.date('Ym').'-'.rand(100,999);
		$salary_month = !empty($salary_month) ? $this->conn->real_escape_string($salary_month) : date('F Y');
		$pay_frequency = !empty($pay_frequency) ? $this->conn->real_escape_string($pay_frequency) : 'Monthly';
		$pay_date = !empty($pay_date) ? $this->conn->real_escape_string($pay_date) : date('Y-m-d');
		$period_start = !empty($period_start) ? $this->conn->real_escape_string($period_start) : date('Y-m-01');
		$period_end = !empty($period_end) ? $this->conn->real_escape_string($period_end) : date('Y-m-t');

		$employee_id = $this->conn->real_escape_string($employee_id);

		// Get Employee details
		$emp_qry = $this->conn->query("SELECT *, concat(firstname, ' ', lastname) as name FROM `employee_list` WHERE id = '{$employee_id}' and tenant_id = '{$tenant_id}'");
		if(!$emp_qry || $emp_qry->num_rows == 0){
			return json_encode(['status' => 'failed', 'msg' => 'Employee not found.']);
		}
		$emp_data = $emp_qry->fetch_assoc();

		$basic_salary = isset($basic_salary) ? (float)$basic_salary : (float)$emp_data['salary'];
		$overtime = isset($overtime) ? (float)$overtime : 0;
		$allowances = isset($allowances) ? (float)$allowances : 0;
		$other_earnings = isset($other_earnings) ? (float)$other_earnings : 0;
		$deductions = isset($deductions) ? (float)$deductions : 0;
		$is_resident = isset($is_resident) ? (int)$is_resident : (int)$emp_data['is_resident'];

		// Calculate Fiji Payroll
		$calc = FijiPayroll::calculate([
			'basic_salary' => $basic_salary,
			'overtime' => $overtime,
			'allowances' => $allowances,
			'other_earnings' => $other_earnings,
			'deductions' => $deductions,
			'is_resident' => $is_resident,
			'pay_frequency' => $pay_frequency
		]);

		$gross_salary = $calc['gross_salary'];
		$fnpf_base = $calc['fnpf_base'];
		$employee_fnpf = $calc['employee_fnpf'];
		$employer_fnpf = $calc['employer_fnpf'];
		$taxable_income = $calc['taxable_income'];
		$paye_tax = $calc['paye_tax'];
		$srt_tax = $calc['srt_tax'];
		$net_salary = $calc['net_salary'];
		$workcare_levy = $calc['workcare_levy'];
		$training_levy = $calc['training_levy'];
		$employer_cost = $calc['employer_cost'];

		$tin = $this->conn->real_escape_string($emp_data['tin']);
		$fnpf_no = $this->conn->real_escape_string($emp_data['fnpf_no']);
		$tax_code = $this->conn->real_escape_string($emp_data['tax_code']);
		$bank_code = $this->conn->real_escape_string($emp_data['bank_code']);
		$bank_account = $this->conn->real_escape_string($emp_data['bank_account']);

		// Accounts for Journal Entry
		$salary_acc = $this->conn->query("SELECT id FROM `account_list` WHERE (`name` LIKE '%salary%' OR `name` LIKE '%expense%') AND tenant_id = '{$tenant_id}' AND delete_flag = 0 LIMIT 1");
		$salary_acc_id = ($salary_acc && $salary_acc->num_rows > 0) ? $salary_acc->fetch_assoc()['id'] : 1;

		$cash_acc = $this->conn->query("SELECT id FROM `account_list` WHERE `name` LIKE '%cash%' AND tenant_id = '{$tenant_id}' AND delete_flag = 0 LIMIT 1");
		$cash_acc_id = ($cash_acc && $cash_acc->num_rows > 0) ? $cash_acc->fetch_assoc()['id'] : 1;

		$group_debit_id = 1;
		$group_credit_id = 2;

		// Create Journal Entry
		$prefix = date("Ym-");
		$code = sprintf("%'.05d",1);
		while(true){
			$check = $this->conn->query("SELECT * FROM `journal_entries` where `code` = '{$prefix}{$code}' and tenant_id = '{$tenant_id}' ")->num_rows;
			if($check > 0){
				$code = sprintf("%'.05d",ceil($code) + 1);
			}else{
				break;
			}
		}
		$journal_code = $prefix.$code;
		$user_id = $this->settings->userdata('id');
		$journal_date = $pay_date;

		$description = $this->conn->real_escape_string("Fiji Payroll Disbursement for {$emp_data['name']} ({$salary_month}) - Gross: \${$gross_salary}, Net: \${$net_salary}");

		if(empty($id)){
			$j_sql = "INSERT INTO `journal_entries` (`tenant_id`, `code`, `journal_date`, `description`, `user_id`) VALUES ('{$tenant_id}', '{$journal_code}', '{$journal_date}', '{$description}', '{$user_id}')";
			$this->conn->query($j_sql);
			$journal_id = $this->conn->insert_id;

			$j_items = "INSERT INTO `journal_items` (`journal_id`, `account_id`, `group_id`, `amount`) VALUES
				('{$journal_id}', '{$salary_acc_id}', '{$group_debit_id}', '{$net_salary}'),
				('{$journal_id}', '{$cash_acc_id}', '{$group_credit_id}', '{$net_salary}')";
			$this->conn->query($j_items);

			$sql = "INSERT INTO `payroll_list` SET
				`tenant_id` = '{$tenant_id}',
				`employee_id` = '{$employee_id}',
				`journal_id` = '{$journal_id}',
				`payroll_run_ref` = '{$payroll_run_ref}',
				`salary_month` = '{$salary_month}',
				`pay_frequency` = '{$pay_frequency}',
				`pay_date` = '{$pay_date}',
				`period_start` = '{$period_start}',
				`period_end` = '{$period_end}',
				`basic_salary` = '{$basic_salary}',
				`overtime` = '{$overtime}',
				`allowances` = '{$allowances}',
				`other_earnings` = '{$other_earnings}',
				`gross_salary` = '{$gross_salary}',
				`fnpf_base` = '{$fnpf_base}',
				`employee_fnpf` = '{$employee_fnpf}',
				`employer_fnpf` = '{$employer_fnpf}',
				`taxable_income` = '{$taxable_income}',
				`paye_tax` = '{$paye_tax}',
				`srt_tax` = '{$srt_tax}',
				`deductions` = '{$deductions}',
				`net_salary` = '{$net_salary}',
				`workcare_levy` = '{$workcare_levy}',
				`training_levy` = '{$training_levy}',
				`employer_cost` = '{$employer_cost}',
				`tin` = '{$tin}',
				`fnpf_no` = '{$fnpf_no}',
				`tax_code` = '{$tax_code}',
				`is_resident` = '{$is_resident}',
				`bank_code` = '{$bank_code}',
				`bank_account` = '{$bank_account}'";
		} else {
			$existing = $this->conn->query("SELECT journal_id FROM `payroll_list` WHERE id = '{$id}'")->fetch_assoc();
			$journal_id = isset($existing['journal_id']) ? $existing['journal_id'] : null;
			if($journal_id){
				$this->conn->query("UPDATE `journal_entries` SET `description` = '{$description}', `journal_date` = '{$journal_date}' WHERE id = '{$journal_id}'");
				$this->conn->query("DELETE FROM `journal_items` WHERE journal_id = '{$journal_id}'");
				$j_items = "INSERT INTO `journal_items` (`journal_id`, `account_id`, `group_id`, `amount`) VALUES
					('{$journal_id}', '{$salary_acc_id}', '{$group_debit_id}', '{$net_salary}'),
					('{$journal_id}', '{$cash_acc_id}', '{$group_credit_id}', '{$net_salary}')";
				$this->conn->query($j_items);
			}

			$sql = "UPDATE `payroll_list` SET
				`employee_id` = '{$employee_id}',
				`payroll_run_ref` = '{$payroll_run_ref}',
				`salary_month` = '{$salary_month}',
				`pay_frequency` = '{$pay_frequency}',
				`pay_date` = '{$pay_date}',
				`period_start` = '{$period_start}',
				`period_end` = '{$period_end}',
				`basic_salary` = '{$basic_salary}',
				`overtime` = '{$overtime}',
				`allowances` = '{$allowances}',
				`other_earnings` = '{$other_earnings}',
				`gross_salary` = '{$gross_salary}',
				`fnpf_base` = '{$fnpf_base}',
				`employee_fnpf` = '{$employee_fnpf}',
				`employer_fnpf` = '{$employer_fnpf}',
				`taxable_income` = '{$taxable_income}',
				`paye_tax` = '{$paye_tax}',
				`srt_tax` = '{$srt_tax}',
				`deductions` = '{$deductions}',
				`net_salary` = '{$net_salary}',
				`workcare_levy` = '{$workcare_levy}',
				`training_levy` = '{$training_levy}',
				`employer_cost` = '{$employer_cost}',
				`tin` = '{$tin}',
				`fnpf_no` = '{$fnpf_no}',
				`tax_code` = '{$tax_code}',
				`is_resident` = '{$is_resident}',
				`bank_code` = '{$bank_code}',
				`bank_account` = '{$bank_account}'
				WHERE id = '{$id}'";
		}

		$save = $this->conn->query($sql);
		if($save){
			$resp['status'] = 'success';
			$resp['msg'] = empty($id) ? " Fiji Payroll processed and Journal Entry created successfully." : " Payroll updated successfully.";
		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occurred while saving payroll.";
			$resp['err'] = $this->conn->error;
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}

	function save_batch_payroll(){
		require_once(base_app.'classes/FijiPayroll.php');
		extract($_POST);
		$tenant_id = $this->settings->active_tenant_id();

		if(empty($employee_ids) || !is_array($employee_ids)){
			return json_encode(['status' => 'failed', 'msg' => 'No employees selected for batch payroll processing.']);
		}

		$payroll_run_ref = !empty($payroll_run_ref) ? $this->conn->real_escape_string($payroll_run_ref) : 'RUN-'.date('Ym').'-'.rand(100,999);
		$salary_month = !empty($salary_month) ? $this->conn->real_escape_string($salary_month) : date('F Y');
		$pay_frequency = !empty($pay_frequency) ? $this->conn->real_escape_string($pay_frequency) : 'Monthly';
		$pay_date = !empty($pay_date) ? $this->conn->real_escape_string($pay_date) : date('Y-m-d');
		$period_start = !empty($period_start) ? $this->conn->real_escape_string($period_start) : date('Y-m-01');
		$period_end = !empty($period_end) ? $this->conn->real_escape_string($period_end) : date('Y-m-t');

		$processed_count = 0;

		foreach($employee_ids as $emp_id){
			$emp_id = $this->conn->real_escape_string($emp_id);

			$emp_qry = $this->conn->query("SELECT *, concat(firstname, ' ', lastname) as name FROM `employee_list` WHERE id = '{$emp_id}' and tenant_id = '{$tenant_id}'");
			if(!$emp_qry || $emp_qry->num_rows == 0) continue;

			$emp_data = $emp_qry->fetch_assoc();

			$basic_salary = isset($emp_basic[$emp_id]) ? (float)$emp_basic[$emp_id] : (float)$emp_data['salary'];
			$overtime = isset($emp_overtime[$emp_id]) ? (float)$emp_overtime[$emp_id] : 0;
			$allowances = isset($emp_allowances[$emp_id]) ? (float)$emp_allowances[$emp_id] : 0;
			$other_earnings = isset($emp_other_earnings[$emp_id]) ? (float)$emp_other_earnings[$emp_id] : 0;
			$deductions = isset($emp_deductions[$emp_id]) ? (float)$emp_deductions[$emp_id] : 0;
			$is_resident = (int)$emp_data['is_resident'];

			$calc = FijiPayroll::calculate([
				'basic_salary' => $basic_salary,
				'overtime' => $overtime,
				'allowances' => $allowances,
				'other_earnings' => $other_earnings,
				'deductions' => $deductions,
				'is_resident' => $is_resident,
				'pay_frequency' => $pay_frequency
			]);

			$gross_salary = $calc['gross_salary'];
			$fnpf_base = $calc['fnpf_base'];
			$employee_fnpf = $calc['employee_fnpf'];
			$employer_fnpf = $calc['employer_fnpf'];
			$taxable_income = $calc['taxable_income'];
			$paye_tax = $calc['paye_tax'];
			$srt_tax = $calc['srt_tax'];
			$net_salary = $calc['net_salary'];
			$workcare_levy = $calc['workcare_levy'];
			$training_levy = $calc['training_levy'];
			$employer_cost = $calc['employer_cost'];

			$tin = $this->conn->real_escape_string($emp_data['tin']);
			$fnpf_no = $this->conn->real_escape_string($emp_data['fnpf_no']);
			$tax_code = $this->conn->real_escape_string($emp_data['tax_code']);
			$bank_code = $this->conn->real_escape_string($emp_data['bank_code']);
			$bank_account = $this->conn->real_escape_string($emp_data['bank_account']);

			// Journal
			$salary_acc = $this->conn->query("SELECT id FROM `account_list` WHERE (`name` LIKE '%salary%' OR `name` LIKE '%expense%') AND tenant_id = '{$tenant_id}' AND delete_flag = 0 LIMIT 1");
			$salary_acc_id = ($salary_acc && $salary_acc->num_rows > 0) ? $salary_acc->fetch_assoc()['id'] : 1;

			$cash_acc = $this->conn->query("SELECT id FROM `account_list` WHERE `name` LIKE '%cash%' AND tenant_id = '{$tenant_id}' AND delete_flag = 0 LIMIT 1");
			$cash_acc_id = ($cash_acc && $cash_acc->num_rows > 0) ? $cash_acc->fetch_assoc()['id'] : 1;

			$prefix = date("Ym-");
			$code = sprintf("%'.05d",1);
			while(true){
				$check = $this->conn->query("SELECT * FROM `journal_entries` where `code` = '{$prefix}{$code}' and tenant_id = '{$tenant_id}' ")->num_rows;
				if($check > 0){
					$code = sprintf("%'.05d",ceil($code) + 1);
				}else{
					break;
				}
			}
			$journal_code = $prefix.$code;
			$user_id = $this->settings->userdata('id');
			$journal_date = $pay_date;

			$description = $this->conn->real_escape_string("Fiji Batch Payroll Disbursement for {$emp_data['name']} ({$salary_month}) - Ref: {$payroll_run_ref}");

			$j_sql = "INSERT INTO `journal_entries` (`tenant_id`, `code`, `journal_date`, `description`, `user_id`) VALUES ('{$tenant_id}', '{$journal_code}', '{$journal_date}', '{$description}', '{$user_id}')";
			$this->conn->query($j_sql);
			$journal_id = $this->conn->insert_id;

			$j_items = "INSERT INTO `journal_items` (`journal_id`, `account_id`, `group_id`, `amount`) VALUES
				('{$journal_id}', '{$salary_acc_id}', '1', '{$net_salary}'),
				('{$journal_id}', '{$cash_acc_id}', '2', '{$net_salary}')";
			$this->conn->query($j_items);

			$sql = "INSERT INTO `payroll_list` SET
				`tenant_id` = '{$tenant_id}',
				`employee_id` = '{$emp_id}',
				`journal_id` = '{$journal_id}',
				`payroll_run_ref` = '{$payroll_run_ref}',
				`salary_month` = '{$salary_month}',
				`pay_frequency` = '{$pay_frequency}',
				`pay_date` = '{$pay_date}',
				`period_start` = '{$period_start}',
				`period_end` = '{$period_end}',
				`basic_salary` = '{$basic_salary}',
				`overtime` = '{$overtime}',
				`allowances` = '{$allowances}',
				`other_earnings` = '{$other_earnings}',
				`gross_salary` = '{$gross_salary}',
				`fnpf_base` = '{$fnpf_base}',
				`employee_fnpf` = '{$employee_fnpf}',
				`employer_fnpf` = '{$employer_fnpf}',
				`taxable_income` = '{$taxable_income}',
				`paye_tax` = '{$paye_tax}',
				`srt_tax` = '{$srt_tax}',
				`deductions` = '{$deductions}',
				`net_salary` = '{$net_salary}',
				`workcare_levy` = '{$workcare_levy}',
				`training_levy` = '{$training_levy}',
				`employer_cost` = '{$employer_cost}',
				`tin` = '{$tin}',
				`fnpf_no` = '{$fnpf_no}',
				`tax_code` = '{$tax_code}',
				`is_resident` = '{$is_resident}',
				`bank_code` = '{$bank_code}',
				`bank_account` = '{$bank_account}'";

			if($this->conn->query($sql)){
				$processed_count++;
			}
		}

		$resp['status'] = 'success';
		$resp['msg'] = "Batch payroll run ({$payroll_run_ref}) successfully created for {$processed_count} employees.";
		$this->settings->set_flashdata('success', $resp['msg']);
		return json_encode($resp);
	}

	function delete_payroll(){
		extract($_POST);
		$id = $this->conn->real_escape_string($id);
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

	/* Tenant Management Functions */
	function save_tenant(){
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
			$sql = "INSERT INTO `tenants` set {$data} ";
		}else{
			$sql = "UPDATE `tenants` set {$data} where id = '{$id}' ";
		}
		$check = $this->conn->query("SELECT * FROM `tenants` where `tenant_code` = '{$tenant_code}' and delete_flag = 0 ".($id > 0 ? " and id != '{$id}'" : ""))->num_rows;
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = " Tenant Code already exists.";
		}else{
			$save = $this->conn->query($sql);
			if($save){
				$resp['status'] = 'success';
				$resp['msg'] = empty($id) ? " Tenant added successfully." : " Tenant details updated successfully.";
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "An error occurred.";
				$resp['err'] = $this->conn->error;
			}
		}
		if($resp['status'] =='success')
			$this->settings->set_flashdata('success',$resp['msg']);
		return json_encode($resp);
	}

	function delete_tenant(){
		extract($_POST);
		$id = $this->conn->real_escape_string($id);
		$del = $this->conn->query("UPDATE `tenants` set delete_flag = 1 where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success'," Tenant deleted successfully.");
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
	case 'calculate_payroll_ajax':
		echo $Master->calculate_payroll_ajax();
	break;
	case 'save_payroll':
		echo $Master->save_payroll();
	break;
	case 'save_batch_payroll':
		echo $Master->save_batch_payroll();
	break;
	case 'delete_payroll':
		echo $Master->delete_payroll();
	break;
	case 'save_tenant':
		echo $Master->save_tenant();
	break;
	case 'delete_tenant':
		echo $Master->delete_tenant();
	break;
	default:
		// echo $sysset->index();
		break;
}