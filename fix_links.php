<?php
$dirs = ['admin', 'coordinator', 'users'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                // replace 'location: login.php' to 'location: ../auth.php?action=login'
                $content = preg_replace('/header\s*\(\s*[\"\'\']location:\s*login\.php[\"\'\']\s*\)/i', 'header("location: ../auth.php?action=login")', $content);

                // replace 'location: ../users/login.php'
                $content = preg_replace('/header\s*\(\s*[\"\'\']location:\s*\.\.\/users\/login\.php[\"\'\']\s*\)/i', 'header("location: ../auth.php?action=login")', $content);

                // replace 'location: index.php' for admin redirecting to index.php  (the old admin login)
                // wait, careful with locations that are completely valid like header("location: index.php") for users
                // let's specifically target admin/index.php
                if (basename($dir) === 'admin') {
                    $content = preg_replace('/header\s*\(\s*[\"\'\']location:\s*index\.php[\"\'\']\s*\)/i', 'header("location: ../auth.php?action=login")', $content);
                } else if ($file->getFilename() === 'scan.php' && basename($dir) === 'coordinator') {
                    $content = preg_replace('/header\s*\(\s*[\"\'\']location:\s*login\.php[\"\'\']\s*\)/i', 'header("location: ../auth.php?action=login")', $content);
                }

                // anchor tag in view_results.php or others
                $content = str_replace('href="login.php"', 'href="../auth.php?action=login"', $content);
                $content = str_replace('href="register.php"', 'href="../auth.php?action=register"', $content);

                file_put_contents($file->getPathname(), $content);
            }
        }
    }
}
echo "Replaced redirects.";
?>