<?php
function listDirectoryStructure($dir, $prefix = '') {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($fullPath)) {
            echo $prefix . "📁 " . $item . PHP_EOL;
            listDirectoryStructure($fullPath, $prefix . "│   ");
        } else {
            $ext = pathinfo($item, PATHINFO_EXTENSION);
            if (in_array($ext, ['php', 'css', 'js', 'html', 'json'])) {
                echo $prefix . "├── " . $item . PHP_EOL;
            }
        }
    }
}

// Usage: Start from current directory
$startPath = __DIR__; // or change to any base path
echo "Project Folder Structure (starting from $startPath):\n\n";
listDirectoryStructure($startPath);
