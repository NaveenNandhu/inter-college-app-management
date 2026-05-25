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

$light_css = "<style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; color: #1e293b; }
        
        /* Reveal Animations */
        @keyframes reveal { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .animate-reveal { animation: reveal 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
        @keyframes float { 0% { transform: translateY(0px) rotate(0deg); } 50% { transform: translateY(-20px) rotate(5deg); } 100% { transform: translateY(0px) rotate(0deg); } }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delayed { animation: float 8s ease-in-out infinite; animation-delay: 2s; }

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08);
            color: #1e293b;
        }
        .glass-nav {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        .glass-card {
            background: linear-gradient(145deg, #ffffff 0%, #f1f5f9 100%);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            color: #334155;
        }
        .glass-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.4);
        }
    
        /* Global Style for Forms - Glass Light Effect with Hover */
        input:not([type=\"checkbox\"]):not([type=\"submit\"]):not([type=\"hidden\"]), select, textarea {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e293b !important;
            transition: all 0.3s ease !important;
            box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.02) !important;
        }
        input:not([type=\"checkbox\"]):not([type=\"submit\"]):not([type=\"hidden\"]):hover, select:hover, textarea:hover {
            border-color: #6366f1 !important;
            box-shadow: 0 2px 4px rgba(99,102,241,0.05) !important;
        }
        input:not([type=\"checkbox\"]):not([type=\"submit\"]):not([type=\"hidden\"]):focus, select:focus, textarea:focus {
            outline: none !important;
            background: #ffffff !important;
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2) !important;
        }
        input::placeholder, textarea::placeholder {
            color: #94a3b8 !important;
        }
        option {
            background-color: #ffffff !important;
            color: #1e293b !important;
        }
    
        /* Advanced Table UI */
        table {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            width: 100% !important;
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03) !important;
            border: 1px solid #e2e8f0 !important;
            background: #ffffff !important;
        }
        thead {
            background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%) !important;
        }
        th {
            padding: 1rem 1.25rem !important;
            text-align: left !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            color: #475569 !important;
            border-bottom: 2px solid #e2e8f0 !important;
        }
        tbody tr {
            background-color: #ffffff !important;
            transition: all 0.2s ease-in-out !important;
        }
        tbody tr:hover {
            background-color: #f8fafc !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05) !important;
            transform: scale(1.001) !important;
        }
        td {
            padding: 1rem 1.25rem !important;
            font-size: 0.95rem !important;
            color: #334155 !important;
            border-bottom: 1px solid #f1f5f9 !important;
            vertical-align: middle !important;
        }
        tbody tr:last-child td {
            border-bottom: none !important;
        }
    </style>";

foreach ($files as $file) {
    if (strpos($file, 'qrcodes') !== false)
        continue;

    $content = file_get_contents($file);

    // Replace the style block entirely
    $content = preg_replace('/<style>.*?<\/style>/s', $light_css, $content);

    // Custom replacing logic for class attributes
    $content = preg_replace_callback('/class="([^"]*)"/s', function ($matches) {
        $classes = $matches[1];

        $hasDarkBg = preg_match('/\bbg-(indigo|purple|pink|blue|emerald|green|red)-[56789]00\b/', $classes) ||
            preg_match('/\bfrom-(indigo|purple|pink|blue|emerald|green|red)-[56789]00\b/', $classes);

        if (!$hasDarkBg) {
            $classes = preg_replace('/\btext-white\b/', 'text-slate-900', $classes);
            $classes = preg_replace('/\btext-gray-100\b/', 'text-slate-800', $classes);
            $classes = preg_replace('/\btext-gray-300\b/', 'text-slate-600', $classes);
            $classes = preg_replace('/\btext-gray-400\b/', 'text-slate-500', $classes);
        } else {
            // Ensure dark backgrounds have white text if not explicitly set
            if (strpos($classes, 'text-') === false) {
                // Not ideal to randomly add, but leaves it alone.
            }
        }

        // bg colors
        $classes = preg_replace('/\bbg-\[\#0a0f1c\]\b/', 'bg-slate-50', $classes);
        $classes = preg_replace('/\bbg-black\/40\b/', 'bg-white shadow-md border-r border-slate-200', $classes);
        $classes = preg_replace('/\bbg-black\/80\b/', 'bg-white border-t border-slate-200', $classes);
        $classes = preg_replace('/\bbg-white\/10\b/', 'bg-white shadow-sm border border-slate-200', $classes);
        $classes = preg_replace('/\bbg-white\/5\b/', 'bg-slate-50 border border-slate-200', $classes);

        // Gradients
        $classes = preg_replace('/\bbg-indigo-600\/20\b/', 'bg-indigo-300/30', $classes);
        $classes = preg_replace('/\bbg-purple-600\/20\b/', 'bg-purple-300/30', $classes);
        $classes = preg_replace('/\bbg-pink-600\/10\b/', 'bg-pink-300/30', $classes);
        $classes = preg_replace('/\bbg-pink-500\/20\b/', 'bg-pink-200/40', $classes);
        $classes = preg_replace('/\bbg-indigo-500\/20\b/', 'bg-indigo-200/40', $classes);
        $classes = preg_replace('/\bbg-purple-500\/20\b/', 'bg-purple-200/40', $classes);
        $classes = preg_replace('/\bbg-blue-500\/20\b/', 'bg-blue-200/40', $classes);
        $classes = preg_replace('/\bbg-emerald-500\/20\b/', 'bg-emerald-200/40', $classes);

        // Borders
        $classes = preg_replace('/\bborder-white\/10\b/', 'border-slate-200', $classes);
        $classes = preg_replace('/\bborder-white\/5\b/', 'border-slate-100', $classes);

        // Text specific overrides if they broke logic (like specific header gradient)
        if (strpos($classes, 'from-white') !== false) {
            $classes = preg_replace('/\bfrom-white\b/', 'from-slate-900', $classes);
            $classes = preg_replace('/\bto-gray-400\b/', 'to-slate-600', $classes);
        }
        if (strpos($classes, 'via-indigo-100') !== false) {
            $classes = preg_replace('/\bvia-indigo-100\b/', 'via-indigo-600', $classes);
            $classes = preg_replace('/\bto-indigo-300\b/', 'to-indigo-800', $classes);
        }

        return 'class="' . $classes . '"';
    }, $content);

    // A couple extra specific replacements tailored for the index / components
    $content = preg_replace('/text-transparent bg-gradient-to-r from-white via-indigo-100 to-indigo-300/', 'text-transparent bg-gradient-to-r from-slate-900 via-indigo-600 to-indigo-900', $content);
    $content = preg_replace('/<svg([^>]*)text-white([^>]*)>/', '<svg$1text-slate-700$2>', $content);

    file_put_contents($file, $content);
}

echo "Light theme applied successfully.";
?>