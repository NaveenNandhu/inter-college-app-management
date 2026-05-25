<?php
$dirs = ['admin', 'coordinator', 'users', '.'];
$files = [];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        if ($dir === '.') {
            foreach (glob('*.php') as $f) {
                if (is_file($f))
                    $files[] = realpath($f);
            }
        } else {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }
    }
}

$css_inject = "
        /* Global Style for Forms - Glass Light Effect with Hover */
        input:not([type=\"checkbox\"]):not([type=\"submit\"]):not([type=\"hidden\"]), select, textarea {
            background: rgba(255, 255, 255, 0.06) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #f8fafc !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
        }
        input:not([type=\"checkbox\"]):not([type=\"submit\"]):not([type=\"hidden\"]):hover, select:hover, textarea:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(167, 139, 250, 0.6) !important;
            transform: translateY(-1px) !important;
        }
        input:not([type=\"checkbox\"]):not([type=\"submit\"]):not([type=\"hidden\"]):focus, select:focus, textarea:focus {
            outline: none !important;
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(167, 139, 250, 0.9) !important;
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.25) !important;
        }
        input::placeholder, textarea::placeholder {
            color: rgba(255, 255, 255, 0.4) !important;
        }
        option {
            background-color: #0f172a !important;
            color: white !important;
        }
";

foreach ($files as $file) {
    if (strpos($file, 'qrcodes') !== false)
        continue;
    if (basename($file) === 'export_participants_pdf.php')
        continue;
    if (basename($file) === 'download_certificate.php')
        continue;

    $content = file_get_contents($file);

    // Check if css already injected
    if (strpos($content, '/* Global Style for Forms') === false) {
        $content = str_replace('
        /* Advanced Table UI */
        table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            width: 100% !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        thead {
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.2) 0%, rgba(147, 51, 234, 0.2) 100%) !important;
        }
        th {
            padding: 1rem 1.25rem !important;
            text-align: left !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: #e2e8f0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        tbody tr {
            background-color: rgba(255, 255, 255, 0.02) !important;
            transition: all 0.2s ease-in-out !important;
        }
        tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
            transform: scale(1.002) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
            z-index: 10 !important;
            position: relative !important;
        }
        td {
            padding: 1.25rem 1.25rem !important;
            font-size: 0.95rem !important;
            color: #cbd5e1 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            vertical-align: middle !important;
        }
        tbody tr:last-child td {
            border-bottom: none !important;
        }
        /* Buttons inside tables */
        td a, td button {
            transition: all 0.2s ease !important;
        }
        td a:hover, td button:hover {
            transform: translateY(-2px) !important;
            filter: brightness(1.2) !important;
        }
    </style>', $css_inject . "    
        /* Advanced Table UI */
        table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            width: 100% !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        thead {
            background: linear-gradient(90deg, rgba(79, 70, 229, 0.2) 0%, rgba(147, 51, 234, 0.2) 100%) !important;
        }
        th {
            padding: 1rem 1.25rem !important;
            text-align: left !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: #e2e8f0 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        tbody tr {
            background-color: rgba(255, 255, 255, 0.02) !important;
            transition: all 0.2s ease-in-out !important;
        }
        tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
            transform: scale(1.002) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
            z-index: 10 !important;
            position: relative !important;
        }
        td {
            padding: 1.25rem 1.25rem !important;
            font-size: 0.95rem !important;
            color: #cbd5e1 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05) !important;
            vertical-align: middle !important;
        }
        tbody tr:last-child td {
            border-bottom: none !important;
        }
        /* Buttons inside tables */
        td a, td button {
            transition: all 0.2s ease !important;
        }
        td a:hover, td button:hover {
            transform: translateY(-2px) !important;
            filter: brightness(1.2) !important;
        }
    </style>", $content);
        file_put_contents($file, $content);
    }
}
echo "Inputs styled successfully.\n";
?>