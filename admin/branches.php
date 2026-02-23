<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'branches';
$pageTitle = 'Branches Management - GMPC Stock Requisition';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_branch'])) {
        $name = sanitize($conn, $_POST['name']);
        $email = sanitize($conn, $_POST['email']);
        $classification = sanitize($conn, $_POST['classification']);
        
        $stmt = $conn->prepare("INSERT INTO branches (name, email, classification) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $classification);
        
        if ($stmt->execute()) {
            $success = "Branch added successfully";
        } else {
            $error = "Error adding branch: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_branch'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM branches WHERE id = $id");
        $success = "Branch deleted successfully";
    }
    
    if (isset($_POST['update_branch'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($conn, $_POST['name']);
        $email = sanitize($conn, $_POST['email']);
        $classification = sanitize($conn, $_POST['classification']);
        
        $stmt = $conn->prepare("UPDATE branches SET name=?, email=?, classification=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $classification, $id);
        $stmt->execute();
        $success = "Branch updated successfully";
    }
}

// Get branches
$branches = $conn->query("SELECT * FROM branches WHERE is_active = 1 ORDER BY name");

include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-building me-2"></i>Branches Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBranchModal">
            <i class="bi bi-plus-circle me-2"></i>Add Branch
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Branches Grid -->
    <div class="row">
        <?php while ($row = $branches->fetch_assoc()): 
            $userCount = $conn->query("SELECT COUNT(*) as count FROM users WHERE branch_id = {$row['id']}")->fetch_assoc()['count'];
            $reqCount = $conn->query("SELECT COUNT(*) as count FROM requisitions WHERE branch_id = {$row['id']}")->fetch_assoc()['count'];
        ?>
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-transparent">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= $row['name'] ?></h5>
                        <span class="badge bg-<?= $row['classification'] == 'Yamaha 3S' ? 'warning' : 'primary' ?>">
                            <?= $row['classification'] ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-2"><i class="bi bi-envelope me-2"></i><?= $row['email'] ?></p>
                    <p class="mb-2"><i class="bi bi-people me-2"></i><?= $userCount ?> Users</p>
                    <p class="mb-0"><i class="bi bi-file-earmark-text me-2"></i><?= $reqCount ?> Requisitions</p>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editBranchModal<?= $row['id'] ?>">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </button>
                    <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                        <input type="hidden" name="delete_branch" value="1">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Edit Modal -->
        <div class="modal fade" id="editBranchModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Branch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="update_branch" value="1">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Branch Name</label>
                                <input type="text" class="form-control" name="name" value="<?= $row['name'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?= $row['email'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Classification</label>
                                <select class="form-select" name="classification" required>
                                    <option value="Multibrand" <?= $row['classification'] == 'Multibrand' ? 'selected' : '' ?>>Multibrand</option>
                                    <option value="Yamaha 3S" <?= $row['classification'] == 'Yamaha 3S' ? 'selected' : '' ?>>Yamaha 3S</option>
                                    <option value="Parts & Accessories" <?= $row['classification'] == 'Parts & Accessories' ? 'selected' : '' ?>>Parts & Accessories</option>
                                    <option value="Service Center" <?= $row['classification'] == 'Service Center' ? 'selected' : '' ?>>Service Center</option>
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
        <?php if ($branches->num_rows === 0): ?>
        <div class="col-12 text-center text-muted py-5">No branches found</div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Branch Modal -->
<div class="modal fade" id="addBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Branch Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Classification</label>
                        <select class="form-select" name="classification" required>
                            <option value="Multibrand">Multibrand</option>
                            <option value="Yamaha 3S">Yamaha 3S</option>
                            <option value="Parts & Accessories">Parts & Accessories</option>
                            <option value="Service Center">Service Center</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_branch" class="btn btn-primary">Add Branch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
