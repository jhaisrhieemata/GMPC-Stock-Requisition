<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] === 'admin') {
    redirect('../login.php');
}

$activePage = 'my-requests';
$branchName = $_SESSION['branch'];

// Get branch ID
$branchResult = $conn->query("SELECT id FROM branches WHERE name = '$branchName'");
$branch = $branchResult->fetch_assoc();
$branchId = $branch['id'] ?? 0;

// Filter
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';

// Get requisitions
$reqWhere = "WHERE r.branch_id = $branchId";
if ($statusFilter) $reqWhere .= " AND r.status = '$statusFilter'";
$requisitions = $conn->query("
    SELECT r.*, b.name as branch_name 
    FROM requisitions r 
    JOIN branches b ON r.branch_id = b.id 
    $reqWhere
    ORDER BY r.created_at DESC
");

// Get special requests
$srWhere = "WHERE sr.branch_id = $branchId";
if ($statusFilter) $srWhere .= " AND sr.status = '$statusFilter'";
$specialRequests = $conn->query("
    SELECT sr.*, b.name as branch_name 
    FROM special_requests sr 
    JOIN branches b ON sr.branch_id = b.id 
    $srWhere
    ORDER BY sr.created_at DESC
");

$pageTitle = 'My Requests - GMPC Branch Portal';
include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-file-earmark-text me-2"></i>My Requests</h4>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $statusFilter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $statusFilter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $statusFilter == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="Completed" <?= $statusFilter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel me-2"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Nav Tabs -->
    <ul class="nav nav-tabs mb-3" id="requestTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="office-tab" data-bs-toggle="tab" data-bs-target="#office" type="button">
                <i class="bi bi-file-earmark-text me-2"></i>Office Supplies
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="special-tab" data-bs-toggle="tab" data-bs-target="#special" type="button">
                <i class="bi bi-exclamation-triangle me-2"></i>Special Requests
            </button>
        </li>
    </ul>

    <div class="tab-content" id="requestTabsContent">
        <!-- Office Supplies Tab -->
        <div class="tab-pane fade show active" id="office" role="tabpanel">
            <div class="card table-card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>To</th>
                                    <th>Purpose</th>
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
                                    <td><?= $row['to'] ?></td>
                                    <td><?= $row['purpose'] ?></td>
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
                                                        <p><strong>To:</strong> <?= $row['to'] ?></p>
                                                        <p><strong>Purpose:</strong> <?= $row['purpose'] ?></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p><strong>Status:</strong> 
                                                            <span class="badge status-badge status-<?= strtolower($row['status']) ?>">
                                                                <?= $row['status'] ?>
                                                            </span>
                                                        </p>
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
                                                        </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th colspan="4" class="text-end">Total</th>
                                                            <th>₱<?= number_format($row['total_amount'], 2) ?></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                                <?php if ($row['note']): ?>
                                                <p><strong>Note:</strong> <?= $row['note'] ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php if ($requisitions->num_rows === 0): ?>
                                <tr><td colspan="7" class="text-center text-muted">No requisitions found</td></tr>
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
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $specialRequests->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['request_code'] ?></td>
                                    <td><?= substr($row['description'], 0, 40) ?><?= strlen($row['description']) > 40 ? '...' : '' ?></td>
                                    <td><?= $row['qty'] ?> <?= $row['unit'] ?></td>
                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewSprModal<?= $row['id'] ?>">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- View Modal -->
                                <div class="modal fade" id="viewSprModal<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Special Request: <?= $row['request_code'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Description:</strong> <?= $row['description'] ?></p>
                                                <p><strong>Quantity:</strong> <?= $row['qty'] ?> <?= $row['unit'] ?></p>
                                                <p><strong>Estimated Price:</strong> ₱<?= number_format($row['estimated_price'], 2) ?></p>
                                                <p><strong>Total Amount:</strong> ₱<?= number_format($row['total_amount'], 2) ?></p>
                                                <p><strong>Purpose:</strong> <?= $row['purpose'] ?></p>
                                                <p><strong>Status:</strong> 
                                                    <span class="badge status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                                        <?= $row['status'] ?>
                                                    </span>
                                                </p>
                                                <p><strong>Date:</strong> <?= date('M d, Y', strtotime($row['created_at'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php if ($specialRequests->num_rows === 0): ?>
                                <tr><td colspan="7" class="text-center text-muted">No special requests found</td></tr>
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
