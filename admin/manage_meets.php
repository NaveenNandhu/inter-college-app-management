<?php
require_once '../config/db_connect.php';

// Security Check
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

// --- Logic for creating a new meet (remains the same) ---
$feedback_message = "";
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_meet'])){
    $meet_name = trim($_POST['meet_name']);
    $department = trim($_POST['department']);
    if(!empty($meet_name) && !empty($department)){
        $sql = "INSERT INTO meets (meet_name, department) VALUES (?, ?)";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("ss", $meet_name, $department);
            if($stmt->execute()){
                $feedback_message = "<div class='bg-green-100 text-green-800 p-3 rounded mb-4'>Meet created successfully!</div>";
            } else {
                $feedback_message = "<div class='bg-red-100 text-red-800 p-3 rounded mb-4'>Error: Could not create meet.</div>";
            }
            $stmt->close();
        }
    } else {
        $feedback_message = "<div class='bg-yellow-100 text-yellow-800 p-3 rounded mb-4'>Please fill in all fields.</div>";
    }
}

// Fetch all meets to display in the table
$meets_result = $conn->query("SELECT meet_id, meet_name, department, created_at FROM meets ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Meets</title>
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
    <div class="flex h-screen">
        <?php include '_sidebar.php'; ?>
        
        <div class="flex-1 p-10 overflow-y-auto">
            <h1 class="text-3xl font-bold text-slate-800 mb-6">Manage Meets</h1>

            <?php echo $feedback_message; ?>

            <!-- Create New Meet Form -->
            <div class="glass-panel p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-bold mb-4">Create a New Meet</h2>
                <form action="manage_meets.php" method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <input type="text" name="meet_name" placeholder="Meet Name (e.g., Tech Fest 2025)" class="mt-1 block w-full p-2 border rounded" required>
                        <input type="text" name="department" placeholder="Department" class="mt-1 block w-full p-2 border rounded" required>
                    </div>
                    <button type="submit" name="create_meet" class="mt-4 bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">Create Meet</button>
                </form>
            </div>

            <!-- List of Created Meets -->
            <div class="glass-panel p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4">Existing Meets</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-50 border border-slate-200">
                            <tr>
                                <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Meet Name</th>
                                <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Department</th>
                                <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Created</th>
                                <!-- New Column for Actions -->
                                <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="glass-panel divide-y divide-gray-200">
                            <?php if ($meets_result && $meets_result->num_rows > 0): ?>
                                <?php while($row = $meets_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="p-3 font-medium text-slate-900"><?php echo htmlspecialchars($row['meet_name']); ?></td>
                                    <td class="p-3 text-slate-500"><?php echo htmlspecialchars($row['department']); ?></td>
                                    <td class="p-3 text-slate-500"><?php echo date("M j, Y", strtotime($row['created_at'])); ?></td>
                                    <td class="p-3 text-sm font-medium">
                                        <!-- New Edit and Delete Links -->
                                        <a href="edit_meet.php?id=<?php echo $row['meet_id']; ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        <a href="delete_meet.php?id=<?php echo $row['meet_id']; ?>" class="text-red-600 hover:text-red-900 ml-4" onclick="return confirm('Are you sure you want to delete this meet? This will also delete all events associated with it.');">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center p-4 text-slate-500">No meets created yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div></body>
</html>