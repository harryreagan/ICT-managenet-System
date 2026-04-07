<?php
$content = file_get_contents('portal/index.php');
$openDivs = substr_count($content, '<div');
$closeDivs = substr_count($content, '</div');
echo "DIV: $openDivs / $closeDivs\n";
