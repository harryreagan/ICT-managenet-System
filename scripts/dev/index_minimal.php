<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

requireLogin();

if ($_SESSION['role'] === 'staff') {
    header("Location: /ict/portal/index.php");
    exit();
}

checkAlarms($pdo);

include 'includes/header.php';
?>
<div class="p-6">
    <h1 class="text-2xl font-bold">Dashboard Safety Mode</h1>
    <p>If you see this, the core bootstrap and header are working.</p>
    <a href="index_original.php" class="text-blue-500 underline">Try loading original index</a>
</div>
<?php
include 'includes/footer.php';
?>