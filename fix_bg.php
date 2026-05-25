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
    // `#` and `[` are not word characters, so \b fails.
    $content = str_replace('bg-[#0a0f1c]', 'bg-slate-50', $content);
    $content = str_replace('bg-gray-100', 'bg-slate-50', $content);
    // Double borders sometimes happen, fix them
    $content = str_replace('border border-slate-200 border border-slate-200', 'border border-slate-200', $content);
    $content = str_replace('border-t border-slate-200 border-t border-slate-200', 'border-t border-slate-200', $content);
    // Also change any remnant text-gray-100 that was inside tags that didn't have class="..."
    // Wait, let's just do a blanket replace for text-gray-100 if it's still there.

    // In index.php, "About the Project" section has bg-white shadow-md border-r
    // Let's remove border-r if it looks weird.

    file_put_contents($file, $content);
}
echo "Fixed body bg logic.\n";
?>