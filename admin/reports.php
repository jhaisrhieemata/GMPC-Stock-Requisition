<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'reports';
$pageTitle = 'Reports - GMPC Stock Requisition';

// Get report data
$reportType = $_GET['type'] ?? 'inventory';

// Inventory status report
$inventoryByStatus = $conn->query("
    SELECT status, COUNT(*) as count, SUM(qty) as total_qty 
    FROM inventory 
    GROUP BY status
");

// Inventory by classification
$inventoryByClassification = $conn->query("
    SELECT classification, COUNT(*) as count, SUM(qty) as total_qty 
    FROM inventory 
    GROUP BY classification
");

// Requisitions by status
$reqByStatus = $conn->query("
    SELECT status, COUNT(*) as count, SUM(total_amount) as total_amount 
    FROM requisitions 
    GROUP BY status
");

// Requisitions by branch
$reqByBranch = $conn->query("
    SELECT b.name, COUNT(r.id) as count, SUM(r.total_amount) as total 
    FROM branches b 
    LEFT JOIN requisitions r ON b.id = r.branch_id 
    GROUP BY b.id, b.name 
    ORDER BY total DESC
    LIMIT 10
");

// Monthly requisitions
$monthlyReq = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(total_amount) as total 
    FROM requisitions 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
    ORDER BY month
");

// Low stock items
$lowStock = $conn->query("
    SELECT i.*, s.name as supplier_name 
    FROM inventory i 
    LEFT JOIN suppliers s ON i.supplier_id = s.id 
    WHERE i.status IN ('Low Stock', 'Out of Stock', 'Critical') 
    ORDER BY i.qty ASC
    LIMIT 20
");

// Special requests by status
$srByStatus = $conn->query("
    SELECT status, COUNT(*) as count, SUM(total_amount) as total_amount 
    FROM special_requests 
    GROUP BY status
");

include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-bar-chart me-2"></i>Reports</h4>
    </div>

    <!-- Report Type Tabs -->
    <ul class="nav nav-pills mb-4" id="reportTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $reportType == 'inventory' ? 'active' : '' ?>" href="?type=inventory">Inventory</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $reportType == 'requisitions' ? 'active' : '' ?>" href="?type=requisitions">Requisitions</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $reportType == 'lowstock' ? 'active' : '' ?>" href="?type=lowstock">Low Stock</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $reportType == 'branches' ? 'active' : '' ?>" href="?type=branches">By Branch</a>
        </li>
    </ul>

    <?php if ($reportType == 'inventory'): ?>
    <!-- Inventory Reports -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Inventory by Status</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Items Count</th>
                                    <th>Total Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $inventoryByStatus->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= $row['count'] ?></td>
                                    <td><?= number_format($row['total_qty']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Inventory by Classification</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Classification</th>
                                    <th>Items Count</th>
                                    <th>Total Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $inventoryByClassification->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['classification'] ?></td>
                                    <td><?= $row['count'] ?></td>
                                    <td><?= number_format($row['total_qty']) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($reportType == 'requisitions'): ?>
    <!-- Requisitions Reports -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Requisitions by Status</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $reqByStatus->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge status-badge status-<?= strtolower($row['status']) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= $row['count'] ?></td>
                                    <td>₱<?= number_format($row['total_amount'] ?? 0, 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Special Requests by Status</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $srByStatus->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['status'] ?></td>
                                    <td><?= $row['count'] ?></td>
                                    <td>₱<?= number_format($row['total_amount'] ?? 0, 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($reportType == 'lowstock'): ?>
    <!-- Low Stock Report -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Low Stock & Out of Stock Items</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item ID</th>
                            <th>Description</th>
                            <th>Supplier</th>
                            <th>Qty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $lowStock->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['item_id'] ?></td>
                            <td><?= $row['description'] ?></td>
                            <td><?= $row['supplier_name'] ?? 'N/A' ?></td>
                            <td><?= $row['qty'] ?></td>
                            <td>
                                <span class="badge status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($lowStock->num_rows === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted">No low stock items</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($reportType == 'branches'): ?>
    <!-- By Branch Report -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Requisitions by Branch</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Branch</th>
                            <th>Total Requisitions</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $reqByBranch->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['name'] ?></td>
                            <td><?= $row['count'] ?></td>
                            <td>₱<?= number_format($row['total'] ?? 0, 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
