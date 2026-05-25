<?php
require_once '../config/db_connect.php';

// --- NEW, MORE FLEXIBLE SECURITY CHECK ---
$is_admin = isset($_SESSION["admin_loggedin"]) && $_SESSION["admin_loggedin"] === true;
$is_coordinator = isset($_SESSION["user_loggedin"]) && $_SESSION["user_loggedin"] === true && $_SESSION['user_type'] === 'coordinator';

if (!$is_admin && !$is_coordinator) {
    // If user is neither an admin nor a coordinator, deny access.
    header("location: ../auth.php?action=login");
    exit;
}

// Determine the ID of the person submitting the results
$submitter_id = $is_admin ? $_SESSION['admin_id'] : $_SESSION['user_id'];

// Get the event_id from the URL (remains the same)
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    header("location: dashboard.php");
    exit;
}

$feedback_message = '';
$feedback_type = 'error';

// --- Handle form submission (using the new $submitter_id) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_place = $_POST['first_place'] ?: NULL;
    $second_place = $_POST['second_place'] ?: NULL;
    $third_place = $_POST['third_place'] ?: NULL;

    $places = array_filter([$first_place, $second_place, $third_place]);
    if (count($places) !== count(array_unique($places))) {
        $feedback_message = "You cannot assign the same participant to multiple places.";
    } else {
        $check_sql = "SELECT result_id FROM results WHERE event_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $event_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            // Logic to UPDATE existing results can be added here if needed
            $feedback_message = "Results have already been submitted for this event. To edit, please implement an update feature.";
        } else {
            // Insert the new results
            $sql = "INSERT INTO results (event_id, first_place_user_id, second_place_user_id, third_place_user_id, submitted_by_coordinator_id) VALUES (?, ?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                // The submitter_id will be the admin's ID or the coordinator's ID
                $stmt->bind_param("iiiii", $event_id, $first_place, $second_place, $third_place, $submitter_id);
                if ($stmt->execute()) {
                    $feedback_type = 'success';
                    $feedback_message = "Results submitted successfully!";
                } else {
                    $feedback_message = "Error: Could not submit results.";
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// --- Fetch data for the page (remains the same) ---
// Get event details
$event_sql = "SELECT event_name FROM events WHERE event_id = ?";
$event_stmt = $conn->prepare($event_sql);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_stmt->bind_result($event_name);
$event_stmt->fetch();
$event_stmt->close();

if (empty($event_name)) {
    header("location: dashboard.php");
    exit;
}

// Get all participants registered for this event
$participants_sql = "SELECT u.student_id, u.full_name, u.roll_number 
                     FROM registrations r
                     JOIN users u ON r.student_id = u.student_id
                     WHERE r.event_id = ? AND r.attendance_marked = 1
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Results</title>
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
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">Submit Results</h1>
                    <p class="text-xl text-indigo-600 font-semibold"><?php echo htmlspecialchars($event_name); ?></p>
                </div>
                <!-- Smart "Back" button -->
                <a href="<?php echo $is_admin ? '../admin/dashboard.php' : 'dashboard.php'; ?>" class="text-blue-500 hover:underline">&larr; Back to Dashboard</a>
            </div>

            <?php if(!empty($feedback_message)): ?>
                <div class="<?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'; ?> p-3 rounded mb-6 text-center">
                    <?php echo $feedback_message; ?>
                </div>
            <?php endif; ?>

            <form action="submit_results.php?event_id=<?php echo $event_id; ?>" method="post" class="space-y-6">
                <?php if (empty($participants)): ?>
                    <p class="text-center text-slate-500 py-10">There are no registered participants for this event to assign results to.</p>
                <?php else: ?>
                    <!-- Winner selection dropdowns (remain the same) -->
                    <div>
                        <label for="first_place" class="block text-lg font-medium text-slate-600">🥇 First Place</label>
                        <select name="first_place" id="first_place" class="mt-1 block w-full p-3 border border-slate-200 rounded-md shadow-sm">
                            <option value="">-- Select Winner --</option>
                            <?php foreach ($participants as $p): ?>
                                <option value="<?php echo $p['student_id']; ?>">
                                    <?php echo htmlspecialchars($p['full_name']) . ' (' . htmlspecialchars($p['roll_number'] ?: 'N/A') . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="second_place" class="block text-lg font-medium text-slate-600">🥈 Second Place</label>
                        <select name="second_place" id="second_place" class="mt-1 block w-full p-3 border border-slate-200 rounded-md shadow-sm">
                            <option value="">-- Select Winner --</option>
                             <?php foreach ($participants as $p): ?>
                                <option value="<?php echo $p['student_id']; ?>">
                                    <?php echo htmlspecialchars($p['full_name']) . ' (' . htmlspecialchars($p['roll_number'] ?: 'N/A') . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="third_place" class="block text-lg font-medium text-slate-600">🥉 Third Place</label>
                        <select name="third_place" id="third_place" class="mt-1 block w-full p-3 border border-slate-200 rounded-md shadow-sm">
                            <option value="">-- Select Winner --</option>
                             <?php foreach ($participants as $p): ?>
                                <option value="<?php echo $p['student_id']; ?>">
                                    <?php echo htmlspecialchars($p['full_name']) . ' (' . htmlspecialchars($p['roll_number'] ?: 'N/A') . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pt-4">
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-md text-lg">
                            Finalize and Submit Results
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </main>
</div></body>
</html>