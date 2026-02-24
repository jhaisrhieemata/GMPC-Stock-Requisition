<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'inventory';
$pageTitle = 'Inventory Management - GMPC Stock Requisition';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_item'])) {
        $item_id = sanitize($conn, $_POST['item_id']);
        $supplier_id = (int)$_POST['supplier_id'];
        $date = sanitize($conn, $_POST['date']);
        $description = sanitize($conn, $_POST['description']);
        $unit = sanitize($conn, $_POST['unit']);
        $qty = (int)$_POST['qty'];
        $unit_price = (float)$_POST['unit_price'];
        $amount = $qty * $unit_price;
        $classification = sanitize($conn, $_POST['classification']);
        
        $status = 'In Stock';
        if ($qty <= 0) $status = 'Out of Stock';
        elseif ($qty < 10) $status = 'Critical';
        elseif ($qty < 20) $status = 'Low Stock';
        
        $stmt = $conn->prepare("INSERT INTO inventory (item_id, supplier_id, date, description, unit, qty, unit_price, amount, total_running_stocks, status, classification) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssiddiss", $item_id, $supplier_id, $date, $description, $unit, $qty, $unit_price, $amount, $qty, $status, $classification);
        
        if ($stmt->execute()) {
            $success = "Item added successfully";
        } else {
            $error = "Error adding item: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_item'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM inventory WHERE id = $id");
        $success = "Item deleted successfully";
    }
    
    if (isset($_POST['update_item'])) {
        $id = (int)$_POST['id'];
        $description = sanitize($conn, $_POST['description']);
        $unit = sanitize($conn, $_POST['unit']);
        $qty = (int)$_POST['qty'];
        $unit_price = (float)$_POST['unit_price'];
        $amount = $qty * $unit_price;
        
        $status = 'In Stock';
        if ($qty <= 0) $status = 'Out of Stock';
        elseif ($qty < 10) $status = 'Critical';
        elseif ($qty < 20) $status = 'Low Stock';
        
        $stmt = $conn->prepare("UPDATE inventory SET description=?, unit=?, qty=?, unit_price=?, amount=?, total_running_stocks=?, status=? WHERE id=?");
        $stmt->bind_param("siiddisi", $description, $unit, $qty, $unit_price, $amount, $qty, $status, $id);
        $stmt->execute();
        $success = "Item updated successfully";
    }
}

// Get inventory items
$inventory = $conn->query("
    SELECT i.*, s.name as supplier_name 
    FROM inventory i 
    LEFT JOIN suppliers s ON i.supplier_id = s.id 
    ORDER BY i.id DESC
");

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name");

include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-boxes me-2"></i>Inventory Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="bi bi-plus-circle me-2"></i>Add Item
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="In Stock">In Stock</option>
                        <option value="Low Stock">Low Stock</option>
                        <option value="Out of Stock">Out of Stock</option>
                        <option value="Critical">Critical</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Supplier</label>
                    <select class="form-select" name="supplier">
                        <option value="">All Suppliers</option>
                        <?php 
                        $suppliers->data_seek(0);
                        while($s = $suppliers->fetch_assoc()): ?>
                        <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Classification</label>
                    <select class="form-select" name="classification">
                        <option value="">All Classifications</option>
                        <option value="Multibrand">Multibrand</option>
                        <option value="Yamaha 3S Only">Yamaha 3S Only</option>
                        <option value="General Supplies">General Supplies</option>
                        <option value="Office Supplies">Office Supplies</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel me-2"></i>Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="inventoryTable">
                    <thead class="table-light">
                        <tr>
                            <th>Item ID</th>
                            <th>Description</th>
                            <th>Supplier</th>
                            <th>Unit</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Classification</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $inventory->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['item_id'] ?></td>
                            <td><?= $row['description'] ?></td>
                            <td><?= $row['supplier_name'] ?? 'N/A' ?></td>
                            <td><?= $row['unit'] ?></td>
                            <td><?= $row['qty'] ?></td>
                            <td>₱<?= number_format($row['unit_price'], 2) ?></td>
                            <td>₱<?= number_format($row['amount'], 2) ?></td>
                            <td>
                                <span class="badge status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td><?= $row['classification'] ?></td>
                            <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editItemModal<?= $row['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                    <input type="hidden" name="delete_item" value="1">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editItemModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Item</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="update_item" value="1">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <input type="text" class="form-control" name="description" value="<?= $row['description'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Unit</label>
                                                <select class="form-select" name="unit" required>
                                                    <option value="REAM" <?= $row['unit'] == 'REAM' ? 'selected' : '' ?>>REAM</option>
                                                    <option value="PC" <?= $row['unit'] == 'PC' ? 'selected' : '' ?>>PC</option>
                                                    <option value="BOX" <?= $row['unit'] == 'BOX' ? 'selected' : '' ?>>BOX</option>
                                                    <option value="PAD" <?= $row['unit'] == 'PAD' ? 'selected' : '' ?>>PAD</option>
                                                    <option value="ROLL" <?= $row['unit'] == 'ROLL' ? 'selected' : '' ?>>ROLL</option>
                                                    <option value="BTL" <?= $row['unit'] == 'BTL' ? 'selected' : '' ?>>BTL</option>
                                                    <option value="SET" <?= $row['unit'] == 'SET' ? 'selected' : '' ?>>SET</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Quantity</label>
                                                <input type="number" class="form-control" name="qty" value="<?= $row['qty'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Unit Price</label>
                                                <input type="number" step="0.01" class="form-control" name="unit_price" value="<?= $row['unit_price'] ?>" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                        <?php if ($inventory->num_rows === 0): ?>
                        <tr><td colspan="11" class="text-center text-muted">No inventory items found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Item ID</label>
                        <input type="text" class="form-control" name="item_id" required placeholder="e.g., OFD-0001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select" name="supplier_id" required>
                            <?php 
                            $suppliers->data_seek(0);
                            while($s = $suppliers->fetch_assoc()): ?>
                            <option value="<?= $s['id'] ?>"><?= $s['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit</label>
                        <select class="form-select" name="unit" required>
                            <option value="REAM">REAM</option>
                            <option value="PC">PC</option>
                            <option value="BOX">BOX</option>
                            <option value="PAD">PAD</option>
                            <option value="ROLL">ROLL</option>
                            <option value="BTL">BTL</option>
                            <option value="SET">SET</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="qty" value="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit Price</label>
                        <input type="number" step="0.01" class="form-control" name="unit_price" value="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Classification</label>
                        <select class="form-select" name="classification" required>
                            <option value="General Supplies">General Supplies</option>
                            <option value="Office Supplies">Office Supplies</option>
                            <option value="Multibrand">Multibrand</option>
                            <option value="Yamaha 3S Only">Yamaha 3S Only</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
