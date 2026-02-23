<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'suppliers';
$pageTitle = 'Suppliers Management - GMPC Stock Requisition';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_supplier'])) {
        $name = sanitize($conn, $_POST['name']);
        $contact_person = sanitize($conn, $_POST['contact_person']);
        $phone = sanitize($conn, $_POST['phone']);
        $email = sanitize($conn, $_POST['email']);
        $address = sanitize($conn, $_POST['address']);
        $classification = sanitize($conn, $_POST['classification']);
        
        $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, classification) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $contact_person, $phone, $email, $address, $classification);
        
        if ($stmt->execute()) {
            $success = "Supplier added successfully";
        } else {
            $error = "Error adding supplier: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_supplier'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM suppliers WHERE id = $id");
        $success = "Supplier deleted successfully";
    }
    
    if (isset($_POST['update_supplier'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($conn, $_POST['name']);
        $contact_person = sanitize($conn, $_POST['contact_person']);
        $phone = sanitize($conn, $_POST['phone']);
        $email = sanitize($conn, $_POST['email']);
        $address = sanitize($conn, $_POST['address']);
        $classification = sanitize($conn, $_POST['classification']);
        
        $stmt = $conn->prepare("UPDATE suppliers SET name=?, contact_person=?, phone=?, email=?, address=?, classification=? WHERE id=?");
        $stmt->bind_param("ssssssi", $name, $contact_person, $phone, $email, $address, $classification, $id);
        $stmt->execute();
        $success = "Supplier updated successfully";
    }
}

// Get suppliers
$suppliers = $conn->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name");

include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-truck me-2"></i>Suppliers Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="bi bi-plus-circle me-2"></i>Add Supplier
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Suppliers Table -->
    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Classification</th>
                            <th>Items</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $suppliers->fetch_assoc()): 
                            $itemCount = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE supplier_id = {$row['id']}")->fetch_assoc()['count'];
                        ?>
                        <tr>
                            <td><?= $row['name'] ?></td>
                            <td><?= $row['contact_person'] ?? 'N/A' ?></td>
                            <td><?= $row['phone'] ?? 'N/A' ?></td>
                            <td><?= $row['email'] ?? 'N/A' ?></td>
                            <td>
                                <span class="badge bg-info"><?= $row['classification'] ?></span>
                            </td>
                            <td><?= $itemCount ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?= $row['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                    <input type="hidden" name="delete_supplier" value="1">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editSupplierModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Supplier</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="update_supplier" value="1">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" class="form-control" name="name" value="<?= $row['name'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Contact Person</label>
                                                <input type="text" class="form-control" name="contact_person" value="<?= $row['contact_person'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Phone</label>
                                                <input type="text" class="form-control" name="phone" value="<?= $row['phone'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" class="form-control" name="email" value="<?= $row['email'] ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea class="form-control" name="address" rows="2"><?= $row['address'] ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Classification</label>
                                                <select class="form-select" name="classification" required>
                                                    <option value="General Supplies" <?= $row['classification'] == 'General Supplies' ? 'selected' : '' ?>>General Supplies</option>
                                                    <option value="Office Supplies" <?= $row['classification'] == 'Office Supplies' ? 'selected' : '' ?>>Office Supplies</option>
                                                    <option value="Multibrand" <?= $row['classification'] == 'Multibrand' ? 'selected' : '' ?>>Multibrand</option>
                                                    <option value="Yamaha 3S Only" <?= $row['classification'] == 'Yamaha 3S Only' ? 'selected' : '' ?>>Yamaha 3S Only</option>
                                                </select>
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
                        <?php if ($suppliers->num_rows === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted">No suppliers found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" class="form-control" name="contact_person">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2"></textarea>
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
                    <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
