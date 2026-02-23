<?php
require_once 'config/db.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

// Verify token
if (empty($token)) {
    $error = 'Invalid reset token';
} else {
    $stmt = $conn->prepare("SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        $error = 'Invalid or expired reset token';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please enter and confirm your new password';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        // Verify token again
        $stmt = $conn->prepare("SELECT * FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password and clear token
            $stmt = $conn->prepare("UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $user['id']);
            $stmt->execute();
            
            $success = 'Password reset successfully! <a href="login.php">Click here to login</a>';
        } else {
            $error = 'Invalid or expired reset token';
        }
    }
}

$pageTitle = 'Reset Password - GMPC Stock Requisition';
include 'includes/header.php';
?>

<div class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header bg-warning">
                        <i class="bi bi-key fs-1"></i>
                        <h3 class="mt-2">Reset Password</h3>
                        <p class="mb-0 opacity-75">Enter your new password</p>
                    </div>
                    <div class="p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <?php if (!$success && empty($error)): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="reset_password" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" name="password" required 
                                           placeholder="Enter new password" id="passwordInput" minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" name="confirm_password" required 
                                           placeholder="Confirm new password" id="confirmPasswordInput">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="bi bi-eye" id="eyeIconConfirm"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 py-2">
                                <i class="bi bi-check2-circle me-2"></i> Reset Password
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <div class="mt-4 text-center">
                            <a href="login.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left me-1"></i> Back to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword')?.addEventListener('click', function() {
    const passwordInput = document.getElementById('passwordInput');
    const eyeIcon = document.getElementById('eyeIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('bi-eye');
        eyeIcon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('bi-eye-slash');
        eyeIcon.classList.add('bi-eye');
    }
});

document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
    const passwordInput = document.getElementById('confirmPasswordInput');
    const eyeIcon = document.getElementById('eyeIconConfirm');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('bi-eye');
        eyeIcon.classList.add('bi-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('bi-eye-slash');
        eyeIcon.classList.add('bi-eye');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
