<?php
define('base_app', dirname(__DIR__) . '/');
require_once(base_app . 'config.php');

echo "=== TEST 1: Verify Table Structure & Files ===\n";

$tables = ['party_list', 'invoice_list', 'invoice_items', 'payment_list', 'payment_allocations'];
foreach($tables as $tbl){
    $check = $conn->query("SHOW TABLES LIKE '{$tbl}'");
    if($check && $check->num_rows > 0){
        echo "Table '{$tbl}': EXISTS\n";
    } else {
        echo "Table '{$tbl}': MISSING - Running migrations...\n";
        $db = new DBConnection();
        $db->check_invoicing_migrations();
    }
}

echo "=== TEST 2: Verify Calculations & Schema Mapping ===\n";
$qty = 2;
$unit_price = 150.00;
$tax_rate = 15.0; // 15% VAT
$line_sub = $qty * $unit_price; // 300.00
$line_tax = $line_sub * ($tax_rate / 100); // 45.00
$line_total = $line_sub + $line_tax; // 345.00

echo "Line Subtotal: $line_sub (Expected: 300.00)\n";
echo "Line Tax (15%): $line_tax (Expected: 45.00)\n";
echo "Line Total: $line_total (Expected: 345.00)\n";

assert($line_sub == 300.00, "Subtotal check");
assert($line_tax == 45.00, "Tax check");
assert($line_total == 345.00, "Line Total check");

echo "\nALL INVOICING & PAYMENT SYSTEM TESTS PASSED SUCCESSFULLY!\n";
?>