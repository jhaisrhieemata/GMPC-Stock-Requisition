<?php
require_once '../config/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$activePage = 'users';
$pageTitle = 'Users Management - GMPC Stock Requisition';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $email = sanitize($conn, $_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = sanitize($conn, $_POST['name']);
        $role = sanitize($conn, $_POST['role']);
        $branch_id = (int)$_POST['branch_id'];
        
        $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role, branch_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $email, $password, $full_name, $role, $branch_id);
        
        if ($stmt->execute()) {
            $success = "User added successfully";
        } else {
            $error = "Error adding user: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_user'])) {
        $id = (int)$_POST['id'];
        if ($id !== $_SESSION['user_id']) {
            $conn->query("DELETE FROM users WHERE id = $id");
            $success = "User deleted successfully";
        } else {
            $error = "You cannot delete your own account";
        }
    }
    
    if (isset($_POST['update_user'])) {
        $id = (int)$_POST['id'];
        $full_name = sanitize($conn, $_POST['name']);
        $role = sanitize($conn, $_POST['role']);
        $branch_id = (int)$_POST['branch_id'];
        
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name=?, role=?, branch_id=?, password=? WHERE id=?");
            $stmt->bind_param("ssssi", $full_name, $role, $branch_id, $password, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name=?, role=?, branch_id=? WHERE id=?");
            $stmt->bind_param("sssi", $full_name, $role, $branch_id, $id);
        }
        $stmt->execute();
        $success = "User updated successfully";
    }
}

// Get users with branch names
$users = $conn->query("SELECT u.*, b.name as branch_name FROM users u LEFT JOIN branches b ON u.branch_id = b.id ORDER BY u.role, u.full_name");

// Get branches for dropdown
$branches = $conn->query("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");

include '../includes/header.php';
include 'sidebar.php';
?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="bi bi-people me-2"></i>Users Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-plus-circle me-2"></i>Add User
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="card table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['full_name'] ?></td>
                            <td><?= $row['email'] ?></td>
                            <td>
                                <span class="badge bg-<?= $row['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                    <?= ucfirst(str_replace('_', ' ', $row['role'])) ?>
                                </span>
                            </td>
                            <td><?= $row['branch_name'] ?? 'N/A' ?></td>
                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $row['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirmDelete()">
                                    <input type="hidden" name="delete_user" value="1">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editUserModal<?= $row['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit User</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="update_user" value="1">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" class="form-control" name="name" value="<?= $row['full_name'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Role</label>
                                                <select class="form-select" name="role" required>
                                                    <option value="admin" <?= $row['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                    <option value="user" <?= $row['role'] == 'user' ? 'selected' : '' ?>>User</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Branch</label>
                                                <select class="form-select" name="branch_id">
                                                    <option value="0">No Branch</option>
                                                    <?php 
                                                    $branches->data_seek(0);
                                                    while($b = $branches->fetch_assoc()): ?>
                                                    <option value="<?= $b['id'] ?>" <?= $row['branch_id'] == $b['id'] ? 'selected' : '' ?>><?= $b['name'] ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">New Password (leave blank to keep current)</label>
                                                <input type="password" class="form-control" name="password" placeholder="Enter new password">
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
                        <?php if ($users->num_rows === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted">No users found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="admin">Admin</option>
                            <option value="user" selected>User</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Branch</label>
                        <select class="form-select" name="branch_id">
                            <option value="0">No Branch</option>
                            <?php 
                            $branches->data_seek(0);
                            while($b = $branches->fetch_assoc()): ?>
                            <option value="<?= $b['id'] ?>"><?= $b['name'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
