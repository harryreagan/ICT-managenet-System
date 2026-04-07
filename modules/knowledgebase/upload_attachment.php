<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$issue_id = $_POST['issue_id'] ?? null;

if (!$issue_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Issue ID is required']);
    exit;
}

// Verify issue exists
$stmt = $pdo->prepare("SELECT id FROM troubleshooting_logs WHERE id = ?");
$stmt->execute([$issue_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Issue not found']);
    exit;
}

// Check if files were uploaded
if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit;
}

$upload_dir = '../../uploads/kb/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$max_file_size = 10 * 1024 * 1024; // 10MB

$uploaded_files = [];
$errors = [];

// Handle multiple files
$file_count = count($_FILES['files']['name']);

for ($i = 0; $i < $file_count; $i++) {
    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['files']['name'][$i];
        $file_tmp = $_FILES['files']['tmp_name'][$i];
        $file_size = $_FILES['files']['size'][$i];
        $file_type = $_FILES['files']['type'][$i];

        // Validate file type
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "$file_name: Invalid file type";
            continue;
        }

        // Validate file size
        if ($file_size > $max_file_size) {
            $errors[] = "$file_name: File too large (max 10MB)";
            continue;
        }

        // Generate unique filename
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_name = uniqid('kb_' . $issue_id . '_') . '.' . $file_extension;
        $file_path = 'uploads/kb/' . $unique_name;
        $full_path = $upload_dir . $unique_name;

        // Move uploaded file
        if (move_uploaded_file($file_tmp, $full_path)) {
            // Save to database
            try {
                $stmt = $pdo->prepare("INSERT INTO issue_attachments (issue_id, file_name, file_path, file_type, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$issue_id, $file_name, $file_path, $file_type, $file_size, $_SESSION['user_id']]);

                $uploaded_files[] = [
                    'id' => $pdo->lastInsertId(),
                    'file_name' => $file_name,
                    'file_path' => $file_path,
                    'file_type' => $file_type,
                    'file_size' => $file_size
                ];

                // Log activity
                $activityStmt = $pdo->prepare("INSERT INTO issue_activity (issue_id, user_id, activity_type, description) VALUES (?, ?, 'attachment_added', ?)");
                $activityStmt->execute([$issue_id, $_SESSION['user_id'], "Uploaded file: $file_name"]);

            } catch (PDOException $e) {
                $errors[] = "$file_name: Database error";
                unlink($full_path); // Remove file if database insert fails
            }
        } else {
            $errors[] = "$file_name: Failed to upload";
        }
    } else {
        $errors[] = $_FILES['files']['name'][$i] . ": Upload error";
    }
}

if (!empty($uploaded_files)) {
    echo json_encode([
        'success' => true,
        'message' => count($uploaded_files) . ' file(s) uploaded successfully',
        'files' => $uploaded_files,
        'errors' => $errors
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No files were uploaded',
        'errors' => $errors
    ]);
}
?>