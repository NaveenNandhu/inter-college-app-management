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

$old_option_style = <<<CSS
        option {
            background-color: #ffffff !important;
            color: #1e293b !important;
        }
CSS;

$new_option_style = <<<CSS
        option {
            background-color: #ffffff !important;
            color: #1e293b !important;
        }
        option:checked {
            background-color: #6366f1 !important;
            color: #ffffff !important;
        }
        option:hover {
            background-color: #f1f5f9 !important;
        }
CSS;

// Let's make it robust against slightly different whitespaces
foreach ($files as $file) {
    if (strpos($file, 'qrcodes') !== false)
        continue;
    $content = file_get_contents($file);

    // Use regex to locate option block and replace it
    $content = preg_replace('/option\s*\{\s*background-color:\s*#ffffff\s*!important;\s*color:\s*#1e293b\s*!important;\s*\}/s', $new_option_style, $content);

    file_put_contents($file, $content);
}
echo "Option selected styles fixed!\n";
?>