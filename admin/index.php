<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'dashboard';

// Get dashboard stats
$stats = [
    'totalInventory' => 0,
    'lowStock' => 0,
    'outOfStock' => 0,
    'totalSuppliers' => 0,
    'totalBranches' => 0,
    'pendingRequisitions' => 0,
    'pendingSpecialRequests' => 0,
    'totalRequisitions' => 0
];

$result = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE status = 'In Stock'");
if ($row = $result->fetch_assoc()) $stats['totalInventory'] = $row['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE status = 'Low Stock'");
if ($row = $result->fetch_assoc()) $stats['lowStock'] = $row['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE status IN ('Out of Stock', 'Critical')");
if ($row = $result->fetch_assoc()) $stats['outOfStock'] = $row['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM suppliers WHERE is_active = 1");
if ($row = $result->fetch_assoc()) $stats['totalSuppliers'] = $row['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM branches WHERE is_active = 1");
if ($row = $result->fetch_assoc()) $stats['totalBranches'] = $row['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM requisitions WHERE status = 'Pending'");
if ($row = $result->fetch_assoc()) $stats['pendingRequisitions'] = $row['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM special_requests WHERE status = 'Pending'");
if ($row = $result->fetch_assoc()) $stats['pendingSpecialRequests'] = $row['count'];

$result = $conn->query("SELECT COUNT(*) as count FROM requisitions");
if ($row = $result->fetch_assoc()) $stats['totalRequisitions'] = $row['count'];

// Get recent requisitions
$recentRequisitions = $conn->query("
    SELECT r.*, b.name as branch_name 
    FROM requisitions r 
    JOIN branches b ON r.branch_id = b.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");

// Get recent special requests
$recentSpecialRequests = $conn->query("
    SELECT sr.*, b.name as branch_name 
    FROM special_requests sr 
    JOIN branches b ON sr.branch_id = b.id 
    ORDER BY sr.created_at DESC 
    LIMIT 10
");

$pageTitle = 'Admin Dashboard - GMPC Stock Requisition';
include '../includes/header.php';
?>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-speedometer2 me-2"></i>Dashboard</h4>
        <span class="text-muted">Welcome, <?= $_SESSION['name'] ?></span>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Inventory</h6>
                            <h2 class="mb-0"><?= $stats['totalInventory'] ?></h2>
                        </div>
                        <i class="bi bi-boxes fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Low Stock</h6>
                            <h2 class="mb-0"><?= $stats['lowStock'] ?></h2>
                        </div>
                        <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Out of Stock</h6>
                            <h2 class="mb-0"><?= $stats['outOfStock'] ?></h2>
                        </div>
                        <i class="bi bi-x-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Suppliers</h6>
                            <h2 class="mb-0"><?= $stats['totalSuppliers'] ?></h2>
                        </div>
                        <i class="bi bi-truck fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Branches</h6>
                            <h2 class="mb-0"><?= $stats['totalBranches'] ?></h2>
                        </div>
                        <i class="bi bi-building fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Pending Requisitions</h6>
                            <h2 class="mb-0"><?= $stats['pendingRequisitions'] ?></h2>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Pending Special</h6>
                            <h2 class="mb-0"><?= $stats['pendingSpecialRequests'] ?></h2>
                        </div>
                        <i class="bi bi-file-earmark-plus fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card" style="background: linear-gradient(135deg, #1e3a5f, #2c5282); color: white;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Total Requisitions</h6>
                            <h2 class="mb-0"><?= $stats['totalRequisitions'] ?></h2>
                        </div>
                        <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Requisitions and Special Requests -->
    <div class="row">
        <div class="col-md-6">
            <div class="card table-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Recent Requisitions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Branch</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $recentRequisitions->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['requisition_code'] ?></td>
                                    <td><?= $row['branch_name'] ?></td>
                                    <td><?= $row['request_type'] ?></td>
                                    <td>
                                        <span class="badge status-badge status-<?= strtolower($row['status']) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($recentRequisitions->num_rows === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted">No requisitions yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card table-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Recent Special Requests</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Branch</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $recentSpecialRequests->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['request_code'] ?></td>
                                    <td><?= $row['branch_name'] ?></td>
                                    <td><?= substr($row['description'], 0, 30) ?><?= strlen($row['description']) > 30 ? '...' : '' ?></td>
                                    <td>
                                        <span class="badge status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($recentSpecialRequests->num_rows === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted">No special requests yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
