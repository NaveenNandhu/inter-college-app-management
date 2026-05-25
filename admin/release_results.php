<?php
require_once '../config/db_connect.php';

// Security Check
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

// Fetch all meets for the first dropdown
$meets_result = $conn->query("SELECT meet_id, meet_name FROM meets ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Release Event Results</title>
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
            <h1 class="text-3xl font-bold text-slate-800 mb-6">Release Event Results</h1>
            <p class="text-slate-500 mb-8">First, select a meet, then select the specific event to manage its results.</p>
            
            <div class="glass-panel p-6 rounded-lg shadow-md max-w-lg mx-auto">
                <form action="edit_results.php" method="GET" class="space-y-6">
                    <!-- Step 1: Select Meet -->
                    <div>
                        <label for="meet_filter" class="block text-lg font-medium text-slate-600">Step 1: Select a Meet</label>
                        <select name="meet_filter" id="meet_filter" class="mt-1 block w-full p-3 border border-slate-200 rounded-md shadow-sm" required>
                            <option value="">-- Choose a Meet --</option>
                            <?php while($meet = $meets_result->fetch_assoc()): ?>
                                <option value="<?php echo $meet['meet_id']; ?>">
                                    <?php echo htmlspecialchars($meet['meet_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Step 2: Select Event (Populated by JavaScript) -->
                    <div>
                        <label for="event_id" class="block text-lg font-medium text-slate-600">Step 2: Select an Event</label>
                        <select name="event_id" id="event_id" class="mt-1 block w-full p-3 border border-slate-200 rounded-md shadow-sm" required disabled>
                            <option value="">-- First Select a Meet --</option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit" id="submit_button" class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-md text-lg opacity-50 cursor-not-allowed" disabled>
                            Manage Results
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const meetFilter = document.getElementById('meet_filter');
        const eventSelect = document.getElementById('event_id');
        const submitButton = document.getElementById('submit_button');

        meetFilter.addEventListener('change', function() {
            const meetId = this.value;

            // Reset and disable the event dropdown
            eventSelect.innerHTML = '<option value="">Loading...</option>';
            eventSelect.disabled = true;
            submitButton.disabled = true;
            submitButton.classList.add('opacity-50', 'cursor-not-allowed');

            if (!meetId) {
                eventSelect.innerHTML = '<option value="">-- First Select a Meet --</option>';
                return;
            }

            // Fetch the events for the selected meet from our new API
            fetch(`../api/get_events_by_meet.php?meet_id=${meetId}`)
                .then(response => response.json())
                .then(data => {
                    // Clear the loading message
                    eventSelect.innerHTML = '<option value="">-- Choose an Event --</option>';

                    if (data.length > 0) {
                        data.forEach(event => {
                            const option = document.createElement('option');
                            option.value = event.event_id;
                            option.textContent = event.event_name;
                            eventSelect.appendChild(option);
                        });
                        // Enable the dropdown
                        eventSelect.disabled = false;
                    } else {
                        eventSelect.innerHTML = '<option value="">-- No events found for this meet --</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching events:', error);
                    eventSelect.innerHTML = '<option value="">-- Error loading events --</option>';
                });
        });

        eventSelect.addEventListener('change', function() {
            // Enable the submit button only if a valid event is chosen
            if (this.value) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        });
    </script>
</div></body>
</html>