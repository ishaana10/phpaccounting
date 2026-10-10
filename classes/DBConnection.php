<?php
if(!defined('DB_SERVER')){
    require_once("../initialize.php");
}
class DBConnection{

    private $host = DB_SERVER;
    private $username = DB_USERNAME;
    private $password = DB_PASSWORD;
    private $database = DB_NAME;
    
    public $conn;
    
    public function __construct(){

        if (!isset($this->conn)) {
            // Disable default mysqli exception throwing to handle errors gracefully
            if (function_exists('mysqli_report')) {
                mysqli_report(MYSQLI_REPORT_OFF);
            }

            // Attempt connection to host and database
            @$this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->conn->connect_error) {
                // If database does not exist or access failed, connect without database to create it
                @$server_conn = new mysqli($this->host, $this->username, $this->password);
                if ($server_conn->connect_error) {
                    die("<div style='padding:20px; font-family:sans-serif; background:#fee2e2; border:1px solid #f87171; color:#991b1b; border-radius:8px;'>
                        <h3 style='margin-top:0;'>Database Connection Failed</h3>
                        <p>Unable to connect to the MySQL database server at <b>{$this->host}</b> with username <b>{$this->username}</b>.</p>
                        <p><b>Error Details:</b> " . htmlspecialchars($server_conn->connect_error) . "</p>
                        <p>Please check your database credentials in <code>initialize.php</code>.</p>
                    </div>");
                }

                // Auto-create database
                $create_db_sql = "CREATE DATABASE IF NOT EXISTS `" . $this->database . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                if ($server_conn->query($create_db_sql)) {
                    $server_conn->close();
                    @$this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
                } else {
                    die("<div style='padding:20px; font-family:sans-serif; background:#fee2e2; border:1px solid #f87171; color:#991b1b; border-radius:8px;'>
                        <h3 style='margin-top:0;'>Database Auto-Creation Failed</h3>
                        <p>Could not auto-create database <b>{$this->database}</b>: " . htmlspecialchars($server_conn->error) . "</p>
                    </div>");
                }
            }

            // Auto-install schema if system_info table is missing
            $check_tbl = @$this->conn->query("SHOW TABLES LIKE 'system_info'");
            if (!$check_tbl || $check_tbl->num_rows == 0) {
                $this->auto_install_schema();
            } else {
                $this->check_fiji_payroll_migrations();
            }
        }    
        
    }

    public function check_fiji_payroll_migrations(){
        if (!$this->conn) return;

        // Check employee_list columns
        $emp_cols = array(
            'tin' => "ALTER TABLE `employee_list` ADD COLUMN `tin` varchar(50) DEFAULT NULL AFTER `salary`",
            'fnpf_no' => "ALTER TABLE `employee_list` ADD COLUMN `fnpf_no` varchar(50) DEFAULT NULL AFTER `tin`",
            'tax_code' => "ALTER TABLE `employee_list` ADD COLUMN `tax_code` varchar(10) NOT NULL DEFAULT 'P' AFTER `fnpf_no`",
            'is_resident' => "ALTER TABLE `employee_list` ADD COLUMN `is_resident` tinyint(1) NOT NULL DEFAULT 1 AFTER `tax_code`",
            'bank_code' => "ALTER TABLE `employee_list` ADD COLUMN `bank_code` varchar(20) DEFAULT 'bsp' AFTER `is_resident`",
            'bank_account' => "ALTER TABLE `employee_list` ADD COLUMN `bank_account` varchar(50) DEFAULT NULL AFTER `bank_code`"
        );

        foreach ($emp_cols as $col => $sql) {
            $res = @$this->conn->query("SHOW COLUMNS FROM `employee_list` LIKE '{$col}'");
            if ($res && $res->num_rows == 0) {
                @$this->conn->query($sql);
            }
        }

        // Check payroll_list columns
        $pay_cols = array(
            'payroll_run_ref' => "ALTER TABLE `payroll_list` ADD COLUMN `payroll_run_ref` varchar(100) DEFAULT NULL AFTER `journal_id`",
            'pay_frequency' => "ALTER TABLE `payroll_list` ADD COLUMN `pay_frequency` varchar(20) NOT NULL DEFAULT 'Monthly' AFTER `salary_month`",
            'pay_date' => "ALTER TABLE `payroll_list` ADD COLUMN `pay_date` date DEFAULT NULL AFTER `pay_frequency`",
            'period_start' => "ALTER TABLE `payroll_list` ADD COLUMN `period_start` date DEFAULT NULL AFTER `pay_date`",
            'period_end' => "ALTER TABLE `payroll_list` ADD COLUMN `period_end` date DEFAULT NULL AFTER `period_start`",
            'overtime' => "ALTER TABLE `payroll_list` ADD COLUMN `overtime` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `basic_salary`",
            'other_earnings' => "ALTER TABLE `payroll_list` ADD COLUMN `other_earnings` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `allowances`",
            'gross_salary' => "ALTER TABLE `payroll_list` ADD COLUMN `gross_salary` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `other_earnings`",
            'fnpf_base' => "ALTER TABLE `payroll_list` ADD COLUMN `fnpf_base` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `gross_salary`",
            'employee_fnpf' => "ALTER TABLE `payroll_list` ADD COLUMN `employee_fnpf` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `fnpf_base`",
            'employer_fnpf' => "ALTER TABLE `payroll_list` ADD COLUMN `employer_fnpf` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `employee_fnpf`",
            'taxable_income' => "ALTER TABLE `payroll_list` ADD COLUMN `taxable_income` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `employer_fnpf`",
            'paye_tax' => "ALTER TABLE `payroll_list` ADD COLUMN `paye_tax` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `taxable_income`",
            'srt_tax' => "ALTER TABLE `payroll_list` ADD COLUMN `srt_tax` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `paye_tax`",
            'workcare_levy' => "ALTER TABLE `payroll_list` ADD COLUMN `workcare_levy` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `net_salary`",
            'training_levy' => "ALTER TABLE `payroll_list` ADD COLUMN `training_levy` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `workcare_levy`",
            'employer_cost' => "ALTER TABLE `payroll_list` ADD COLUMN `employer_cost` decimal(12,2) NOT NULL DEFAULT 0.00 AFTER `training_levy`",
            'tin' => "ALTER TABLE `payroll_list` ADD COLUMN `tin` varchar(50) DEFAULT NULL AFTER `employer_cost`",
            'fnpf_no' => "ALTER TABLE `payroll_list` ADD COLUMN `fnpf_no` varchar(50) DEFAULT NULL AFTER `tin`",
            'tax_code' => "ALTER TABLE `payroll_list` ADD COLUMN `tax_code` varchar(10) DEFAULT 'P' AFTER `fnpf_no`",
            'is_resident' => "ALTER TABLE `payroll_list` ADD COLUMN `is_resident` tinyint(1) DEFAULT 1 AFTER `tax_code`",
            'bank_code' => "ALTER TABLE `payroll_list` ADD COLUMN `bank_code` varchar(20) DEFAULT NULL AFTER `is_resident`",
            'bank_account' => "ALTER TABLE `payroll_list` ADD COLUMN `bank_account` varchar(50) DEFAULT NULL AFTER `bank_code`"
        );

        foreach ($pay_cols as $col => $sql) {
            $res = @$this->conn->query("SHOW COLUMNS FROM `payroll_list` LIKE '{$col}'");
            if ($res && $res->num_rows == 0) {
                @$this->conn->query($sql);
            }
        }
    }

    public function auto_install_schema(){
        $sql_file = base_app . 'database/ajms_db.sql';
        if (file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            if (!empty($sql_content)) {
                if (@$this->conn->multi_query($sql_content)) {
                    do {
                        if ($result = $this->conn->store_result()) {
                            $result->free();
                        }
                    } while ($this->conn->more_results() && $this->conn->next_result());
                } else {
                    // Fallback to split execution if multi_query is restricted
                    $queries = preg_split("/;[\r\n]+/", $sql_content);
                    foreach ($queries as $query) {
                        $query = trim($query);
                        if (!empty($query)) {
                            @$this->conn->query($query);
                        }
                    }
                }
            }
        }
    }

    public function __destruct(){
        if (isset($this->conn) && $this->conn instanceof mysqli) {
            @$this->conn->close();
        }
    }
}
?>