<?php
require_once '../config/db_connect.php';

// Security Check
if(!isset($_SESSION["user_loggedin"]) || $_SESSION["user_loggedin"] !== true || $_SESSION['user_type'] !== 'coordinator'){
    header("location: ../auth.php?action=login");
    exit;
}

$coordinator_id = $_SESSION['user_id'];

// Fetch all events assigned to this coordinator
$sql = "SELECT e.event_id, e.event_name, e.event_date, e.venue, m.meet_name
        FROM event_assignments a
        JOIN events e ON a.event_id = e.event_id
        JOIN meets m ON e.meet_id = m.meet_id
        WHERE a.user_id = ?
        ORDER BY e.event_date ASC";

$assigned_events = [];
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $coordinator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $assigned_events[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard</title>
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
                <div class="flex items-center text-xl font-bold text-blue-600">Coordinator Portal</div>
                <div class="flex items-center">
                    <a href="../users/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-md text-sm font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <p class="text-slate-500 mb-8">Here are the events you have been assigned to coordinate.</p>

        <div class="glass-panel p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-slate-800 mb-4">Your Assigned Events</h2>
            <div class="space-y-4">
                <?php if(empty($assigned_events)): ?>
                    <p class="text-center text-slate-500 py-10">You have not been assigned to any events yet.</p>
                <?php else: ?>
                    <?php foreach($assigned_events as $event): ?>
                        <div class="border border-slate-100 p-4 rounded-lg flex flex-col md:flex-row justify-between items-center hover:bg-slate-50 border border-slate-200">
                            <div>
                                <span class="text-sm font-semibold text-indigo-600 uppercase"><?php echo htmlspecialchars($event['meet_name']); ?></span>
                                <h3 class="text-xl font-bold text-slate-900"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                <p class="text-sm text-slate-500 mt-1">
                                    <?php echo htmlspecialchars($event['venue']); ?> - 
                                    <span class="font-medium"><?php echo date("D, M j, Y - g:i A", strtotime($event['event_date'])); ?></span>
                                </p>
                            </div>
                            <div class="flex flex-wrap justify-end space-x-2 mt-4 md:mt-0">
                                <!-- New "View Participants" Button -->
                                <a href="view_participants.php?event_id=<?php echo $event['event_id']; ?>" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded transition duration-300">
                                    View Participants
                                </a>
                                <button type="button" onclick="openScanner()" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2 px-4 rounded transition duration-300 shadow-[0_0_15px_rgba(37,99,235,0.3)] hover:shadow-[0_0_25px_rgba(37,99,235,0.5)]">
                                    Scan QR Codes
                                </button>
                                <a href="submit_results.php?event_id=<?php echo $event['event_id']; ?>" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded transition duration-300">
                                    Submit Results
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Scanner Modal (Hidden by Default) -->
    <div id="scannerModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-white border-t border-slate-200 backdrop-blur-sm p-4 transition-opacity duration-300">
        <div class="glass-panel w-full max-w-2xl p-6 rounded-3xl shadow-[0_0_50px_rgba(0,0,0,0.8)] relative border border-slate-200">
            
            <!-- Close Button -->
            <button type="button" onclick="closeScanner()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors bg-slate-50 border border-slate-200 hover:bg-red-500/80 p-2 rounded-full shadow-lg z-50">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-blue-200/40 border border-blue-500/30 flex items-center justify-center text-blue-400 shadow-[0_0_15px_rgba(59,130,246,0.5)]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-wide">Live Scanner Portal</h2>
            </div>
            
            <p class="text-slate-500 mb-6 text-sm ml-14">Verify participant entry passes and lunch tokens instantly using your device camera.</p>
            
            <!-- UI Overrides for html5-qrcode -->
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
            
            <div id="qr-reader" class="mx-auto shadow-[0_0_40px_rgba(0,0,0,0.4)]"></div>
            
            <div id="qr-reader-results" class="mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-semibold text-sm tracking-wide text-slate-600 border border-slate-100 backdrop-blur-md">
                Awaiting camera feed. Position the QR code within the frame above.
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode/html5-qrcode.min.js"></script>
<script>
    let html5QrcodeScanner = null;

    function openScanner() {
        const modal = document.getElementById("scannerModal");
        modal.classList.remove("hidden");
        
        const resultDiv = document.getElementById("qr-reader-results");
        resultDiv.textContent = "Awaiting camera feed. Position the QR code within the frame above.";
        resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-semibold text-sm tracking-wide text-gray-300 border border-white/5 backdrop-blur-md";

        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader",
                { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 },
                /* verbose= */ false
            );
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }
    }

    function closeScanner() {
        document.getElementById("scannerModal").classList.add("hidden");
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                html5QrcodeScanner = null;
                document.getElementById("qr-reader").innerHTML = ""; 
            }).catch(e => console.error("Could not stop scanner.", e));
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        const resultDiv = document.getElementById("qr-reader-results");
        resultDiv.textContent = "Processing verification...";
        resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-semibold text-sm bg-yellow-400/10 border border-yellow-400/30 text-yellow-300 backdrop-blur-md shadow-[0_0_15px_rgba(250,204,21,0.2)] animate-pulse";

        // Optional: Pause scanner briefly
        if (html5QrcodeScanner) html5QrcodeScanner.pause();

        fetch("../api/verify_qr.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ qr_data: decodedText })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.textContent = `SUCCESS: ${data.message}`;
                resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-bold text-sm bg-green-500/10 border border-green-500/40 text-green-400 backdrop-blur-md shadow-[0_0_20px_rgba(34,197,94,0.3)]";
                let audio = new Audio("https://cdn.pixabay.com/audio/2022/03/15/audio_2c64e9a052.mp3");
                audio.volume = 0.5; audio.play().catch(e => console.log(e));
                
                setTimeout(() => { if(html5QrcodeScanner) html5QrcodeScanner.resume(); }, 2000);
            } else {
                resultDiv.textContent = `FAILED: ${data.message}`;
                resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-bold text-sm bg-red-500/10 border border-red-500/50 text-red-500 backdrop-blur-md shadow-[0_0_20px_rgba(239,68,68,0.4)]";
                let audio = new Audio("https://cdn.pixabay.com/audio/2022/03/10/audio_c81c107383.mp3");
                audio.volume = 0.5; audio.play().catch(e => console.log(e));
                
                setTimeout(() => { if(html5QrcodeScanner) html5QrcodeScanner.resume(); }, 2000);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            resultDiv.textContent = "Network Error: Could not verify with the server.";
            resultDiv.className = "mt-6 mx-auto max-w-lg p-4 rounded-xl text-center font-bold text-sm bg-red-500/10 border border-red-500/50 text-red-500 backdrop-blur-md shadow-[0_0_20px_rgba(239,68,68,0.4)]";
            setTimeout(() => { if(html5QrcodeScanner) html5QrcodeScanner.resume(); }, 3000);
        });
    }

    function onScanFailure(error) {
        // Handle quietly
    }
</script>
</body>
</html>