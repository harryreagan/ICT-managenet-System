<?php
$files = ['index.php', 'config/database.php', 'includes/functions.php', 'includes/auth.php', 'includes/header.php'];
foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        echo "$file: Could not read\n";
        continue;
    }
    if (substr($content, 0, 5) !== '<?php') {
        echo "$file: Starts with " . bin2hex(substr($content, 0, 5)) . "\n";
    } else {
        echo "$file: OK (starts with <?php)\n";
    }
    // Check for closing tag and trailing content
    $last_pos = strrpos($content, '?>');
    if ($last_pos !== false && $last_pos < strlen($content) - 2) {
        $trailing = substr($content, $last_pos + 2);
        if (trim($trailing) !== '') {
            echo "$file: Trailing content after ?>: [" . bin2hex($trailing) . "]\n";
        }
    }
}
