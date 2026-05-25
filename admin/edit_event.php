<?php
require_once '../config/db_connect.php';

// Security Check
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

// Get event ID from URL
$event_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$event_id) {
    header("location: manage_events.php");
    exit;
}

$feedback_message = '';

// --- Handle form submission for UPDATING the event ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $meet_id = $_POST['meet_id'];
    $event_name = trim($_POST['event_name']);
    $description = trim($_POST['description']);
    $event_date = trim($_POST['event_date']);
    $venue = trim($_POST['venue']);
    $event_type = $_POST['event_type'];

    // Convert empty datetime to NULL
    $event_date_for_db = !empty($event_date) ? date("Y-m-d H:i:s", strtotime($event_date)) : null;

    $sql = "UPDATE events SET meet_id = ?, event_name = ?, description = ?, event_date = ?, venue = ?, event_type = ? WHERE event_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("isssssi", $meet_id, $event_name, $description, $event_date_for_db, $venue, $event_type, $event_id);
        if ($stmt->execute()) {
            header("location: manage_events.php");
            exit;
        } else {
            $feedback_message = "<div class='bg-red-100 text-red-800 p-3 rounded'>Error updating event.</div>";
        }
        $stmt->close();
    }
}

// --- Fetch existing event data to pre-fill the form ---
$sql_fetch = "SELECT meet_id, event_name, description, event_date, venue, event_type FROM events WHERE event_id = ?";
$stmt_fetch = $conn->prepare($sql_fetch);
$stmt_fetch->bind_param("i", $event_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
if ($result->num_rows === 1) {
    $event = $result->fetch_assoc();
} else {
    // If no event found, redirect
    header("location: manage_events.php");
    exit;
}
$stmt_fetch->close();

// Fetch all meets for the dropdown
$meets = $conn->query("SELECT meet_id, meet_name FROM meets");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event</title>
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
        
        <div class="flex-1 p-10">
            <h1 class="text-3xl font-bold text-slate-800 mb-6">Edit Event</h1>
            <?php echo $feedback_message; ?>

            <div class="glass-panel p-6 rounded-lg shadow-md">
                <form action="edit_event.php?id=<?php echo $event_id; ?>" method="post" class="space-y-4">
                    <div>
                        <label for="meet_id" class="block font-bold">Meet</label>
                        <select name="meet_id" id="meet_id" class="w-full p-2 border rounded mt-1" required>
                            <?php while($meet = $meets->fetch_assoc()): ?>
                                <option value="<?php echo $meet['meet_id']; ?>" <?php echo ($meet['meet_id'] == $event['meet_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($meet['meet_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label for="event_name" class="block font-bold">Event Name</label>
                        <input type="text" name="event_name" id="event_name" value="<?php echo htmlspecialchars($event['event_name']); ?>" class="w-full p-2 border rounded mt-1" required>
                    </div>
                    <div>
                        <label for="description" class="block font-bold">Description</label>
                        <textarea name="description" id="description" class="w-full p-2 border rounded mt-1"><?php echo htmlspecialchars($event['description']); ?></textarea>
                    </div>
                    <div>
                        <label for="event_date" class="block font-bold">Event Date & Time</label>
                        <input type="datetime-local" name="event_date" id="event_date" value="<?php echo !empty($event['event_date']) ? date('Y-m-d\TH:i', strtotime($event['event_date'])) : ''; ?>" class="w-full p-2 border rounded mt-1" required>
                    </div>
                     <div>
                        <label for="venue" class="block font-bold">Venue</label>
                        <input type="text" name="venue" id="venue" value="<?php echo htmlspecialchars($event['venue']); ?>" class="w-full p-2 border rounded mt-1" required>
                    </div>
                    <div>
                        <label for="event_type" class="block font-bold">Event Type</label>
                        <select name="event_type" id="event_type" class="w-full p-2 border rounded mt-1" required>
                            <option value="individual" <?php echo ($event['event_type'] == 'individual') ? 'selected' : ''; ?>>Individual</option>
                            <option value="team" <?php echo ($event['event_type'] == 'team') ? 'selected' : ''; ?>>Team</option>
                        </select>
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700">Save Changes</button>
                        <a href="manage_events.php" class="text-slate-500 ml-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div></body>
</html>