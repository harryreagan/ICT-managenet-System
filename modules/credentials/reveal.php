<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/encryption.php';

requireLogin();

header('Content-Type: application/json');

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT encrypted_password, user_id FROM credential_vault WHERE id = ?");
    $stmt->execute([$id]);
    $cred = $stmt->fetch();

    if ($cred) {
        // Access Check: NULL (shared) or matches current user (personal)
        if ($cred['user_id'] !== null && $cred['user_id'] != $_SESSION['user_id']) {
            echo json_encode(['error' => 'Permission denied. This is a personal secret.']);
            exit;
        }

        $decrypted = decryptData($cred['encrypted_password']);

        // Log the access
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
        $logStmt->execute([$_SESSION['user_id'], 'REVEAL_PASSWORD', "Revealed password for Credential ID: $id"]);

        echo json_encode(['password' => $decrypted]);
    } else {
        echo json_encode(['error' => 'Credential not found']);
    }

} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}
?>