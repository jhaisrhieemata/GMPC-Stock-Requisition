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

// Start session
if (session_status() === PHP_SESSION_NONE) {
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
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
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
    $result = $conn->query("SELECT COUNT(*) as count FROM requisitions WHERE YEAR(created_at) = $year");
    $row = $result->fetch_assoc();
    $num = $row['count'] + 1;
    return 'REQ-' . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

// Generate special request code
function generateSpecialRequestCode($conn) {
    $year = date('Y');
    $result = $conn->query("SELECT COUNT(*) as count FROM special_requests WHERE YEAR(created_at) = $year");
    $row = $result->fetch_assoc();
    $num = $row['count'] + 1;
    return 'SPR-' . $year . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 2, '.', ',');
}

// Get branch name from ID
function getBranchName($conn, $branchId) {
    $result = $conn->query("SELECT name FROM branches WHERE id = $branchId");
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
