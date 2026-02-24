<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gmpc_requisition');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Base URL for assets
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptDir === '\\' || $scriptDir === '/') $scriptDir = '';
define('BASE_URL', $protocol . '://' . $host . $scriptDir);

// CSRF Token
function csrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 86400);
    ini_set('session.gc_maxlifetime', 86400);
    session_start();
}

// Helper function to respond with JSON
function jsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Helper function to sanitize input
function sanitize($conn, $input) {
    if ($input === null) return '';
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Helper function to check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Generate requisition code
function generateRequisitionCode($conn) {
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM requisitions WHERE YEAR(created_at) = ?");
    $stmt->bind_param("s", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $num = ($row['count'] ?? 0) + 1;
    return 'REQ-' . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

// Generate special request code
function generateSpecialRequestCode($conn) {
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM special_requests WHERE YEAR(created_at) = ?");
    $stmt->bind_param("s", $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $num = ($row['count'] ?? 0) + 1;
    return 'SPR-' . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

// Generate inventory item ID
function generateItemId($conn) {
    $year = date('Y');
    $result = $conn->query("SELECT COUNT(*) as count FROM inventory WHERE YEAR(created_at) = $year");
    $row = $result->fetch_assoc();
    $num = ($row['count'] ?? 0) + 1;
    return 'ITEM-' . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 2, '.', ',');
}

// Get branch name from ID
function getBranchName($conn, $branchId) {
    $stmt = $conn->prepare("SELECT name FROM branches WHERE id = ?");
    $stmt->bind_param("i", $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['name'] ?? '';
}

// Get branch email
function getBranchEmail($branchName) {
    $emails = [
        'ADMIN' => 'gmpcpurchasing@gmail.com',
        'WAREHOUSE' => 'warehousegiant21@gmail.com',
        'LAGTANG' => 'giantmotoprolagtang@gmail.com',
        'V-RAMA' => 'gmpcguad_accounting@yahoo.com.ph',
        'BULACAO' => 'ivyhamistoso@gmail.com',
        'Y3S TALISAY' => 'gmpcyamaha3st@gmail.com',
        'MINGLANILLA' => 'giantmotoprolinao@gmail.com',
        'Y3S PARDO' => 'gmpcy3spardo@yahoo.com',
        'LAPU-LAPU' => 'giantmotoproopon19@gmail.com',
        'BACAYAN' => 'gmpcbacayan@gmail.com',
        'SAN FERNANDO MB' => 'gmpcsanfernando@gmail.com',
        'Y3S SAN FERNANDO' => 'sanfernandoyamaha3s@gmail.com',
        'LILOAN' => 'gmpctayud@gmail.com',
        'CORDOVA MB' => 'gmpccordova@gmail.com',
        'Y3S CORDOVA' => 'gmpccordovay3s@gmail.com',
        'CLARIN' => 'gmpcclarin@gmail.com',
        'TOLEDO' => 'giantmotopro_corporation@yahoo.com.ph',
        'UBAY' => 'gmpcubay@gmail.com',
        'CARMEN' => 'gmpccarmenbh@gmail.com',
        'TUBIGON' => 'gmpctubigon@gmail.com',
        'TALIBON' => 'gmpctalibon@gmail.com',
        'BOGO' => 'gmpcbogo@gmail.com',
        'BALAMBAN' => 'gmpcbalamban@gmail.com',
        'Y3S BARILI' => 'yamaha3sbarili@gmail.com',
        'BARILI MB' => 'giantmotoprobarilibranch@gmail.com',
        'SIERRA BULLONES' => 'gmpcsierra@gmail.com',
        'PITOGO' => 'gmpcpitogo@gmail.com',
        'TAYUD CONSOLACION' => 'gmpctayudconsolacion@gmail.com',
        'PINAMUNGAJAN' => 'gmpcpinamungajan22@gmail.com',
        'CANDIJAY' => 'giantcandijay@gmail.com',
        'YATI LILOAN' => 'gmpcyatililoan@gmail.com',
    ];
    $key = strtoupper(trim($branchName));
    return $emails[$key] ?? 'gmpcpurchasing@gmail.com';
}

// Pagination helper
function getPagination($page, $totalPages, $baseUrl) {
    $html = '';
    if ($totalPages > 1) {
        $html .= '<nav><ul class="pagination justify-content-center">';
        
        // Previous
        $prevDisabled = $page <= 1 ? 'disabled' : '';
        $prevUrl = $page > 1 ? $baseUrl . '?page=' . ($page - 1) : '#';
        $html .= '<li class="page-item ' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '">Previous</a></li>';
        
        // Pages
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = $i == $page ? 'active' : '';
            $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        }
        
        // Next
        $nextDisabled = $page >= $totalPages ? 'disabled' : '';
        $nextUrl = $page < $totalPages ? $baseUrl . '?page=' . ($page + 1) : '#';
        $html .= '<li class="page-item ' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '">Next</a></li>';
        
        $html .= '</ul></nav>';
    }
    return $html;
}

// Log activity
function logActivity($conn, $userId, $action, $details = '') {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt->bind_param("isss", $userId, $action, $details, $ip);
    $stmt->execute();
}

// Check if activity_log table exists, create if not
$result = $conn->query("SHOW TABLES LIKE 'activity_log'");
if ($result->num_rows === 0) {
    $conn->query("CREATE TABLE IF NOT EXISTS activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_created_at (created_at)
    )");
}
