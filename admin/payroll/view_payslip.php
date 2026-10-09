<?php
require_once('../../config.php');
if(isset($_GET['id']) && $_GET['id'] > 0){
    $qry = $conn->query("SELECT p.*, e.employee_code, e.firstname, e.lastname, e.department, e.designation, e.phone from `payroll_list` p inner join `employee_list` e on p.employee_id = e.id where p.id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_assoc() as $k => $v){
            $$k=$v;
        }
    }
}
?>
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #print-area, #print-area * {
            visibility: visible;
        }
        #print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>
<div class="container-fluid" id="print-area">
    <div class="card p-3 shadow-none border">
        <div class="text-center mb-4">
            <h3 class="font-weight-bold mb-0"><?php echo $_settings->info('name') ?></h3>
            <p class="text-muted mb-1"><?php echo $_settings->info('address') ?></p>
            <h4 class="text-primary font-weight-bold mt-3">EMPLOYEE PAYSLIP</h4>
            <span class="badge badge-secondary px-3 py-1 font-weight-normal"><?php echo isset($salary_month) ? $salary_month : '' ?></span>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <p class="mb-1"><b>Employee Code:</b> <?php echo isset($employee_code) ? $employee_code : '' ?></p>
                <p class="mb-1"><b>Name:</b> <?php echo isset($firstname) ? $firstname . ' ' . $lastname : '' ?></p>
                <p class="mb-1"><b>Phone:</b> <?php echo isset($phone) ? $phone : '' ?></p>
            </div>
            <div class="col-6 text-right">
                <p class="mb-1"><b>Department:</b> <?php echo isset($department) ? $department : '' ?></p>
                <p class="mb-1"><b>Designation:</b> <?php echo isset($designation) ? $designation : '' ?></p>
                <p class="mb-1"><b>Generated Date:</b> <?php echo isset($date_created) ? date("M d, Y", strtotime($date_created)) : date("M d, Y") ?></p>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="bg-light">
                <tr>
                    <th>Description</th>
                    <th class="text-right" style="width: 200px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td class="text-right"><?php echo number_format($basic_salary, 2) ?></td>
                </tr>
                <tr>
                    <td>Allowances</td>
                    <td class="text-right text-success">+ <?php echo number_format($allowances, 2) ?></td>
                </tr>
                <tr>
                    <td>Deductions</td>
                    <td class="text-right text-danger">- <?php echo number_format($deductions, 2) ?></td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="bg-light">
                    <th class="text-uppercase font-weight-bold">Net Salary Payable</th>
                    <th class="text-right font-weight-bold h5 text-primary mb-0"><?php echo number_format($net_salary, 2) ?></th>
                </tr>
            </tfoot>
        </table>

        <div class="row mt-5 pt-4">
            <div class="col-6 text-center">
                <p class="border-top pt-2 mb-0">Employee Signature</p>
            </div>
            <div class="col-6 text-center">
                <p class="border-top pt-2 mb-0">Authorized Signature</p>
            </div>
        </div>
    </div>
</div>
<div class="text-right mt-3">
    <button class="btn btn-flat btn-primary" type="button" id="print-payslip"><i class="fa fa-print"></i> Print Payslip</button>
    <button class="btn btn-flat btn-secondary" type="button" data-dismiss="modal">Close</button>
</div>

<script>
    $(document).ready(function(){
        $('#print-payslip').click(function(){
            var head = $('head').clone();
            var p = $('#print-area').clone();
            var el = $('<div>');
            el.append(head);
            el.append(p);
            var nw = window.open('','_blank','width=800,height=600');
            nw.document.write(el.html());
            nw.document.close();
            setTimeout(() => {
                nw.print();
                setTimeout(() => {
                    nw.close();
                }, 500);
            }, 500);
        })
    })
</script>
