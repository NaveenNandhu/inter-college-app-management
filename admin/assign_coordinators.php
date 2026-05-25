<?php
require_once '../config/db_connect.php';

// Security Check
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

$feedback_message = '';
$feedback_type = 'error';

// --- Handle form submission for assigning MULTIPLE coordinators to ONE event ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_assignments'])) {
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $assigned_user_ids = $_POST['user_ids'] ?? []; // This will be an array of selected user IDs

    if (empty($event_id)) {
        $feedback_message = "Please select an event first.";
    } else {
        // --- Smart Update Logic: Delete old assignments, then insert new ones ---
        
        // 1. First, delete all existing assignments for this event.
        $delete_sql = "DELETE FROM event_assignments WHERE event_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $event_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // 2. Now, insert the new set of assignments.
        if (!empty($assigned_user_ids)) {
            $insert_sql = "INSERT INTO event_assignments (event_id, user_id) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            $success_count = 0;
            foreach ($assigned_user_ids as $user_id) {
                $insert_stmt->bind_param("ii", $event_id, $user_id);
                if ($insert_stmt->execute()) {
                    $success_count++;
                }
            }
            $insert_stmt->close();
            
            $feedback_type = 'success';
            $feedback_message = "Successfully saved " . $success_count . " coordinator assignments for the event.";
        } else {
            // This case handles when all coordinators are un-checked
            $feedback_type = 'success';
            $feedback_message = "All coordinators have been unassigned from this event.";
        }
    }
}

// --- Fetch data for the page ---

// 1. Fetch all events for the primary dropdown
$events_sql = "SELECT e.event_id, e.event_name, m.meet_name 
               FROM events e JOIN meets m ON e.meet_id = m.meet_id 
               ORDER BY m.meet_name, e.event_date DESC";
$events_result = $conn->query($events_sql);

// 2. Fetch all potential coordinators
$coordinators_sql = "SELECT student_id, full_name, roll_number FROM users WHERE user_type = 'coordinator' ORDER BY full_name";
$coordinators_result = $conn->query($coordinators_sql);
$all_coordinators = $coordinators_result->fetch_all(MYSQLI_ASSOC);

// 3. Fetch all existing assignments to display in the main table at the bottom
$assignments_sql = "SELECT u.full_name, u.roll_number, e.event_name 
                    FROM event_assignments a
                    JOIN users u ON a.user_id = u.student_id
                    JOIN events e ON a.event_id = e.event_id
                    ORDER BY e.event_name, u.full_name";
$assignments_result = $conn->query($assignments_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Coordinators</title>
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
            <h1 class="text-3xl font-bold text-slate-800 mb-6">Assign Coordinators to Events</h1>

            <?php if(!empty($feedback_message)): ?>
                <div class="p-3 rounded mb-6 text-center <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $feedback_message; ?>
                </div>
            <?php endif; ?>

            <!-- Assignment Form -->
            <form id="assignment_form" action="assign_coordinators.php" method="post">
                <div class="glass-panel p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-bold mb-4 border-b pb-2">Step 1: Select an Event</h2>
                    <select name="event_id" id="event_selector" class="mt-1 block w-full md:w-1/2 p-2 border border-slate-200 rounded-md" required>
                        <option value="">-- Choose an Event to Manage --</option>
                        <?php while($event = $events_result->fetch_assoc()): ?>
                            <option value="<?php echo $event['event_id']; ?>">
                                <?php echo htmlspecialchars($event['meet_name']) . ' - ' . htmlspecialchars($event['event_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div id="coordinators_section" class="glass-panel p-6 rounded-lg shadow-md mb-8 hidden">
                    <h2 class="text-xl font-bold mb-4 border-b pb-2">Step 2: Assign Coordinators</h2>
                    <p class="text-sm text-slate-500 mb-4">Check the boxes for all faculty or volunteers you want to assign to this event.</p>
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        <?php foreach($all_coordinators as $coordinator): ?>
                            <label class="flex items-center space-x-3 p-2 rounded hover:bg-slate-50">
                                <input type="checkbox" name="user_ids[]" value="<?php echo $coordinator['student_id']; ?>" class="h-5 w-5 text-indigo-600 border-slate-200 rounded coordinator-checkbox">
                                <div>
                                    <span class="font-medium"><?php echo htmlspecialchars($coordinator['full_name']); ?></span>
                                    <span class="text-xs text-slate-500">(ID: <?php echo htmlspecialchars($coordinator['roll_number'] ?: 'N/A'); ?>)</span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" name="save_assignments" class="mt-6 w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded hover:bg-indigo-700">
                        Save Assignments for this Event
                    </button>
                </div>
            </form>
            
            <!-- Existing Assignments Table -->
            <div class="glass-panel p-6 rounded-lg shadow-md mt-8">
                <h2 class="text-xl font-bold mb-4">Current Assignment Overview</h2>
                <div class="overflow-x-auto">
                    <!-- Table content remains the same as before -->
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-50 border border-slate-200">
                            <tr>
                                <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Event</th>
                                <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Assigned Coordinator</th>
                                <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Coordinator ID</th>
                            </tr>
                        </thead>
                        <tbody class="glass-panel divide-y divide-gray-200">
                            <?php if ($assignments_result && $assignments_result->num_rows > 0): ?>
                                <?php while($assignment = $assignments_result->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50 border border-slate-200">
                                    <td class="p-3 font-medium text-slate-900"><?php echo htmlspecialchars($assignment['event_name']); ?></td>
                                    <td class="p-3 text-slate-500"><?php echo htmlspecialchars($assignment['full_name']); ?></td>
                                    <td class="p-3 text-slate-500"><?php echo htmlspecialchars($assignment['roll_number']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center p-4 text-slate-500">No coordinators assigned to any event yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const eventSelector = document.getElementById('event_selector');
            const coordinatorsSection = document.getElementById('coordinators_section');
            const checkboxes = document.querySelectorAll('.coordinator-checkbox');

            eventSelector.addEventListener('change', function() {
                const eventId = this.value;

                // First, uncheck all boxes
                checkboxes.forEach(cb => cb.checked = false);

                if (!eventId) {
                    coordinatorsSection.classList.add('hidden');
                    return;
                }

                // Show the coordinator list
                coordinatorsSection.classList.remove('hidden');

                // Fetch the current assignments for this event to pre-check the boxes
                fetch(`../api/get_assignments_by_event.php?event_id=${eventId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.assigned_user_ids) {
                            checkboxes.forEach(checkbox => {
                                // Check if the checkbox's value (user_id) is in the array of assigned IDs
                                if (data.assigned_user_ids.includes(checkbox.value)) {
                                    checkbox.checked = true;
                                }
                            });
                        }
                    })
                    .catch(error => console.error('Error fetching assignments:', error));
            });
        });
    </script>
</div></body>
</html>
