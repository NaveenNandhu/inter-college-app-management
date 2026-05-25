<?php
// Must include the database connection, which also starts the session.
require_once '../config/db_connect.php';

// Security Check: If the admin is not logged in, redirect them to the login page.
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

// --- Function to safely execute a count query ---
function getCount($conn, $sql) {
    $result = $conn->query($sql);
    if ($result) {
        return $result->fetch_assoc()['count'];
    }
    return 0; // Return 0 if the query fails for any reason
}

// --- Fetch Statistics from the Database using the new table name 'users' ---

// Count total meets
$meets_count = getCount($conn, "SELECT COUNT(meet_id) as count FROM meets");

// Count total events
$events_count = getCount($conn, "SELECT COUNT(event_id) as count FROM events");

// Count total users registered as 'student'
$students_count = getCount($conn, "SELECT COUNT(student_id) as count FROM users WHERE user_type = 'student'");

// Count total users registered as 'coordinator'
$coordinators_count = getCount($conn, "SELECT COUNT(student_id) as count FROM users WHERE user_type = 'coordinator'");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
<body class="bg-slate-50 text-slate-800 min-h-screen relative overflow-x-hidden">
    <!-- Background Animated Gradients -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-indigo-300/30 blur-[120px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] rounded-full bg-purple-300/30 blur-[150px]"></div>
        <div class="absolute top-[40%] left-[50%] w-[40%] h-[40%] rounded-full bg-pink-300/30 blur-[120px]"></div>
    </div>
    <!-- Main Content Wrapper to keep it above gradients -->
    <div class="relative z-10 w-full h-full">
    <div class="flex h-screen">
        <?php include '_sidebar.php'; // Include the reusable sidebar ?>
        
        <!-- Main Content -->
        <div class="flex-1 p-10">
            <h1 class="text-3xl font-bold text-slate-800 mb-6">Admin Dashboard</h1>
            <p class="text-slate-500 mb-8">This is the central dashboard. From here you can manage all aspects of the inter-college meets.</p>
            
            <!-- Updated Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                 <div class="glass-panel p-6 rounded-lg shadow-md transition-transform transform hover:scale-105">
                    <h3 class="font-bold text-lg text-slate-500 mb-2">Total Meets</h3>
                    <p class="text-4xl font-bold text-indigo-600"><?php echo $meets_count; ?></p>
                 </div>
                 <div class="glass-panel p-6 rounded-lg shadow-md transition-transform transform hover:scale-105">
                    <h3 class="font-bold text-lg text-slate-500 mb-2">Total Events</h3>
                    <p class="text-4xl font-bold text-green-600"><?php echo $events_count; ?></p>
                 </div>
                 <div class="glass-panel p-6 rounded-lg shadow-md transition-transform transform hover:scale-105">
                    <h3 class="font-bold text-lg text-slate-500 mb-2">Student Participants</h3>
                    <p class="text-4xl font-bold text-blue-600"><?php echo $students_count; ?></p>
                 </div>
                 <div class="glass-panel p-6 rounded-lg shadow-md transition-transform transform hover:scale-105">
                    <h3 class="font-bold text-lg text-slate-500 mb-2">Registered Coordinators</h3>
                    <p class="text-4xl font-bold text-yellow-500"><?php echo $coordinators_count; ?></p>
                 </div>
            </div>
        </div>
    </div>
</div></body>
</html>