<?php
require_once __DIR__ . '/../config/database.php';

// Read and execute the migration file
$migrationFile = __DIR__ . '/migrations/add_knowledge_base_features.sql';

if (!file_exists($migrationFile)) {
    die("Migration file not found: $migrationFile\n");
}

$sql = file_get_contents($migrationFile);

// Remove comments and split into statements
$sql = preg_replace('/--.*$/m', '', $sql); // Remove single-line comments
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // Remove multi-line comments

// Split by semicolon and filter empty statements
$statements = explode(';', $sql);
$statements = array_filter(array_map('trim', $statements), function ($stmt) {
    return !empty($stmt);
});

echo "Starting database migration...\n";
echo "Found " . count($statements) . " SQL statements to execute.\n\n";

$successCount = 0;
$errorCount = 0;

foreach ($statements as $index => $statement) {
    try {
        $pdo->exec($statement);
        $successCount++;

        // Extract table name for better logging
        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
            echo "✓ Created table: {$matches[1]}\n";
        } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
            echo "✓ Altered table: {$matches[1]}\n";
        } else {
            echo "✓ Executed statement #" . ($index + 1) . "\n";
        }
    } catch (PDOException $e) {
        // Check if error is "already exists" - treat as warning, not fatal error
        if (
            strpos($e->getMessage(), 'already exists') !== false ||
            strpos($e->getMessage(), 'Duplicate') !== false ||
            strpos($e->getMessage(), 'check that column') !== false
        ) {
            echo "⚠ Warning: " . $e->getMessage() . "\n";
        } else {
            $errorCount++;
            echo "✗ Error in statement #" . ($index + 1) . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "\n";
echo "========================================\n";
echo "Migration completed!\n";
echo "Successful: $successCount\n";
echo "Errors: $errorCount\n";
echo "========================================\n";
?>