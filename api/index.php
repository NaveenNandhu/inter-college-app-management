<?php
// Root-level router for Vercel deployments

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'] ?? '/';

// Remove leading slash
$path = ltrim($path, '/');

// Default to home.php if accessing root
if ($path === '' || $path === 'index.php' || $path === 'api/index.php') {
    $path = 'home.php';
}

$base_dir = dirname(__DIR__); // Point to project root
$target_path = $base_dir . '/' . $path;

function serve_file($file, $path) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext === 'php') {
        // Change working directory so relative includes/requires work properly
        chdir(dirname($file));
        
        // Mock $_SERVER variables so scripts think they are accessed directly
        $_SERVER['SCRIPT_FILENAME'] = $file;
        $_SERVER['SCRIPT_NAME'] = '/' . ltrim($path, '/');
        $_SERVER['PHP_SELF'] = '/' . ltrim($path, '/');
        
        require basename($file);
    } else {
        $mime_types = [
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            'json' => 'application/json',
            'pdf'  => 'application/pdf',
            'txt'  => 'text/plain',
            'woff' => 'font/woff',
            'woff2'=> 'font/woff2',
            'ttf'  => 'font/ttf'
        ];
        $ext = strtolower($ext);
        if (array_key_exists($ext, $mime_types)) {
            header('Content-Type: ' . $mime_types[$ext]);
        } else {
            // Default to text/html for unknown text files, or octet-stream
            header('Content-Type: text/html');
        }
        readfile($file);
    }
}

// 1. Try exact file match
if (file_exists($target_path) && is_file($target_path)) {
    $real_path = realpath($target_path);
    // Security check against directory traversal
    if ($real_path !== false && strpos($real_path, $base_dir) === 0) {
        serve_file($real_path, $path);
        exit;
    }
}

// 2. Try appending .php (mimics Vercel cleanUrls behavior)
$target_path_php = $target_path . '.php';
if (file_exists($target_path_php) && is_file($target_path_php)) {
    $real_path = realpath($target_path_php);
    if ($real_path !== false && strpos($real_path, $base_dir) === 0) {
        serve_file($real_path, $path . '.php');
        exit;
    }
}

// 3. Not found
http_response_code(404);
echo "404 Not Found";
