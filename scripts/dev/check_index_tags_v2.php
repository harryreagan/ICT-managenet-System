<?php
$content = file_get_contents('index.php');
preg_match_all('/<div(?:\s|>)/i', $content, $matchesOpen);
preg_match_all('/<\/div>/i', $content, $matchesClose);
echo "DIV: " . count($matchesOpen[0]) . " / " . count($matchesClose[0]) . "\n";

preg_match_all('/<a(?:\s|>)/i', $content, $matchesOpenA);
preg_match_all('/<\/a>/i', $content, $matchesCloseA);
echo "A: " . count($matchesOpenA[0]) . " / " . count($matchesCloseA[0]) . "\n";

preg_match_all('/<form(?:\s|>)/i', $content, $matchesOpenForm);
preg_match_all('/<\/form>/i', $content, $matchesCloseForm);
echo "FORM: " . count($matchesOpenForm[0]) . " / " . count($matchesCloseForm[0]) . "\n";
