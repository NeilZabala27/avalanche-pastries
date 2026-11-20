<?php
require 'auth.php';      // login check and class loader
require 'config.php';

// Polymorphism: three different report types in one list, the same render() call on each
$reports = [
    new MonthlySalesReport($pdo),
    new OrderCountReport($pdo),
    new BestSellingReport($pdo),
];
$pageTitle = 'Reports';
include 'header.php';
?>
    <h1>Reports</h1>
    <?php foreach ($reports as $report) { $report->render(); } ?>
<?php include 'footer.php'; ?>
