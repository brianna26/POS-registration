<?php
include_once 'connectdb.php';
session_start();

if($_SESSION['useremail'] == "") {
    header('location:../index.php');
}

include_once "header.php";

// Date filter defaults to current month
$date_from = isset($_GET['date_from']) && $_GET['date_from'] != '' ? $_GET['date_from'] : date('Y-m-01');
$date_to   = isset($_GET['date_to'])   && $_GET['date_to']   != '' ? $_GET['date_to']   : date('Y-m-d');

// Summary stats
$summary = $pdo->prepare("
    SELECT
        COUNT(invoice_id)  AS total_orders,
        SUM(subtotal)      AS total_subtotal,
        SUM(discount)      AS total_discount,
        SUM(total)         AS total_revenue,
        SUM(paid)          AS total_paid,
        SUM(due)           AS total_due
    FROM tbl_invoice
    WHERE DATE(order_date) BETWEEN :dfrom AND :dto
");
$summary->bindParam(':dfrom', $date_from);
$summary->bindParam(':dto',   $date_to);
$summary->execute();
$stats = $summary->fetch(PDO::FETCH_OBJ);

// Daily sales breakdown
$daily = $pdo->prepare("
    SELECT
        DATE(order_date)  AS sale_date,
        COUNT(invoice_id) AS orders,
        SUM(total)        AS daily_total,
        SUM(paid)         AS daily_paid,
        SUM(due)          AS daily_due
    FROM tbl_invoice
    WHERE DATE(order_date) BETWEEN :dfrom AND :dto
    GROUP BY DATE(order_date)
    ORDER BY sale_date ASC
");
$daily->bindParam(':dfrom', $date_from);
$daily->bindParam(':dto',   $date_to);
$daily->execute();
$daily_rows = $daily->fetchAll(PDO::FETCH_OBJ);

// Top selling products
$top_products = $pdo->prepare("
    SELECT
        d.product_name,
        d.barcode,
        SUM(d.qty)                                                         AS total_qty,
        SUM(d.qty * d.saleprice)                                           AS total_sales,
        SUM(d.qty * p.purchaseprice)                                       AS total_cost,
        SUM(d.qty * d.saleprice) - SUM(d.qty * p.purchaseprice)           AS gross_profit
    FROM tbl_invoice_details d
    LEFT JOIN tbl_product p  ON p.pid        = d.product_id
    LEFT JOIN tbl_invoice  i ON i.invoice_id = d.invoice_id
    WHERE DATE(i.order_date) BETWEEN :dfrom AND :dto
    GROUP BY d.product_id, d.product_name, d.barcode
    ORDER BY total_qty DESC
    LIMIT 10
");
$top_products->bindParam(':dfrom', $date_from);
$top_products->bindParam(':dto',   $date_to);
$top_products->execute();
$top = $top_products->fetchAll(PDO::FETCH_OBJ);

// All invoices in range
$invoices = $pdo->prepare("
    SELECT * FROM tbl_invoice
    WHERE DATE(order_date) BETWEEN :dfrom AND :dto
    ORDER BY invoice_id DESC
");
$invoices->bindParam(':dfrom', $date_from);
$invoices->bindParam(':dto',   $date_to);
$invoices->execute();
$invoice_rows = $invoices->fetchAll(PDO::FETCH_OBJ);
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Sales Report</h1>
                    <hr>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Sales Report</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">

            <!-- Date Filter -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-filter mr-2"></i>Filter by Date Range</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="salesreport.php" class="form-inline">
                        <div class="form-group mr-3">
                            <label class="mr-2">From:</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="form-group mr-3">
                            <label class="mr-2">To:</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search mr-1"></i>Generate Report
                        </button>
                        <a href="salesreport.php" class="btn btn-secondary">
                            <i class="fas fa-sync-alt mr-1"></i>Reset
                        </a>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo number_format($stats->total_orders ?? 0); ?></h3>
                            <p>Total Orders</p>
                        </div>
                        <div class="icon"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>&#8369;<?php echo number_format($stats->total_revenue ?? 0, 2); ?></h3>
                            <p>Total Revenue</p>
                        </div>
                        <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>&#8369;<?php echo number_format($stats->total_paid ?? 0, 2); ?></h3>
                            <p>Total Collected</p>
                        </div>
                        <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3>&#8369;<?php echo number_format($stats->total_due ?? 0, 2); ?></h3>
                            <p>Total Due</p>
                        </div>
                        <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                    </div>
                </div>
            </div>

            <!-- Financial Summary -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-receipt mr-2"></i>Financial Summary</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <td><strong>Subtotal</strong></td>
                                <td class="text-right">&#8369;<?php echo number_format($stats->total_subtotal ?? 0, 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Discount</strong></td>
                                <td class="text-right text-danger">- &#8369;<?php echo number_format($stats->total_discount ?? 0, 2); ?></td>
                            </tr>
                            <tr class="table-success">
                                <td><strong>Net Revenue</strong></td>
                                <td class="text-right"><strong>&#8369;<?php echo number_format($stats->total_revenue ?? 0, 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daily Sales Chart -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-chart-bar mr-2"></i>Daily Sales Chart</h5>
                </div>
                <div class="card-body">
                    <canvas id="dailySalesChart" style="min-height:250px; height:250px; max-height:250px;"></canvas>
                </div>
            </div>

            <!-- Daily Breakdown Table -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-calendar-alt mr-2"></i>Daily Sales Breakdown</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover" id="table_daily">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>No. of Orders</th>
                                <th>Daily Total</th>
                                <th>Collected</th>
                                <th>Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_rows as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d->sale_date); ?></td>
                                <td><?php echo (int)$d->orders; ?></td>
                                <td>&#8369;<?php echo number_format($d->daily_total, 2); ?></td>
                                <td>&#8369;<?php echo number_format($d->daily_paid, 2); ?></td>
                                <td>
                                    <?php if ($d->daily_due > 0): ?>
                                        <span class="text-danger">&#8369;<?php echo number_format($d->daily_due, 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-success">&#8369;0.00</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($daily_rows)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No sales data for selected period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Selling Products -->
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-trophy mr-2"></i>Top 10 Best-Selling Products</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover" id="table_top">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Barcode</th>
                                <th>Qty Sold</th>
                                <th>Total Sales</th>
                                <th>Total Cost</th>
                                <th>Gross Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($top as $t): ?>
                            <tr>
                                <td><?php echo $rank++; ?></td>
                                <td><?php echo htmlspecialchars($t->product_name); ?></td>
                                <td><?php echo htmlspecialchars($t->barcode); ?></td>
                                <td><?php echo (int)$t->total_qty; ?></td>
                                <td>&#8369;<?php echo number_format($t->total_sales, 2); ?></td>
                                <td>&#8369;<?php echo number_format($t->total_cost ?? 0, 2); ?></td>
                                <td>
                                    <?php $profit = $t->gross_profit ?? 0; ?>
                                    <span class="<?php echo $profit >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <strong>&#8369;<?php echo number_format($profit, 2); ?></strong>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($top)): ?>
                            <tr><td colspan="7" class="text-center text-muted">No product sales for selected period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- All Invoices Table -->
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h5 class="m-0"><i class="fas fa-list-alt mr-2"></i>All Invoices</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover" id="table_invoices">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <th>Order Date</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoice_rows as $inv): ?>
                            <tr>
                                <td><?php echo $inv->invoice_id; ?></td>
                                <td><?php echo htmlspecialchars($inv->order_date); ?></td>
                                <td>&#8369;<?php echo number_format($inv->subtotal, 2); ?></td>
                                <td>&#8369;<?php echo number_format($inv->discount, 2); ?></td>
                                <td>&#8369;<?php echo number_format($inv->total, 2); ?></td>
                                <td>&#8369;<?php echo number_format($inv->paid, 2); ?></td>
                                <td>
                                    <?php if ($inv->due > 0): ?>
                                        <span class="text-danger">&#8369;<?php echo number_format($inv->due, 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-success">&#8369;0.00</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-warning"><?php echo htmlspecialchars($inv->payment_type); ?></span>
                                </td>
                                <td>
                                    <a href="printbill.php?id=<?php echo $inv->invoice_id; ?>" class="btn btn-sm btn-primary" title="Print Bill">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($invoice_rows)): ?>
                            <tr><td colspan="9" class="text-center text-muted">No invoices for selected period.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /.container-fluid -->
    </div><!-- /.content -->
</div><!-- /.content-wrapper -->

<?php include_once("footer.php"); ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">

<script>
$(document).ready(function () {

    $('#table_daily').DataTable({
        "order": [[0, "asc"]],
        dom: 'Bfrtip',
        buttons: ['excel', 'pdf', 'print']
    });

    $('#table_top').DataTable({
        "order": [[3, "desc"]],
        dom: 'Bfrtip',
        buttons: ['excel', 'pdf', 'print']
    });

    $('#table_invoices').DataTable({
        "order": [[0, "desc"]],
        dom: 'Bfrtip',
        buttons: ['excel', 'pdf', 'print']
    });

    // Daily sales chart
    var labels  = <?php
        $chart_labels = [];
        $chart_totals = [];
        foreach ($daily_rows as $d) {
            $chart_labels[] = $d->sale_date;
            $chart_totals[] = (float)$d->daily_total;
        }
        echo json_encode($chart_labels);
    ?>;
    var totals = <?php echo json_encode($chart_totals); ?>;

    var ctx = document.getElementById('dailySalesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Daily Revenue (₱)',
                data: totals,
                backgroundColor: 'rgba(60, 141, 188, 0.7)',
                borderColor: 'rgba(60, 141, 188, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: true },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return ' ₱' + ctx.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString('en-PH');
                        }
                    }
                }
            }
        }
    });
});
</script>
