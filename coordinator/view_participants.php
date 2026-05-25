<?php
require_once '../config/db_connect.php';

// Security check: Ensure a coordinator is logged in.
if(!isset($_SESSION["user_loggedin"]) || $_SESSION["user_loggedin"] !== true || $_SESSION['user_type'] !== 'coordinator'){
    header("location: ../auth.php?action=login");
    exit;
}

// Get the event_id from the URL and validate it.
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    header("location: dashboard.php");
    exit;
}

// --- Fetch data for the page ---
// 1. Get the name of the event for the page title.
$event_sql = "SELECT event_name FROM events WHERE event_id = ?";
$event_stmt = $conn->prepare($event_sql);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_stmt->bind_result($event_name);
$event_stmt->fetch();
$event_stmt->close();

if (empty($event_name)) {
    // If no event is found with that ID, redirect back to the dashboard.
    header("location: dashboard.php");
    exit;
}

// 2. Get all participants who are registered for this specific event.
$participants_sql = "SELECT u.full_name, u.roll_number, u.college_name 
                     FROM registrations r
                     JOIN users u ON r.student_id = u.student_id
                     WHERE r.event_id = ?
                     ORDER BY u.full_name ASC";
$participants = [];
if ($part_stmt = $conn->prepare($participants_sql)) {
    $part_stmt->bind_param("i", $event_id);
    $part_stmt->execute();
    $result = $part_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $participants[] = $row;
    }
    $part_stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant List</title>
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
    <main class="max-w-4xl mx-auto py-10 px-4">
        <div class="glass-panel p-8 rounded-lg shadow-md">
            <div class="flex flex-col md:flex-row justify-between md:items-start mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">Participant List</h1>
                    <p class="text-xl text-indigo-600 font-semibold"><?php echo htmlspecialchars($event_name); ?></p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <a href="dashboard.php" class="text-blue-500 hover:underline py-2">&larr; Back to Dashboard</a>
                    
                    <!-- *** THE FIX IS HERE *** -->
                    <!-- The Export button will only be displayed if there are participants -->
                    <?php if (!empty($participants)): ?>
                        <a href="export_participants_pdf.php?event_id=<?php echo $event_id; ?>" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded transition duration-300">
                            Export as PDF
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50 border border-slate-200">
                        <tr>
                            <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Participant Name</th>
                            <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Roll Number / ID</th>
                            <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">College</th>
                        </tr>
                    </thead>
                    <tbody class="glass-panel divide-y divide-gray-200">
                        <?php if (empty($participants)): ?>
                            <tr>
                                <td colspan="3" class="text-center p-4 text-slate-500">No participants have registered for this event yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($participants as $participant): ?>
                                <tr class="hover:bg-slate-50 border border-slate-200">
                                    <td class="p-3 font-medium text-slate-900"><?php echo htmlspecialchars($participant['full_name']); ?></td>
                                    <td class="p-3 text-slate-500"><?php echo htmlspecialchars($participant['roll_number'] ?: 'N/A'); ?></td>
                                    <td class="p-3 text-slate-500"><?php echo htmlspecialchars($participant['college_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div></body>
</html>