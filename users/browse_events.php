<?php
require_once '../config/db_connect.php';

// Security Check
if (!isset($_SESSION["user_loggedin"]) || $_SESSION["user_loggedin"] !== true) {
    header("location: ../auth.php?action=login");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get IDs of events the user has already registered for
$registered_event_ids = [];
$reg_sql = "SELECT event_id FROM registrations WHERE student_id = ?";
if ($reg_stmt = $conn->prepare($reg_sql)) {
    $reg_stmt->bind_param("i", $user_id);
    if ($reg_stmt->execute()) {
        $reg_result = $reg_stmt->get_result();
        while ($row = $reg_result->fetch_assoc()) {
            $registered_event_ids[] = $row['event_id'];
        }
    }
    $reg_stmt->close();
}

// Fetch all available, upcoming events. This query will now work correctly.
$events_sql = "SELECT e.event_id, e.event_name, e.event_date, m.meet_name, e.venue, e.description, e.event_type 
               FROM events e 
               JOIN meets m ON e.meet_id = m.meet_id 
               WHERE e.event_date > NOW() 
               ORDER BY e.event_date ASC";
$events = $conn->query($events_sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Events</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] rounded-full bg-purple-300/30 blur-[150px]">
        </div>
        <div class="absolute top-[40%] left-[50%] w-[40%] h-[40%] rounded-full bg-pink-300/30 blur-[120px]"></div>
    </div>
    <!-- Main Content Wrapper to keep it above gradients -->
    <div class="relative z-10 w-full h-full">
        <!-- Navbar -->
        <nav class="glass-panel shadow-md">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center text-xl font-bold text-indigo-600">User Portal</div>
                    <div class="flex items-center"><a href="dashboard.php"
                            class="text-slate-600 hover:text-indigo-600 px-3 py-2">My Dashboard</a><a href="logout.php"
                            class="ml-4 bg-red-500 text-white px-3 py-2 rounded-md">Logout</a></div>
                </div>
            </div>
        </nav>
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-slate-800 mb-6">Browse & Register for Events</h1>

            <div id="feedback-message" class="hidden p-3 rounded mb-4 text-center"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if ($events && $events->num_rows > 0): ?>
                    <?php while ($event = $events->fetch_assoc()):
                        $is_registered = in_array($event['event_id'], $registered_event_ids);
                        ?>
                        <div class="glass-panel p-6 rounded-lg shadow-md flex flex-col justify-between">
                            <div>
                                <span
                                    class="text-sm font-semibold text-indigo-600 uppercase"><?php echo htmlspecialchars($event['meet_name']); ?></span>
                                <h3 class="text-xl font-bold mt-1 text-slate-900">
                                    <?php echo htmlspecialchars($event['event_name']); ?></h3>
                                <p class="text-slate-500 mt-2 text-sm h-16 overflow-hidden">
                                    <?php echo htmlspecialchars($event['description']); ?></p>
                                <div class="mt-4 text-sm space-y-1">
                                    <p><span class="font-semibold">When:</span>
                                        <?php echo date("D, M j, Y, g:i A", strtotime($event['event_date'])); ?></p>
                                    <p><span class="font-semibold">Where:</span>
                                        <?php echo htmlspecialchars($event['venue']); ?></p>
                                    <p><span class="font-semibold">Type:</span> <span
                                            class="capitalize bg-indigo-500 text-white px-2 py-1 rounded-full"><?php echo htmlspecialchars($event['event_type']); ?></span>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-6">
                                <button <?php if ($is_registered)
                                    echo 'disabled'; ?>
                                    onclick="registerForEvent(<?php echo $event['event_id']; ?>, this)"
                                    class="w-full text-white font-bold py-2 px-4 rounded transition duration-300 <?php echo $is_registered ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-500 hover:bg-green-600'; ?>">
                                    <?php echo $is_registered ? '✓ Already Registered' : 'Register Now'; ?>
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="col-span-full text-center text-slate-500 py-10">No upcoming events found at the moment. Please
                        check back later!</p>
                <?php endif; ?>
            </div>
        </main>
        <script>
            function registerForEvent(eventId, buttonElement) {
                buttonElement.disabled = true;
                buttonElement.textContent = 'Registering...';
                fetch('../api/register_for_event.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', },
                    body: JSON.stringify({ event_id: eventId }),
                })
                    .then(response => response.json())
                    .then(data => {
                        const feedbackDiv = document.getElementById('feedback-message');
                        feedbackDiv.className = 'p-3 rounded mb-4 text-center';
                        if (data.success) {
                            feedbackDiv.textContent = data.message;
                            feedbackDiv.classList.add('bg-green-100', 'text-green-800');
                            buttonElement.textContent = '✓ Already Registered';
                            buttonElement.classList.add('bg-gray-400', 'cursor-not-allowed');
                        } else {
                            feedbackDiv.textContent = 'Error: ' + data.message;
                            feedbackDiv.classList.add('bg-red-100', 'text-red-800');
                            buttonElement.disabled = false;
                            buttonElement.textContent = 'Register Now';
                        }
                        feedbackDiv.classList.remove('hidden');
                    })
                    .catch(error => { /* ... */ });
            }
        </script>
    </div>
</body>

</html>