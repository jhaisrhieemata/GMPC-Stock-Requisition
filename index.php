<?php
require_once 'config/db.php';

if (!isLoggedIn()) {
    redirect('login.php');
} elseif ($_SESSION['role'] === 'admin') {
    redirect('admin/index.php');
} else {
    redirect('client/index.php');
}
