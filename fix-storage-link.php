<?php

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/bootstrap/app.php';

$target = storage_path('app/public');
$link = public_path('storage');

header('Content-Type: text/plain');

echo "Attempting to create symlink...\n";
echo "Target: $target\n";
echo "Link: $link\n\n";

if (file_exists($link)) {
    echo "The [public/storage] directory already exists.\n";
    exit;
}

try {
    if (symlink($target, $link)) {
        echo "The [public/storage] directory has been linked.\n";
    } else {
        echo "Failed to create symlink using symlink() function.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
