<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP LINT TEST ===\n";

$files = [
    __DIR__ . '/index.php',
    __DIR__ . '/diagnostic.php',
    dirname(__DIR__) . '/config/database.php',
    dirname(__DIR__) . '/config/app.php',
    dirname(__DIR__) . '/src/Core/Request.php',
    dirname(__DIR__) . '/src/Core/Router.php',
    dirname(__DIR__) . '/src/Core/Database.php',
    dirname(__DIR__) . '/src/Core/Autoloader.php',
];

foreach ($files as $file) {
    echo "Checking " . basename($file) . "... ";
    if (!file_exists($file)) {
        echo "FILE NOT FOUND!\n";
        continue;
    }

    // Run php -l
    $output = [];
    $retval = -1;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $retval);

    if ($retval === 0) {
        echo "OK\n";
    } else {
        echo "FAILED (code $retval):\n";
        echo implode("\n", $output) . "\n";
    }
    echo "--------------------------------------------------\n";
}
