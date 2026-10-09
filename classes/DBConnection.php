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
            // Enable error reporting mode for mysqli exceptions handling
            mysqli_report(MYSQLI_REPORT_OFF);

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

            // Auto-install schema if system_info or users table is missing
            $check_tbl = @$this->conn->query("SHOW TABLES LIKE 'system_info'");
            if (!$check_tbl || $check_tbl->num_rows == 0) {
                $this->auto_install_schema();
            }
        }    
        
    }

    private function auto_install_schema(){
        $sql_file = base_app . 'database/ajms_db.sql';
        if (file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            if (!empty($sql_content)) {
                $queries = explode(";\n", $sql_content);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        @$this->conn->query($query);
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