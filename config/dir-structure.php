<?php
function listDirectoryStructure($dir) {
    $items = scandir($dir);
    echo "<ul>";
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;

        $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
        $encodedItem = htmlspecialchars($item); // Escape special HTML chars

        if (is_dir($fullPath)) {
            echo "<li>📁 <strong>$encodedItem</strong>";
            listDirectoryStructure($fullPath); // Recursive call
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

// Output as full HTML page
$startPath = __DIR__; // Or use another path

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Project Folder Structure</title>
    <style>
        body { font-family: Arial, sans-serif; }
        ul { list-style-type: none; margin-left: 20px; }
        li { margin: 4px 0; }
    </style>
</head>
<body>
<h1>Project Folder Structure</h1>";
echo "<p><code>$startPath</code></p>";

listDirectoryStructure($startPath);

echo "</body></html>";
?>
