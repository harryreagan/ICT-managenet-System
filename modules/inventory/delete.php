<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();
if (!isAdmin() && $_SESSION['role'] !== 'technician')
    redirect('index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM inventory_items WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Item removed from inventory.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}

redirect('/ict/modules/inventory/index.php');
