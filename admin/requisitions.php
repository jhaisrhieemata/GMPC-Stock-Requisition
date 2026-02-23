<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'requisitions';
$pageTitle = 'Requisitions Management - GMPC Stock Requisition';

// Handle status update
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = sanitize($conn, $_POST['status']);
    $type = sanitize($conn, $_POST['type']);
    
    if ($type === 'requisition') {
        $conn->query("UPDATE requisitions SET status = '$status' WHERE id = $id");
    } else {
        $conn->query("UPDATE special_requests SET status = '$status' WHERE id = $id");
    }
    $success = "Status updated successfully";
}

// Filter by status
$statusFilter = $_GET['status'] ?? '';
$where = '';
if ($statusFilter) {
    $where = "WHERE r.status = '$statusFilter'";
}

// Get requisitions
$requisitions = $conn->query("
    SELECT r.*, b.name as branch_name 
    FROM requisitions r 
    JOIN branches b ON r.branch_id = b.id 
    $where
    ORDER BY r.created_at DESC
");

// Get special requests
$specialRequests = $conn->query("
    SELECT sr.*, b.name as branch_name 
    FROM special_requests sr 
    JOIN branches b ON sr.branch_id = b.id 
    ORDER BY sr.created_at DESC
");

include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-file-earmark-text me-2"></i>Requisitions Management</h4>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Status</label>
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $statusFilter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $statusFilter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $statusFilter == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="Completed" <?= $statusFilter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs mb-3" id="requisitionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="office-tab" data-bs-toggle="tab" data-bs-target="#office" type="button">
                <i class="bi bi-file-earmark-text me-2"></i>Office Supplies
                <span class="badge bg-warning ms-2"><?= $requisitions->num_rows ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="special-tab" data-bs-toggle="tab" data-bs-target="#special" type="button">
                <i class="bi bi-exclamation-triangle me-2"></i>Special Requests
                <span class="badge bg-danger ms-2"><?= $specialRequests->num_rows ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="requisitionTabsContent">
        <!-- Office Supplies Tab -->
        <div class="tab-pane fade show active" id="office" role="tabpanel">
            <div class="card table-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Branch</th>
                                    <th>To</th>
                                    <th>Requested By</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $requisitions->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['requisition_code'] ?></td>
                                    <td><?= $row['branch_name'] ?></td>
                                    <td><?= $row['to'] ?></td>
                                    <td><?= $row['requested_by'] ?></td>
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge status-badge status-<?= strtolower($row['status']) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewReqModal<?= $row['id'] ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateReqStatus<?= $row['id'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- View Modal -->
                                <div class="modal fade" id="viewReqModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Requisition: <?= $row['requisition_code'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Branch:</strong> <?= $row['branch_name'] ?></p>
                                                        <p><strong>To:</strong> <?= $row['to'] ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Requested By:</strong> <?= $row['requested_by'] ?></p>
                                                        <p><strong>Date:</strong> <?= date('M d, Y', strtotime($row['created_at'])) ?></p>
                                                    </div>
                                                </div>
                                                <h6>Items</h6>
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Description</th>
                                                            <th>Qty</th>
                                                            <th>Unit</th>
                                                            <th>Unit Price</th>
                                                            <th>Amount</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        $items = $conn->query("SELECT * FROM requisition_items WHERE requisition_id = {$row['id']}");
                                                        while ($item = $items->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?= $item['description'] ?></td>
                                                            <td><?= $item['qty'] ?></td>
                                                            <td><?= $item['unit'] ?></td>
                                                            <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                                                            <td>₱<?= number_format($item['amount'], 2) ?></td>
                                                            <td><?= $item['status'] ?></td>
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                                <?php if ($row['note']): ?>
                                                <p><strong>Note:</strong> <?= $row['note'] ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Update Status Modal -->
                                <div class="modal fade" id="updateReqStatus<?= $row['id'] ?>" tabindex="-1">
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
                                                    <input type="hidden" name="type" value="requisition">
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select class="form-select" name="status" required>
                                                            <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                            <option value="Approved" <?= $row['status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                                            <option value="Rejected" <?= $row['status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                            <option value="Completed" <?= $row['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
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
                                <?php if ($requisitions->num_rows === 0): ?>
                                <tr><td colspan="8" class="text-center text-muted">No requisitions found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Special Requests Tab -->
        <div class="tab-pane fade" id="special" role="tabpanel">
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
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $specialRequests->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['request_code'] ?></td>
                                    <td><?= $row['branch_name'] ?></td>
                                    <td><?= substr($row['description'], 0, 40) ?><?= strlen($row['description']) > 40 ? '...' : '' ?></td>
                                    <td><?= $row['qty'] ?> <?= $row['unit'] ?></td>
                                    <td>₱<?= number_format($row['estimated_price'], 2) ?></td>
                                    <td>
                                        <span class="badge status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateSprStatus<?= $row['id'] ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- Update Status Modal -->
                                <div class="modal fade" id="updateSprStatus<?= $row['id'] ?>" tabindex="-1">
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
                                                    <input type="hidden" name="type" value="special">
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
                                <tr><td colspan="8" class="text-center text-muted">No special requests found</td></tr>
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
