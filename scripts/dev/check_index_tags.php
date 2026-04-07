<?php
$content = file_get_contents('index.php');
$divOpen = substr_count($content, '<div');
$divClose = substr_count($content, '</div>');
echo "DIV: $divOpen / $divClose\n";

$aOpen = substr_count($content, '<a ');
$aClose = substr_count($content, '</a>');
echo "A: $aOpen / $aClose\n";

$formOpen = substr_count($content, '<form');
$formClose = substr_count($content, '</form>');
echo "FORM: $formOpen / $formClose\n";
