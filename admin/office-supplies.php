<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'office-supplies';
$pageTitle = 'Office Supplies - GMPC Stock Requisition';

// Handle batch status update
if (isset($_POST['batch_update']) && isset($_POST['selected_items'])) {
    $selectedIds = $_POST['selected_items'];
    $newStatus = $_POST['batch_status'];
    
    foreach ($selectedIds as $id) {
        $id = (int)$id;
        $conn->query("UPDATE requisitions SET status = '$newStatus' WHERE id = $id");
    }
    $success = count($selectedIds) . " items updated to '$newStatus'";
}

// Handle single status update
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = sanitize($conn, $_POST['status']);
    $conn->query("UPDATE requisitions SET status = '$status' WHERE id = $id");
    $success = "Status updated successfully";
}

// Filters
$search = $_GET['search'] ?? '';
$branchFilter = $_GET['branch'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query - get individual items
$query = "SELECT 
            ri.id as item_id,
            r.id as requisition_id,
            r.requisition_code,
            r.status as requisition_status,
            b.name as branch_name,
            ri.description,
            ri.qty,
            ri.unit,
            ri.unit_price,
            ri.amount,
            ri.status as item_status,
            r.created_at
          FROM requisition_items ri
          JOIN requisitions r ON ri.requisition_id = r.id
          LEFT JOIN branches b ON r.branch_id = b.id
          WHERE 1=1";

if ($search) {
    $searchSafe = sanitize($conn, $search);
    $query .= " AND (r.requisition_code LIKE '%$searchSafe%' OR ri.description LIKE '%$searchSafe%' OR b.name LIKE '%$searchSafe%')";
}

if ($branchFilter) {
    $query .= " AND r.branch_id = " . (int)$branchFilter;
}

if ($statusFilter) {
    $query .= " AND r.status = '" . sanitize($conn, $statusFilter) . "'";
}

if ($dateFrom) {
    $query .= " AND DATE(r.created_at) >= '" . sanitize($conn, $dateFrom) . "'";
}

if ($dateTo) {
    $query .= " AND DATE(r.created_at) <= '" . sanitize($conn, $dateTo) . "'";
}

$query .= " ORDER BY r.created_at DESC, ri.id ASC";
$items = $conn->query($query);

// Calculate total
$totalAmount = 0;
while ($row = $items->fetch_assoc()) {
    $totalAmount += $row['amount'];
}
$items->data_seek(0);

// Get branches for dropdown
$branches = $conn->query("SELECT id, name FROM branches ORDER BY name");

include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-shop me-2"></i>All Office Supplies Requests</h4>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer me-2"></i>Print
            <span class="badge bg-light text-dark ms-2">₱<?= number_format($totalAmount, 2) ?></span>
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" placeholder="Code, Description, Branch..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select class="form-select" name="branch">
                        <option value="">All Branches</option>
                        <?php $branches->data_seek(0); while ($branch = $branches->fetch_assoc()): ?>
                            <option value="<?= $branch['id'] ?>" <?= $branchFilter == $branch['id'] ? 'selected' : '' ?>><?= $branch['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $statusFilter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $statusFilter == 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="No Stocks" <?= $statusFilter == 'No Stocks' ? 'selected' : '' ?>>No Stocks</option>
                        <option value="Petty Cash By Branch" <?= $statusFilter == 'Petty Cash By Branch' ? 'selected' : '' ?>>Petty Cash By Branch</option>
                        <option value="Completed" <?= $statusFilter == 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Rejected" <?= $statusFilter == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="Cancelled" <?= $statusFilter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="<?= $dateFrom ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="<?= $dateTo ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Batch Actions -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="POST" class="d-flex align-items-center gap-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label" for="selectAll">Select All</label>
                </div>
                <div class="border-start ps-3">
                    <label class="form-label mb-0 me-2">Batch Action:</label>
                    <select class="form-select form-select-sm d-inline-block w-auto" name="batch_status" required>
                        <option value="">Select Status</option>
                        <option value="Approved">Approved</option>
                        <option value="No Stocks">No Stocks</option>
                        <option value="Petty Cash By Branch">Petty Cash By Branch</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                    <button type="submit" name="batch_update" class="btn btn-sm btn-success">Apply</button>
                </div>
                <div class="ms-auto">
                    <span class="badge bg-primary fs-6">Total: ₱<?= number_format($totalAmount, 2) ?></span>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="officeSuppliesTable">
                    <thead class="table-light">
                        <tr>
                            <th width="40"><input type="checkbox" class="form-check-input" id="selectAll"></th>
                            <th>Code</th>
                            <th>Branch</th>
                            <th>Description</th>
                            <th>Qty</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items->num_rows > 0): ?>
                            <?php while ($row = $items->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input item-checkbox" name="selected_items[]" value="<?= $row['requisition_id'] ?>">
                                </td>
                                <td><?= $row['requisition_code'] ?></td>
                                <td><?= $row['branch_name'] ?></td>
                                <td><?= $row['description'] ?></td>
                                <td><?= $row['qty'] ?></td>
                                <td><?= $row['unit'] ?></td>
                                <td>₱<?= number_format($row['unit_price'], 2) ?></td>
                                <td>₱<?= number_format($row['amount'], 2) ?></td>
                                <td>
                                    <?php 
                                    $statusClass = 'bg-secondary';
                                    if ($row['requisition_status'] === 'Pending') $statusClass = 'bg-warning text-dark';
                                    elseif ($row['requisition_status'] === 'Approved') $statusClass = 'bg-success';
                                    elseif ($row['requisition_status'] === 'Rejected' || $row['requisition_status'] === 'Cancelled') $statusClass = 'bg-danger';
                                    elseif ($row['requisition_status'] === 'No Stocks') $statusClass = 'bg-danger';
                                    elseif ($row['requisition_status'] === 'Petty Cash By Branch') $statusClass = 'bg-info text-dark';
                                    elseif ($row['requisition_status'] === 'Completed') $statusClass = 'bg-primary';
                                    ?>
                                    <span class="badge <?= $statusClass ?>"><?= $row['requisition_status'] ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateModal<?= $row['requisition_id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Update Status Modal -->
                            <div class="modal fade" id="updateModal<?= $row['requisition_id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Status - <?= $row['requisition_code'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <p><strong>Branch:</strong> <?= $row['branch_name'] ?></p>
                                                <p><strong>Description:</strong> <?= $row['description'] ?></p>
                                                <p><strong>Qty:</strong> <?= $row['qty'] ?> <?= $row['unit'] ?></p>
                                                <p><strong>Amount:</strong> ₱<?= number_format($row['amount'], 2) ?></p>
                                                <hr>
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="id" value="<?= $row['requisition_id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select class="form-select" name="status" required>
                                                        <option value="Pending" <?= $row['requisition_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="Approved" <?= $row['requisition_status'] == 'Approved' ? 'selected' : '' ?>>Approved</option>
                                                        <option value="No Stocks" <?= $row['requisition_status'] == 'No Stocks' ? 'selected' : '' ?>>No Stocks</option>
                                                        <option value="Petty Cash By Branch" <?= $row['requisition_status'] == 'Petty Cash By Branch' ? 'selected' : '' ?>>Petty Cash By Branch</option>
                                                        <option value="Completed" <?= $row['requisition_status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                        <option value="Rejected" <?= $row['requisition_status'] == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                        <option value="Cancelled" <?= $row['requisition_status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
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
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center text-muted py-4">No records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});
</script>

<?php include '../includes/footer.php'; ?>
