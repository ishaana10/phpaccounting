<?php
$tenant_id = $_settings->active_tenant_id();
$user_type = $_settings->userdata('type'); // 1 = Admin, 2 = Regular User/Staff

// Metric Calculations
$group_count = $conn->query("SELECT * FROM `group_list` WHERE tenant_id = '{$tenant_id}' AND delete_flag = 0 AND status = 1")->num_rows;
$account_count = $conn->query("SELECT * FROM `account_list` WHERE tenant_id = '{$tenant_id}' AND delete_flag = 0 AND status = 1")->num_rows;
$journal_count = $conn->query("SELECT * FROM `journal_entries` WHERE tenant_id = '{$tenant_id}'")->num_rows;

$employee_count = $conn->query("SELECT * FROM `employee_list` WHERE tenant_id = '{$tenant_id}' AND delete_flag = 0 AND status = 1")->num_rows;
$pending_leaves = $conn->query("SELECT * FROM `leave_list` WHERE tenant_id = '{$tenant_id}' AND status = 'pending'")->num_rows;
$total_payroll = $conn->query("SELECT SUM(net_salary) as total FROM `payroll_list` WHERE tenant_id = '{$tenant_id}'")->fetch_assoc()['total'] ?? 0;

// Monthly Financial Journal Summary (Debits vs Credits per Month)
$monthly_debits = [0,0,0,0,0,0,0,0,0,0,0,0];
$monthly_credits = [0,0,0,0,0,0,0,0,0,0,0,0];

$j_sql = "SELECT MONTH(j.journal_date) as m, g.type, SUM(ji.amount) as total
          FROM journal_items ji
          INNER JOIN journal_entries j ON ji.journal_id = j.id
          INNER JOIN group_list g ON ji.group_id = g.id
          WHERE j.tenant_id = '{$tenant_id}' AND YEAR(j.journal_date) = YEAR(CURRENT_DATE())
          GROUP BY MONTH(j.journal_date), g.type";
$j_qry = $conn->query($j_sql);
if($j_qry){
    while($r = $j_qry->fetch_assoc()){
        $m_idx = (int)$r['m'] - 1;
        if($r['type'] == 1) $monthly_debits[$m_idx] = (float)$r['total'];
        if($r['type'] == 2) $monthly_credits[$m_idx] = (float)$r['total'];
    }
}

// Department Salary Breakdown
$dept_labels = [];
$dept_series = [];
$dept_qry = $conn->query("SELECT department, SUM(salary) as total FROM employee_list WHERE tenant_id = '{$tenant_id}' AND delete_flag = 0 GROUP BY department");
if($dept_qry){
    while($dr = $dept_qry->fetch_assoc()){
        $dept_labels[] = !empty($dr['department']) ? $dr['department'] : 'Unassigned';
        $dept_series[] = (float)$dr['total'];
    }
}
if(empty($dept_labels)){
    $dept_labels = ['Engineering', 'Finance', 'Sales', 'HR'];
    $dept_series = [15000, 8000, 12000, 6000];
}

// Attendance Status Breakdown
$att_present = $conn->query("SELECT * FROM attendance_list WHERE tenant_id = '{$tenant_id}' AND status = 'present'")->num_rows;
$att_late = $conn->query("SELECT * FROM attendance_list WHERE tenant_id = '{$tenant_id}' AND status = 'late'")->num_rows;
$att_absent = $conn->query("SELECT * FROM attendance_list WHERE tenant_id = '{$tenant_id}' AND status = 'absent'")->num_rows;
$att_half = $conn->query("SELECT * FROM attendance_list WHERE tenant_id = '{$tenant_id}' AND status = 'half_day'")->num_rows;
?>

<div class="content-header pb-2">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 font-weight-bold text-dark">Executive Dashboard</h1>
            <p class="text-muted mb-0"><i class="fas fa-building text-primary mr-1"></i> Active Enterprise Tenant: <b><?php echo $conn->query("SELECT name FROM tenants WHERE id = '{$tenant_id}'")->fetch_assoc()['name'] ?? 'Default Tenant' ?></b></p>
        </div>
        <div>
            <span class="badge badge-primary px-3 py-2"><i class="fas fa-user-shield mr-1"></i> Role: <?php echo ($user_type == 1) ? 'System Administrator' : 'Staff User' ?></span>
        </div>
    </div>
</div>

<!-- KPI Metric Widgets -->
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-primary text-white"><i class="fas fa-book font-weight-bold"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Journal Entries</span>
                <span class="info-box-number"><?php echo number_format($journal_count) ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-info text-white"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active Employees</span>
                <span class="info-box-number"><?php echo number_format($employee_count) ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-success text-white"><i class="fas fa-file-invoice-dollar"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Payroll</span>
                <span class="info-box-number">$<?php echo number_format($total_payroll, 2) ?></span>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-md-3">
        <div class="info-box shadow-sm">
            <span class="info-box-icon bg-warning text-white"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pending Leaves</span>
                <span class="info-box-number"><?php echo number_format($pending_leaves) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Dynamic ApexCharts Dashboard Widgets -->
<div class="row">
    <!-- Financial Overview Chart -->
    <div class="col-lg-8 col-md-12">
        <div class="card shadow-sm">
            <div class="card-header border-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold"><i class="fas fa-chart-line text-primary mr-2"></i> Financial Movement (Debits vs Credits)</h3>
                <span class="text-muted small">Current Year Overview</span>
            </div>
            <div class="card-body p-2">
                <div id="financial-overview-chart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>

    <!-- Department Salary Distribution -->
    <div class="col-lg-4 col-md-12">
        <div class="card shadow-sm">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-chart-pie text-info mr-2"></i> Payroll Expense by Dept.</h3>
            </div>
            <div class="card-body p-2">
                <div id="department-payroll-chart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>
</div>

<?php if($user_type == 1): ?>
<!-- Admin Executive Widgets -->
<div class="row">
    <div class="col-lg-6 col-md-12">
        <div class="card shadow-sm">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-user-check text-success mr-2"></i> Workforce Attendance Analytics</h3>
            </div>
            <div class="card-body p-2">
                <div id="attendance-donut-chart" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-md-12">
        <div class="card shadow-sm">
            <div class="card-header border-0">
                <h3 class="card-title font-weight-bold"><i class="fas fa-layer-group text-primary mr-2"></i> Chart of Accounts & Groups</h3>
            </div>
            <div class="card-body d-flex align-items-center justify-content-around py-4">
                <div class="text-center p-3 border rounded bg-light" style="min-width: 160px;">
                    <i class="fas fa-th-list fa-2x text-primary mb-2"></i>
                    <h2 class="font-weight-bold mb-0"><?php echo number_format($group_count) ?></h2>
                    <span class="text-muted small">Account Groups</span>
                </div>
                <div class="text-center p-3 border rounded bg-light" style="min-width: 160px;">
                    <i class="fas fa-list-alt fa-2x text-info mb-2"></i>
                    <h2 class="font-weight-bold mb-0"><?php echo number_format($account_count) ?></h2>
                    <span class="text-muted small">Active Accounts</span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
$(document).ready(function(){
    // Financial Movement Area/Bar Chart
    var finOptions = {
        series: [{
            name: 'Total Debits',
            data: <?php echo json_encode($monthly_debits) ?>
        }, {
            name: 'Total Credits',
            data: <?php echo json_encode($monthly_credits) ?>
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: false }
        },
        colors: ['#2563eb', '#10b981'],
        plotOptions: {
            bar: { horizontal: false, columnWidth: '45%', borderRadius: 4 }
        },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        },
        fill: { opacity: 1 },
        tooltip: {
            y: { formatter: function (val) { return "$" + val.toLocaleString(); } }
        }
    };
    var finChart = new ApexCharts(document.querySelector("#financial-overview-chart"), finOptions);
    finChart.render();

    // Department Payroll Donut
    var deptOptions = {
        series: <?php echo json_encode($dept_series) ?>,
        labels: <?php echo json_encode($dept_labels) ?>,
        chart: { type: 'donut', height: 320 },
        colors: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'],
        legend: { position: 'bottom' },
        tooltip: {
            y: { formatter: function (val) { return "$" + val.toLocaleString(); } }
        }
    };
    var deptChart = new ApexCharts(document.querySelector("#department-payroll-chart"), deptOptions);
    deptChart.render();

    <?php if($user_type == 1): ?>
    // Attendance Analytics Chart
    var attOptions = {
        series: [<?php echo $att_present ?>, <?php echo $att_late ?>, <?php echo $att_half ?>, <?php echo $att_absent ?>],
        labels: ['Present', 'Late', 'Half Day', 'Absent'],
        chart: { type: 'pie', height: 280 },
        colors: ['#10b981', '#f59e0b', '#3b82f6', '#ef4444'],
        legend: { position: 'bottom' }
    };
    var attChart = new ApexCharts(document.querySelector("#attendance-donut-chart"), attOptions);
    attChart.render();
    <?php endif; ?>
});
</script>
