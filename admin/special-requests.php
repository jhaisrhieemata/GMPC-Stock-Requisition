<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'special-requests';
$pageTitle = 'Special Requests - GMPC Stock Requisition';

// Handle status update
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = sanitize($conn, $_POST['status']);
    $conn->query("UPDATE special_requests SET status = '$status' WHERE id = $id");
    $success = "Status updated successfully";
}

// Get special requests with stats
$specialRequests = $conn->query("
    SELECT sr.*, b.name as branch_name, b.email as branch_email
    FROM special_requests sr 
    JOIN branches b ON sr.branch_id = b.id 
    ORDER BY sr.created_at DESC
");

// Get stats
$stats = [
    'pending' => 0,
    'approved_purchasing' => 0,
    'approved_accounting' => 0,
    'to_purchased' => 0,
    'cancelled' => 0
];

$result = $conn->query("SELECT COUNT(*) as count, status FROM special_requests GROUP BY status");
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'Pending') $stats['pending'] = $row['count'];
    elseif ($row['status'] === 'Approved By Purchasing') $stats['approved_purchasing'] = $row['count'];
    elseif ($row['status'] === 'Approved By Accounting') $stats['approved_accounting'] = $row['count'];
    elseif ($row['status'] === 'To Purchased') $stats['to_purchased'] = $row['count'];
    elseif ($row['status'] === 'Cancelled') $stats['cancelled'] = $row['count'];
}

include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-exclamation-triangle me-2"></i>Special Requests</h4>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card stat-card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= $stats['pending'] ?></h3>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['approved_purchasing'] ?></h3>
                    <small>Approved (Purchasing)</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['approved_accounting'] ?></h3>
                    <small>Approved (Accounting)</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['to_purchased'] ?></h3>
                    <small>To Purchased</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['cancelled'] ?></h3>
                    <small>Cancelled</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <?php $total = array_sum($stats); ?>
            <div class="card stat-card" style="background: linear-gradient(135deg, #1e3a5f, #2c5282); color: white;">
                <div class="card-body text-center">
                    <h3><?= $total ?></h3>
                    <small>Total</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Special Requests Table -->
    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Branch</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Estimated Price</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Requested By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $specialRequests->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['request_code'] ?></td>
                            <td><?= $row['branch_name'] ?></td>
                            <td><?= substr($row['description'], 0, 30) ?><?= strlen($row['description']) > 30 ? '...' : '' ?></td>
                            <td><?= $row['qty'] ?> <?= $row['unit'] ?></td>
                            <td>₱<?= number_format($row['estimated_price'], 2) ?></td>
                            <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td><?= $row['requested_by'] ?></td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= $row['id'] ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?= $row['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <!-- View Modal -->
                        <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Special Request: <?= $row['request_code'] ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Branch:</strong> <?= $row['branch_name'] ?></p>
                                        <p><strong>Email:</strong> <?= $row['branch_email'] ?></p>
                                        <p><strong>Description:</strong> <?= $row['description'] ?></p>
                                        <p><strong>Quantity:</strong> <?= $row['qty'] ?> <?= $row['unit'] ?></p>
                                        <p><strong>Estimated Price:</strong> ₱<?= number_format($row['estimated_price'], 2) ?></p>
                                        <p><strong>Total Amount:</strong> ₱<?= number_format($row['total_amount'], 2) ?></p>
                                        <p><strong>Purpose:</strong> <?= $row['purpose'] ?></p>
                                        <p><strong>Requested By:</strong> <?= $row['requested_by'] ?></p>
                                        <p><strong>Date:</strong> <?= date('M d, Y', strtotime($row['created_at'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Update Status Modal -->
                        <div class="modal fade" id="updateModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Status</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Approved By Purchasing" <?= $row['status'] == 'Approved By Purchasing' ? 'selected' : '' ?>>Approved By Purchasing</option>
                                                    <option value="Approved By Accounting" <?= $row['status'] == 'Approved By Accounting' ? 'selected' : '' ?>>Approved By Accounting</option>
                                                    <option value="Petty Cash By Branch" <?= $row['status'] == 'Petty Cash By Branch' ? 'selected' : '' ?>>Petty Cash By Branch</option>
                                                    <option value="To Purchased" <?= $row['status'] == 'To Purchased' ? 'selected' : '' ?>>To Purchased</option>
                                                    <option value="Cancelled" <?= $row['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php if ($specialRequests->num_rows === 0): ?>
                        <tr><td colspan="10" class="text-center text-muted">No special requests found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
