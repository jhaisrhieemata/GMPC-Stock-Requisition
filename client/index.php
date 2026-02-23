<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] === 'admin') {
    redirect('../login.php');
}

$activePage = 'dashboard';
$branchId = $_SESSION['branch_id'] ?? 0;

// Get branch name
$branchName = $_SESSION['branch'] ?? 'Branch';

// Get stats for this branch
$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'completed' => 0,
    'total' => 0
];

if ($branchId > 0) {
$result = $conn->query("SELECT status, COUNT(*) as count FROM requisitions WHERE branch_id = $branchId GROUP BY status");
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'Pending') $stats['pending'] = $row['count'];
    elseif ($row['status'] === 'Approved') $stats['approved'] = $row['count'];
    elseif ($row['status'] === 'Rejected') $stats['rejected'] = $row['count'];
    elseif ($row['status'] === 'Completed') $stats['completed'] = $row['count'];
    $stats['total'] += $row['count'];
}

// Get special request stats
$srStats = ['pending' => 0, 'approved' => 0, 'total' => 0];
$result = $conn->query("SELECT status, COUNT(*) as count FROM special_requests WHERE branch_id = $branchId GROUP BY status");
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'Pending') $srStats['pending'] = $row['count'];
    elseif (strpos($row['status'], 'Approved') !== false) $srStats['approved'] = $row['count'];
    $srStats['total'] += $row['count'];
}
}

// Get recent requisitions
$recentReqs = $conn->query("
    SELECT * FROM requisitions 
    WHERE branch_id = $branchId 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get recent special requests
$recentSpr = $conn->query("
    SELECT * FROM special_requests 
    WHERE branch_id = $branchId 
    ORDER BY created_at DESC 
    LIMIT 5
");

$pageTitle = 'Dashboard - GMPC Branch Portal';
include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-speedometer2 me-2"></i>Dashboard</h4>
        <span class="text-muted">Welcome, <?= $_SESSION['name'] ?></span>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-warning text-dark">
                <div class="card-body">
                    <h3><?= $stats['pending'] ?></h3>
                    <small>Pending Requisitions</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <h3><?= $stats['approved'] ?></h3>
                    <small>Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <h3><?= $srStats['pending'] ?></h3>
                    <small>Pending Special Requests</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card" style="background: linear-gradient(135deg, #1e3a5f, #2c5282); color: white;">
                <div class="card-body">
                    <h3><?= $stats['total'] + $srStats['total'] ?></h3>
                    <small>Total Requests</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-plus fs-1 text-primary mb-3"></i>
                    <h5>New Office Supplies Request</h5>
                    <p class="text-muted">Request office supplies from inventory</p>
                    <a href="new-requisition.php?type=office" class="btn btn-primary">Create Request</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle fs-1 text-warning mb-3"></i>
                    <h5>New Special Request</h5>
                    <p class="text-muted">Request items not in inventory</p>
                    <a href="new-requisition.php?type=special" class="btn btn-warning">Create Request</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card table-card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Recent Requisitions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $recentReqs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['requisition_code'] ?></td>
                                    <td><?= $row['request_type'] ?></td>
                                    <td>
                                        <span class="badge status-badge status-<?= strtolower($row['status']) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d', strtotime($row['created_at'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($recentReqs->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted">No requisitions yet</td></tr>
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
                    <h5 class="mb-0">Recent Special Requests</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $recentSpr->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['request_code'] ?></td>
                                    <td><?= substr($row['description'], 0, 20) ?>...</td>
                                    <td>
                                        <span class="badge status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d', strtotime($row['created_at'])) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($recentSpr->num_rows === 0): ?>
                                <tr><td colspan="4" class="text-center text-muted">No special requests yet</td></tr>
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
