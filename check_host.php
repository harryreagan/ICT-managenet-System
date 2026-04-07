<?php
// check_host.php
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "<br>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'not set') . "<br>";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'not set') . "<br>";
