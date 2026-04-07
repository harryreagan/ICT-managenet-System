<?php
$content = file_get_contents('portal/index.php');
$lines = explode("\n", $content);
$count = 0;
foreach ($lines as $i => $line) {
    $opens = substr_count($line, '<div');
    $closes = substr_count($line, '</div');
    $count += $opens;
    $count -= $closes;
    if ($count < 0) {
        echo "Negative count at line " . ($i + 1) . ": " . trim($line) . "\n";
        $count = 0; // Reset to continue searching
    }
}
echo "Final count: " . $count . "\n";
