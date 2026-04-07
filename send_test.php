<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$subject = "Manual Test Email";
$body = "This is a direct test email sent via the trigger script to verify ict@dallas.ke connectivity.";

echo "<h1>Sending test email to ict@dallas.ke...</h1>";
sendICTEmail($subject, $body);

echo "<p>The request was sent to the server's mail agent. If the server is configured correctly, it should arrive shortly.</p>";
echo "<a href='test_mail.php'>Check Mail Configuration Diagnostic</a>";
?>