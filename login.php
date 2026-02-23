<?php
require_once 'config/db.php';

$error = '';
$success = '';
$showForgotModal = isset($_GET['forgot']) && $_GET['forgot'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = sanitize($conn, $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password';
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($user['branch_id']) {
                        $branchName = getBranchName($conn, $user['branch_id']);
                        $_SESSION['branch_id'] = $user['branch_id'];
                        $_SESSION['branch'] = $branchName;
                    } else {
                        $_SESSION['branch_id'] = null;
                        $_SESSION['branch'] = null;
                    }
                    
                    if ($user['role'] === 'admin') {
                        redirect('admin/index.php');
                    } else {
                        redirect('client/index.php');
                    }
                } else {
                    $error = 'Invalid password';
                }
            } else {
                $error = 'User not found';
            }
        }
    }
    
    // Handle forgot password
    if (isset($_POST['forgot_password'])) {
        $email = sanitize($conn, $_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address';
        } else {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database
                $stmt = $conn->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
                $stmt->bind_param("ssi", $token, $expires, $user['id']);
                $stmt->execute();
                
                // In production, send email here
                // For now, show the reset link
                $resetLink = "http://localhost/gmpc-requisition/reset-password.php?token=$token";
                $success = "Password reset link generated (for testing): <a href='$resetLink' target='_blank'>$resetLink</a>";
                
                // Simulate email sent
                // mail($email, "Password Reset Request", "Click here to reset: $resetLink");
            } else {
                $error = 'Email not found in our system';
            }
        }
    }
}

$pageTitle = 'Login - GMPC Stock Requisition';
?>
<?php include 'includes/header.php'; ?>

<style>
.password-toggle {
    cursor: pointer;
}
</style>

<div class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="login-header">
                        <i class="bi bi-box-seam fs-1"></i>
                        <h3 class="mt-2">GMPC Stock Requisition</h3>
                        <p class="mb-0 opacity-75">Sign in to your account</p>
                    </div>
                    <div class="p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="login" value="1">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" required 
                                           placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" name="password" required 
                                           placeholder="Enter your password" id="passwordInput">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3 text-end">
                                <a href="?forgot=1" class="text-decoration-none small">Forgot Password?</a>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                            </button>
                        </form>

                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                Demo Credentials:<br>
                                Admin: admin@gmpc.com / password123<br>
                                Branch: lagtang@gmpc.com / password123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<?php if ($showForgotModal): ?>
<div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <a href="login.php" class="btn-close"></a>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="forgot_password" value="1">
                    <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" required placeholder="Enter your email">
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="login.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Send Reset Link</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

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
</script>

<?php include 'includes/footer.php'; ?>
