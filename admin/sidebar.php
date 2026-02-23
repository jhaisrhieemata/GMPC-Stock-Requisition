<div class="sidebar">
    <div class="text-center py-4 border-bottom border-secondary">
        <i class="bi bi-box-seam fs-2"></i>
        <h5 class="mt-2 mb-1">GMPC Admin</h5>
        <small class="text-white-50">Stock Requisition System</small>
    </div>
    
    <ul class="nav flex-column py-3">
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'inventory' ? 'active' : '' ?>" href="inventory.php">
                <i class="bi bi-boxes me-2"></i> Inventory
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'suppliers' ? 'active' : '' ?>" href="suppliers.php">
                <i class="bi bi-truck me-2"></i> Suppliers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'branches' ? 'active' : '' ?>" href="branches.php">
                <i class="bi bi-building me-2"></i> Branches
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'requisitions' ? 'active' : '' ?>" href="requisitions.php">
                <i class="bi bi-file-earmark-text me-2"></i> Requisitions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'special-requests' ? 'active' : '' ?>" href="special-requests.php">
                <i class="bi bi-exclamation-triangle me-2"></i> Special Requests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>" href="users.php">
                <i class="bi bi-people me-2"></i> Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'reports' ? 'active' : '' ?>" href="reports.php">
                <i class="bi bi-bar-chart me-2"></i> Reports
            </a>
        </li>
    </ul>
    
    <div class="position-absolute bottom-0 w-100 p-3 border-top border-secondary">
        <div class="d-flex align-items-center mb-3 px-2">
            <i class="bi bi-person-circle fs-4"></i>
            <div class="ms-2">
                <div class="fw-medium"><?= $_SESSION['name'] ?></div>
                <small class="text-white-50"><?= ucfirst($_SESSION['role']) ?></small>
            </div>
        </div>
        <a href="../logout.php" class="btn btn-outline-light w-100">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>
