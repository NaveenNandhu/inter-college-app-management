<?php
$dirs = ['admin', 'coordinator', 'users', '.'];
$files = [];

foreach ($dirs as $dir) {
    if ($dir === '.') {
        $files[] = 'index.php';
        $files[] = 'auth.php';
        continue;
    }
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }
}

foreach ($files as $file) {
    if (strpos($file, 'qrcodes') !== false)
        continue;
    $content = file_get_contents($file);

    // Using regex to remove the option styles
    $content = preg_replace('/option\s*\{.*?\}/s', '', $content);
    $content = preg_replace('/option:checked\s*\{.*?\}/s', '', $content);
    $content = preg_replace('/option:hover\s*\{.*?\}/s', '', $content);

    // In case there was any leftover newlines
    $content = preg_replace("/\n\s*\n\s*\n/", "\n\n", $content);

    file_put_contents($file, $content);
}
echo "Removed option styles to rely on default OS light mode highlighting!\n";
?>