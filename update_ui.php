<?php
$dirs = ['admin', 'coordinator', 'users'];
$files = [];

foreach ($dirs as $dir) {
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
        continue; // skip qrcodes generated
    if (basename($file) === 'export_participants_pdf.php')
        continue; // FPDF might break
    if (basename($file) === 'download_certificate.php')
        continue; // FDPF might break

    $content = file_get_contents($file);

    // Ensure we don't double apply
    // if (strpos($content, 'Outfit') !== false && basename($file) !== 'index.php') {
    //     continue;
    // }

    // 1. Replace Inter font link with Outfit
    $content = preg_replace(
        '/<link href="https:\/\/fonts\.googleapis\.com\/css2\?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">/',
        '<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">',
        $content
    );

    // 2. Replace the internal style block
    $content = preg_replace(
        '/<style>\s*body \{ font-family: \'Inter\', sans-serif; \}\s*<\/style>/',
        "<style>\n        body { font-family: 'Outfit', sans-serif; }\n        .glass-panel {\n            background: rgba(255, 255, 255, 0.03);\n            backdrop-filter: blur(24px);\n            -webkit-backdrop-filter: blur(24px);\n            border: 1px solid rgba(255, 255, 255, 0.08);\n            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);\n        }\n    
        /* Global Style for Forms - Glass Light Effect with Hover */
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]), select, textarea {
            background: rgba(255, 255, 255, 0.06) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            color: #f8fafc !important;
            transition: all 0.3s ease !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
        }
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]):hover, select:hover, textarea:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            border-color: rgba(167, 139, 250, 0.6) !important;
            transform: translateY(-1px) !important;
        }
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]):focus, select:focus, textarea:focus {
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
    </style>",
        $content
    );

    // 3. Replace body tag + inject gradients
    $content = preg_replace(
        '/<body class="bg-gray-100(.*?)">/s',
        '<body class="bg-[#0a0f1c] text-gray-100 min-h-screen relative overflow-x-hidden$1">
    <!-- Background Animated Gradients -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-indigo-600/20 blur-[120px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] rounded-full bg-purple-600/20 blur-[150px]"></div>
        <div class="absolute top-[40%] left-[50%] w-[40%] h-[40%] rounded-full bg-pink-600/10 blur-[120px]"></div>
    </div>
    <!-- Main Content Wrapper to keep it above gradients -->
    <div class="relative z-10 w-full h-full">',
        $content
    );

    // Add closing div for the wrapper before </body>
    $content = preg_replace(
        '/<\/body>/',
        '</div></body>',
        $content
    );

    // Colors and backgrounds
    $content = preg_replace('/\bbg-white\b/', 'glass-panel', $content);
    $content = preg_replace('/\bbg-gray-100\b/', 'bg-[#0a0f1c]', $content);
    $content = preg_replace('/\bbg-gray-50\b/', 'bg-white/5', $content);
    $content = preg_replace('/\bbg-gray-800\b/', 'bg-black/40 border-r border-white/5', $content); // for sidebars mainly
    $content = preg_replace('/\bbg-gray-700\b/', 'bg-white/10', $content);

    // Text colors
    $content = preg_replace('/\btext-gray-900\b/', 'text-white', $content);
    $content = preg_replace('/\btext-gray-800\b/', 'text-gray-100', $content);
    $content = preg_replace('/\btext-gray-700\b/', 'text-gray-300', $content);
    $content = preg_replace('/\btext-gray-600\b/', 'text-gray-400', $content);
    $content = preg_replace('/\btext-gray-500\b/', 'text-gray-400', $content);

    // Borders
    $content = preg_replace('/\bborder-gray-300\b/', 'border-white/10', $content);
    $content = preg_replace('/\bborder-gray-200\b/', 'border-white/5', $content);

    // Form Inputs - give them a glass look
    $content = preg_replace('/class="([^"]*)shadow appearance-none border rounded w-full([^"]*)"/', 'class="$1bg-black/20 border border-white/10 text-white rounded-lg w-full$2"', $content);

    // Some specific tailwind replacements for tables
    $content = preg_replace('/\btext-gray-500\b/', 'text-gray-400', $content);

    // Specifically for users/view_results.php navbar because `bg-indigo-600 text-white` might have been rewritten weirdly if bg-white was there
    // Actually our script handles things individually.

    // Save
    file_put_contents($file, $content);
}
echo "UI updated successfully across working pages.\n";
?>