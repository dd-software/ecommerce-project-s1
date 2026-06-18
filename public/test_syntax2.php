<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== RECURSIVE PHP LINT ===\n";

function scanDirRecursive($dir, &$results = []) {
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $results[] = $path;
            }
        } else if ($value != "." && $value != "..") {
            scanDirRecursive($path, $results);
        }
    }
    return $results;
}

$root = dirname(__DIR__);
$allFiles = scanDirRecursive($root);

echo "Found " . count($allFiles) . " PHP files to check.\n\n";

$failedCount = 0;
foreach ($allFiles as $file) {
    // Run php -l
    $output = [];
    $retval = -1;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $retval);

    if ($retval !== 0) {
        $failedCount++;
        echo "FAILED: " . str_replace($root, '', $file) . "\n";
        echo implode("\n", $output) . "\n";
        echo "--------------------------------------------------\n";
    }
}

echo "\nLint complete. Total failures: $failedCount\n";
