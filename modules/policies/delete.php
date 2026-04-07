<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

requireLogin();

$id = $_POST['id'] ?? $_GET['id'] ?? null;

if (!$id) {
    $_SESSION['error'] = "Invalid document ID.";
    header("Location: index.php");
    exit;
}

try {
    // Check if document exists and get info for logging
    $stmt = $pdo->prepare("SELECT title, image_path FROM sop_documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch();

    if (!$doc) {
        $_SESSION['error'] = "Document not found.";
        header("Location: index.php");
        exit;
    }

    // Delete image if exists
    if ($doc['image_path'] && file_exists('../../' . $doc['image_path'])) {
        unlink('../../' . $doc['image_path']);
    }

    // Delete document
    $stmt = $pdo->prepare("DELETE FROM sop_documents WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = "Document '" . $doc['title'] . "' deleted successfully.";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting document: " . $e->getMessage();
}

header("Location: index.php");
exit;
?>