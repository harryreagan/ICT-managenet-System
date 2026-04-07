<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>ICT Email Diagnostic Tool v2</h1>";

$to = 'ict@dallas.ke';
$subject = "Diagnostic Test: " . date('Y-m-d H:i:s');
$message = "This is a test message to verify if the ICT email system is working.";
$headers = "From: ICT System <noreply@dallas.ke>\r\n";

echo "<h2>Section 1: Native Server Check (PHP mail)</h2>";
echo "<ul>";
echo "<li><strong>Native Function:</strong> " . (function_exists('mail') ? "<span style='color:green'>Exists</span>" : "<span style='color:red'>Disabled</span>") . "</li>";
echo "<li><strong>php.ini SMTP:</strong> " . ini_get('SMTP') . "</li>";
echo "<li><strong>php.ini Port:</strong> " . ini_get('smtp_port') . "</li>";
echo "</ul>";

echo "<h2>Section 2: Custom SMTP Client Check</h2>";
$config_file = 'config/mail_config.php';
if (file_exists($config_file)) {
    require_once $config_file;
    echo "<ul>";
    echo "<li><strong>Config Found:</strong> <span style='color:green'>Yes</span></li>";
    echo "<li><strong>SMTP Host:</strong> " . SMTP_HOST . "</li>";
    echo "<li><strong>SMTP Port:</strong> " . SMTP_PORT . "</li>";
    echo "<li><strong>SMTP User:</strong> " . SMTP_USER . "</li>";
    echo "<li><strong>Password Set:</strong> " . (SMTP_PASS !== 'your-app-password' ? "<span style='color:green'>Yes</span>" : "<span style='color:red'>No (Using placeholder)</span>") . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color:red'><strong>Error:</strong> $config_file not found.</p>";
}

echo "<h2>Section 3: Delivery Test</h2>";
if (isset($_GET['run'])) {
    echo "<p>Running full delivery test using <code>sendICTEmail()</code>...</p>";

    // Attempting send
    // Note: sendICTEmail has @ suppression, so we'll check return or logic
    $result = sendICTEmail($subject, "This is a test from the Diagnostic Tool.\nTimestamp: " . date('Y-m-d H:i:s'));

    echo "<div style='padding:15px; background:#f0f0f0; border-radius:8px;'>";
    echo "<strong>Result:</strong> The system has processed the request.<br>";
    echo "If you configured SMTP correctly in Step 2, the email should arrive. If Step 2 shows 'Placeholder', it likely failed via native mail().";
    echo "</div>";
} else {
    echo "<a href='?run=1' style='display:inline-block; padding:10px 20px; background:#3b82f6; color:white; border-radius:6px; text-decoration:none; font-weight:bold;'>Run Delivery Test</a>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If <strong>'Password Set'</strong> is <strong>No</strong>, you MUST edit <code>config/mail_config.php</code> with your real email credentials.</li>";
echo "<li>If using Gmail, ensure you created an <strong>'App Password'</strong> (not your regular password).</li>";
echo "</ol>";
?>