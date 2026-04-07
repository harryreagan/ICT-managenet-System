<?php
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header("Location: $url");
    exit;
}

/**
 * Scans the database for critical events and generates notifications
 */
function checkAlarms($pdo)
{
    // Throttle check to run once every 15 minutes
    $lastCheck = get_setting($pdo, 'last_alarm_check', 0);
    if (time() - (int) $lastCheck < 900) { // 15 minutes
        return;
    }

    // 1. Check for Low Stock
    $stmt = $pdo->query("SELECT id, name FROM inventory_items WHERE stock_level <= reorder_threshold AND status != 'out_of_stock'");
    while ($item = $stmt->fetch()) {
        $msg = "Low stock alert: " . $item['name'];
        createNotification($pdo, $msg, 'warning', '/ict/modules/inventory/index.php', 'admin');
    }

    // 2. Check for Expiring Warranties (within 30 days)
    $stmt = $pdo->query("SELECT id, name FROM hardware_assets WHERE warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    while ($asset = $stmt->fetch()) {
        $msg = "Warranty expiring soon for: " . $asset['name'];
        createNotification($pdo, $msg, 'info', '/ict/modules/hardware/index.php', 'admin');
    }

    // 3. Check for Due Renewals (within 7 days)
    $stmt = $pdo->query("SELECT id, service_name FROM renewals WHERE renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'active'");
    while ($ren = $stmt->fetch()) {
        $msg = "Renewal due in 7 days: " . $ren['service_name'];
        createNotification($pdo, $msg, 'alert', '/ict/modules/renewals/index.php', 'admin');
    }

    set_setting($pdo, 'last_alarm_check', time());
}

function createNotification($pdo, $message, $type = 'info', $link = null, $target_role = 'all', $target_user_id = null)
{
    // Check if column exists (using a static variable to avoid multiple queries)
    static $hasTargetColumn = null;
    if ($hasTargetColumn === null) {
        try {
            $pdo->query("SELECT target_user_id FROM notifications LIMIT 1");
            $hasTargetColumn = true;
        } catch (Exception $e) {
            $hasTargetColumn = false;
        }
    }

    // Prevent duplicate unread notifications for the same message/user combination
    $query = "SELECT id FROM notifications WHERE message = ? AND is_read = 0";
    $params = [$message];

    if ($target_user_id && $hasTargetColumn) {
        $query .= " AND target_user_id = ?";
        $params[] = $target_user_id;
    } else {
        if ($hasTargetColumn) {
            $query .= " AND target_user_id IS NULL";
        }
        $query .= " AND target_role = ?";
        $params[] = $target_role;
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        if ($stmt->fetch())
            return;

        if ($hasTargetColumn) {
            $stmt = $pdo->prepare("INSERT INTO notifications (message, type, link_url, target_role, target_user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$message, $type, $link, $target_role, $target_user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO notifications (message, type, link_url, target_role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$message, $type, $link, $target_role]);
        }
    } catch (Exception $e) {
        // Fallback for unexpected issues
        return;
    }
}

function formatDate($date)
{
    return date('M d, Y', strtotime($date));
}

function timeAgo($timestamp)
{
    $time = strtotime($timestamp);
    $diff = time() - $time;

    if ($diff < 60)
        return "Just now";
    if ($diff < 3600)
        return floor($diff / 60) . "m ago";
    if ($diff < 86400)
        return floor($diff / 3600) . "h ago";
    return floor($diff / 86400) . "d ago";
}

function formatCurrency($amount)
{
    return 'KES ' . number_format($amount, 2);
}

function getStatusBadgeClass($status)
{
    return match ($status) {
        'active', 'working', 'completed', 'resolved', 'closed' => 'bg-green-100 text-green-800',
        'expired', 'faulty', 'critical' => 'bg-red-100 text-red-800',
        'needs_service', 'pending', 'open', 'high' => 'bg-yellow-100 text-yellow-800',
        'in_progress', 'medium' => 'bg-primary-100 text-primary-600',
        default => 'bg-slate-100 text-slate-800'
    };
}

function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $w = floor($diff->d / 7);
    $d = $diff->d % 7;

    $units = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    $values = array(
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => (int) $w,
        'd' => (int) $d,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    );

    $result = array();
    foreach ($units as $k => $v) {
        if ($values[$k]) {
            $result[] = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
        }
    }

    if (!$full)
        $result = array_slice($result, 0, 1);
    return $result ? implode(', ', $result) . ' ago' : 'just now';
}

function isActive($patterns)
{
    if (!is_array($patterns)) {
        $patterns = [$patterns];
    }

    $match = false;
    foreach ($patterns as $pattern) {
        if (strpos($_SERVER['PHP_SELF'], $pattern) !== false) {
            $match = true;
            break;
        }
    }

    return $match
        ? 'bg-primary-500/10 text-primary-500 border-l-2 border-primary-500 font-bold'
        : 'text-slate-400 hover:bg-slate-800 hover:text-white border-l-2 border-transparent';
}

function logActivity($pdo, $userId, $action, $details = null)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $action, $details]);
    } catch (PDOException $e) {
        // Silently fail or handle as needed
    }
}

/**
 * Retrieve a system setting from the database
 */
function get_setting($pdo, $key, $default = null)
{
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function set_setting($pdo, $key, $value)
{
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    } catch (PDOException $e) {
        // Create table if missing
        if ($e->getCode() == '42S02') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
    }
}

/**
 * Send an email notification to the ICT team
 */
function sendICTEmail($subject, $body)
{
    $config_file = __DIR__ . '/../config/mail_config.php';
    $client_file = __DIR__ . '/smtp_client.php';

    // Check if SMTP configuration exists and is customized
    if (file_exists($config_file) && file_exists($client_file)) {
        require_once $config_file;
        require_once $client_file;

        // Only use SMTP if it's not the default placeholder
        if (SMTP_PASS !== 'your-app-password') {
            $smtp = new SMTPClient(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
            $success = $smtp->send('ict@dallas.ke', $subject, $body, MAIL_FROM_NAME, MAIL_FROM_EMAIL);

            if ($success)
                return true;
            // If SMTP fails, it will fall through to native mail() as backup
        }
    }

    // Native mail() fallback (requires server-side config)
    $to = 'ict@dallas.ke';
    $headers = "From: ICT System <noreply@dallas.ke>\r\n";
    $headers .= "Reply-To: noreply@dallas.ke\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // Add a signature
    $body .= "\n\n---\nAutomated Notification from ICT Management System\n" . date('Y-m-d H:i:s');

    @mail($to, $subject, $body, $headers);
}

function isUserOnDuty($pdo)
{
    if (!isset($_SESSION['user_id']))
        return false;
    try {
        $stmt = $pdo->prepare("SELECT is_on_duty FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}
?>