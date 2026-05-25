<?php
require_once '../config/db_connect.php'; // Includes the database connection

// --- NEW, MORE ROBUST QUERY ---
// This version uses subqueries to ensure that results are shown even if only one winner is selected.
$sql = "SELECT 
            e.event_name,
            r.submitted_at,
            (SELECT GROUP_CONCAT(u.full_name ORDER BY u.full_name SEPARATOR ', ') FROM result_winners rw JOIN users u ON rw.user_id = u.student_id WHERE rw.result_id = r.result_id AND rw.position = 1) AS first_place_winners,
            (SELECT GROUP_CONCAT(u.full_name ORDER BY u.full_name SEPARATOR ', ') FROM result_winners rw JOIN users u ON rw.user_id = u.student_id WHERE rw.result_id = r.result_id AND rw.position = 2) AS second_place_winners,
            (SELECT GROUP_CONCAT(u.full_name ORDER BY u.full_name SEPARATOR ', ') FROM result_winners rw JOIN users u ON rw.user_id = u.student_id WHERE rw.result_id = r.result_id AND rw.position = 3) AS third_place_winners
        FROM 
            results r
        JOIN 
            events e ON r.event_id = e.event_id
        ORDER BY 
            e.event_date DESC";

$results = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
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
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]), select, textarea {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e293b !important;
            transition: all 0.3s ease !important;
            box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.02) !important;
        }
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]):hover, select:hover, textarea:hover {
            border-color: #6366f1 !important;
            box-shadow: 0 2px 4px rgba(99,102,241,0.05) !important;
        }
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]):focus, select:focus, textarea:focus {
            outline: none !important;
            background: #ffffff !important;
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2) !important;
        }
        input::placeholder, textarea::placeholder {
            color: #94a3b8 !important;
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
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen relative overflow-x-hidden font-sans">
    <!-- Background Animated Gradients -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-indigo-300/30 blur-[120px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] rounded-full bg-purple-300/30 blur-[150px]"></div>
        <div class="absolute top-[40%] left-[50%] w-[40%] h-[40%] rounded-full bg-pink-300/30 blur-[120px]"></div>
    </div>
    <!-- Main Content Wrapper to keep it above gradients -->
    <div class="relative z-10 w-full h-full">
    <nav class="glass-panel shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <a href="../index.html" class="flex items-center text-xl font-bold text-indigo-600">Event Hub</a>
                <div class="flex items-center">
                    <?php if(isset($_SESSION["user_loggedin"]) && $_SESSION["user_loggedin"] === true): ?>
                        <a href="dashboard.php" class="text-slate-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">My Dashboard</a>
                    <?php else: ?>
                        <a href="../auth.php?action=login" class="text-slate-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-5xl mx-auto py-10 px-4">
        <h1 class="text-4xl font-bold text-center text-slate-800 mb-8">Official Event Results</h1>

        <div class="space-y-6">
            <?php if ($results && $results->num_rows > 0): ?>
                <?php while($row = $results->fetch_assoc()): ?>
                    <div class="glass-panel p-6 rounded-lg shadow-lg">
                        <h2 class="text-2xl font-bold text-indigo-700 mb-4"><?php echo htmlspecialchars($row['event_name']); ?></h2>
                        <div class="space-y-3">
                            <div class="flex items-start bg-yellow-100 p-3 rounded-md">
                                <span class="text-2xl mr-4 pt-1">🥇</span>
                                <div>
                                    <span class="text-lg font-semibold text-yellow-800">1st Place:</span>
                                    <p class="ml-2 text-lg text-slate-800"><?php echo htmlspecialchars($row['first_place_winners'] ?: 'Not Announced'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start bg-gray-200 p-3 rounded-md">
                                <span class="text-2xl mr-4 pt-1">🥈</span>
                                <div>
                                    <span class="text-lg font-semibold text-slate-600">2nd Place:</span>
                                    <p class="ml-2 text-lg text-slate-800"><?php echo htmlspecialchars($row['second_place_winners'] ?: 'Not Announced'); ?></p>
                                </div>
                            </div>
                            <div class="flex items-start bg-orange-200 p-3 rounded-md">
                                <span class="text-2xl mr-4 pt-1">🥉</span>
                                <div>
                                    <span class="text-lg font-semibold text-orange-800">3rd Place:</span>
                                    <p class="ml-2 text-lg text-slate-800"><?php echo htmlspecialchars($row['third_place_winners'] ?: 'Not Announced'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center glass-panel p-10 rounded-lg shadow-md">
                    <p class="text-slate-500 text-lg">No results have been published yet. Please check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div></body>
</html>