<div class="sidebar">
    <div class="text-center py-4 border-bottom border-secondary">
        <i class="bi bi-box-seam fs-2"></i>
        <h5 class="mt-2 mb-1">GMPC Branch</h5>
        <small class="text-white-50"><?= $_SESSION['branch'] ?? 'Portal' ?></small>
    </div>
    
    <ul class="nav flex-column py-3">
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="index.php">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'new-requisition' ? 'active' : '' ?>" href="new-requisition.php">
                <i class="bi bi-plus-circle me-2"></i> New Requisition
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'my-requests' ? 'active' : '' ?>" href="my-requests.php">
                <i class="bi bi-file-earmark-text me-2"></i> My Requests
            </a>
        </li>
    </ul>
    
    <div class="position-absolute bottom-0 w-100 p-3 border-top border-secondary">
        <div class="d-flex align-items-center mb-3 px-2">
            <i class="bi bi-person-circle fs-4"></i>
            <div class="ms-2">
                <div class="fw-medium"><?= $_SESSION['name'] ?></div>
                <small class="text-white-50"><?= $_SESSION['branch'] ?></small>
            </div>
        </div>
        <a href="../logout.php" class="btn btn-outline-light w-100">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </div>
</div>
