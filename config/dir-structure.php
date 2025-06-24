<?php
function listDirectoryStructure($dir, $excludeDirs = []) {
    $items = scandir($dir);
    echo "<ul>";
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        $encodedItem = htmlspecialchars($item); // HTML safe

        if (is_dir($fullPath)) {
            // Skip excluded directories
            if (in_array($item, $excludeDirs)) continue;

            echo "<li>📁 <strong>$encodedItem</strong>";
            listDirectoryStructure($fullPath, $excludeDirs);
            echo "</li>";
        } else {
            $ext = pathinfo($item, PATHINFO_EXTENSION);
            if (in_array($ext, ['php', 'css', 'js', 'html', 'json'])) {
                echo "<li>📄 $encodedItem</li>";
            }
        }
    }
    echo "</ul>";
}

// Exclude folders like 'vendor', 'node_modules', etc.
$exclude = ['vendors', 'node_modules', 'storage', '.git', 'demo_3','demo_1','demo_2','demo_5','maps'];

$startPath = __DIR__;

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Project Folder Structure</title>
    <style>
        body { font-family: Arial, sans-serif; }
        ul { list-style-type: none; margin-left: 20px; padding-left: 10px; border-left: 1px dotted #ccc; }
        li { margin: 4px 0; }
    </style>
</head>
<body>
<h1>Project Folder Structure</h1>
<p><code>$startPath</code></p>";

listDirectoryStructure($startPath, $exclude);

echo "</body></html>";
?>
