<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] === 'admin') {
    redirect('../login.php');
}

$activePage = 'new-requisition';
$branchName = $_SESSION['branch'];

// Get branch ID
$branchResult = $conn->query("SELECT id, email FROM branches WHERE name = '$branchName'");
$branch = $branchResult->fetch_assoc();
$branchId = $branch['id'] ?? 0;

// Get inventory items for selection
$inventory = $conn->query("SELECT * FROM inventory WHERE qty > 0 ORDER BY description");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestType = sanitize($conn, $_POST['request_type']);
    
    if ($requestType === 'OFFICE SUPPLIES') {
        $requisitionCode = generateRequisitionCode($conn);
        $to = sanitize($conn, $_POST['to']);
        $purpose = sanitize($conn, $_POST['purpose']);
        $note = sanitize($conn, $_POST['note']);
        $requestedBy = $_SESSION['name'];
        
        // Calculate total
        $items = $_POST['items'] ?? [];
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += ($item['qty'] * $item['unit_price']);
        }
        
        $stmt = $conn->prepare("INSERT INTO requisitions (requisition_code, user_id, branch_id, request_type, `to`, purpose, note, requested_by, status, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)");
        $stmt->bind_param("siisssssd", $requisitionCode, $_SESSION['user_id'], $branchId, $requestType, $to, $purpose, $note, $requestedBy, $totalAmount);
        
        if ($stmt->execute()) {
            $reqId = $conn->insert_id;
            
            // Insert items
            foreach ($items as $item) {
                if (!empty($item['description']) && $item['qty'] > 0) {
                    $desc = sanitize($conn, $item['description']);
                    $qty = (int)$item['qty'];
                    $unit = sanitize($conn, $item['unit']);
                    $uprice = (float)$item['unit_price'];
                    $amount = $qty * $uprice;
                    
                    $stmt2 = $conn->prepare("INSERT INTO requisition_items (requisition_id, description, qty, unit, unit_price, amount, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
                    $stmt2->bind_param("isisdd", $reqId, $desc, $qty, $unit, $uprice, $amount);
                    $stmt2->execute();
                }
            }
            
            $success = "Requisition submitted successfully! Code: $requisitionCode";
        } else {
            $error = "Error submitting requisition: " . $conn->error;
        }
    } else {
        // Special request
        $requestCode = generateSpecialRequestCode($conn);
        $description = sanitize($conn, $_POST['description']);
        $qty = (int)$_POST['qty'];
        $unit = sanitize($conn, $_POST['unit']);
        $estimatedPrice = (float)$_POST['estimated_price'];
        $totalAmount = $qty * $estimatedPrice;
        $purpose = sanitize($conn, $_POST['purpose']);
        $requestedBy = $_SESSION['name'];
        
        $stmt = $conn->prepare("INSERT INTO special_requests (request_code, branch_id, description, qty, unit, estimated_price, total_amount, purpose, requested_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("sisissdss", $requestCode, $branchId, $description, $qty, $unit, $estimatedPrice, $totalAmount, $purpose, $requestedBy);
        
        if ($stmt->execute()) {
            $success = "Special request submitted successfully! Code: $requestCode";
        } else {
            $error = "Error submitting special request: " . $conn->error;
        }
    }
}

$pageTitle = 'New Requisition - GMPC Branch Portal';
include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-plus-circle me-2"></i>New Requisition</h4>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Request Type Selection -->
    <div class="row mb-4">
        <div class="col-md-6">
            <a href="?type=office" class="text-decoration-none">
                <div class="card <?= ($_GET['type'] ?? 'office') === 'office' ? 'border-primary' : '' ?>">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-text fs-1 text-primary"></i>
                        <h5 class="mt-2">Office Supplies</h5>
                        <p class="text-muted mb-0">Request items from available inventory</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="?type=special" class="text-decoration-none">
                <div class="card <?= ($_GET['type'] ?? '') === 'special' ? 'border-warning' : '' ?>">
                    <div class="card-body text-center">
                        <i class="bi bi-exclamation-triangle fs-1 text-warning"></i>
                        <h5 class="mt-2">Special Request</h5>
                        <p class="text-muted mb-0">Request items not in inventory</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <?php if (($_GET['type'] ?? 'office') === 'office'): ?>
    <!-- Office Supplies Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Office Supplies Requisition</h5>
        </div>
        <div class="card-body">
            <form method="POST" id="officeForm">
                <input type="hidden" name="request_type" value="OFFICE SUPPLIES">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" value="<?= $branchName ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" value="<?= date('F d, Y') ?>" disabled>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">To</label>
                        <select class="form-select" name="to" required>
                            <option value="GMPC Purchasing">GMPC Purchasing</option>
                            <option value="Warehouse">Warehouse</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purpose</label>
                        <select class="form-select" name="purpose" required>
                            <option value="OFFICE SUPPLIES">Office Supplies</option>
                            <option value="OPERATIONAL">Operational</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Note (Optional)</label>
                    <textarea class="form-control" name="note" rows="2" placeholder="Any additional notes..."></textarea>
                </div>
                
                <h5 class="mb-3">Items</h5>
                <div id="itemsContainer">
                    <div class="row g-3 mb-2 item-row">
                        <div class="col-md-4">
                            <select class="form-select item-select" name="items[0][item_id]" onchange="updateItemDetails(this)">
                                <option value="">Select Item</option>
                                <?php while ($row = $inventory->fetch_assoc()): ?>
                                <option value="<?= $row['id'] ?>" data-desc="<?= $row['description'] ?>" data-unit="<?= $row['unit'] ?>" data-price="<?= $row['unit_price'] ?>" data-qty="<?= $row['qty'] ?>">
                                    <?= $row['description'] ?> (<?= $row['unit'] ?>) - ₱<?= number_format($row['unit_price'], 2) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control item-description" name="items[0][description]" placeholder="Description" readonly>
                        </div>
                        <div class="col-md-2">
                            <input type="number" class="form-control item-qty" name="items[0][qty]" placeholder="Qty" min="1" value="1">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control item-unit" name="items[0][unit]" placeholder="Unit" readonly>
                        </div>
                        <div class="col-md-1">
                            <input type="hidden" class="item-price" name="items[0][unit_price]" value="0">
                            <button type="button" class="btn btn-danger" onclick="removeItem(this)"><i class="bi bi-x"></i></button>
                        </div>
                    </div>
                </div>
                
                <button type="button" class="btn btn-secondary mb-3" onclick="addItem()">
                    <i class="bi bi-plus me-2"></i>Add Item
                </button>
                
                <hr>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Submit Requisition
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Special Request Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Special Request Form</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="request_type" value="SPECIAL REQUEST">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Branch</label>
                        <input type="text" class="form-control" value="<?= $branchName ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date</label>
                        <input type="text" class="form-control" value="<?= date('F d, Y') ?>" disabled>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Item Description</label>
                    <input type="text" class="form-control" name="description" required placeholder="Enter item description">
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="qty" required min="1" value="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit</label>
                        <select class="form-select" name="unit" required>
                            <option value="PC">PC</option>
                            <option value="UNIT">UNIT</option>
                            <option value="SET">SET</option>
                            <option value="BOX">BOX</option>
                            <option value="REAM">REAM</option>
                            <option value="ROLL">ROLL</option>
                            <option value="BTL">BTL</option>
                            <option value="PAD">PAD</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estimated Price (per unit)</label>
                        <input type="number" class="form-control" name="estimated_price" required min="0" step="0.01" value="0">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Purpose</label>
                    <textarea class="form-control" name="purpose" rows="2" required placeholder="Explain why this item is needed..."></textarea>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-send me-2"></i>Submit Special Request
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
let itemCount = 1;

function addItem() {
    const container = document.getElementById('itemsContainer');
    const row = document.createElement('div');
    row.className = 'row g-3 mb-2 item-row';
    row.innerHTML = `
        <div class="col-md-4">
            <select class="form-select item-select" name="items[${itemCount}][item_id]" onchange="updateItemDetails(this)">
                <option value="">Select Item</option>
                <?php 
                $inventory->data_seek(0);
                while ($row = $inventory->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>" data-desc="<?= $row['description'] ?>" data-unit="<?= $row['unit'] ?>" data-price="<?= $row['unit_price'] ?>" data-qty="<?= $row['qty'] ?>">
                    <?= $row['description'] ?> (<?= $row['unit'] ?>) - ₱<?= number_format($row['unit_price'], 2) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control item-description" name="items[${itemCount}][description]" placeholder="Description" readonly>
        </div>
        <div class="col-md-2">
            <input type="number" class="form-control item-qty" name="items[${itemCount}][qty]" placeholder="Qty" min="1" value="1">
        </div>
        <div class="col-md-2">
            <input type="text" class="form-control item-unit" name="items[${itemCount}][unit]" placeholder="Unit" readonly>
        </div>
        <div class="col-md-1">
            <input type="hidden" class="item-price" name="items[${itemCount}][unit_price]" value="0">
            <button type="button" class="btn btn-danger" onclick="removeItem(this)"><i class="bi bi-x"></i></button>
        </div>
    `;
    container.appendChild(row);
    itemCount++;
}

function removeItem(btn) {
    const row = btn.closest('.item-row');
    if (document.querySelectorAll('.item-row').length > 1) {
        row.remove();
    }
}

function updateItemDetails(select) {
    const row = select.closest('.item-row');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        row.querySelector('.item-description').value = option.dataset.desc;
        row.querySelector('.item-unit').value = option.dataset.unit;
        row.querySelector('.item-price').value = option.dataset.price;
        row.querySelector('.item-qty').max = option.dataset.qty;
    } else {
        row.querySelector('.item-description').value = '';
        row.querySelector('.item-unit').value = '';
        row.querySelector('.item-price').value = '0';
    }
}
</script>

<?php include '../includes/footer.php'; ?>
