<?php
require_once '../config/db_connect.php';

// --- NEW UNIFIED SECURITY CHECK ---
// It now checks for the generic "user_loggedin" session.
if(!isset($_SESSION["user_loggedin"]) || $_SESSION["user_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

// Hide deprecated warnings from the QR code library
error_reporting(E_ALL & ~E_DEPRECATED);

// Include the QR Code library.
$qrlib_path = '../lib/php-qrcode/qrlib.php';
if (!file_exists($qrlib_path)) {
    die("Error: QR Code library not found. Please follow installation instructions.");
}
require_once $qrlib_path;

// Prepare the directory for storing generated QR codes
$qr_temp_dir = 'qrcodes/';
if (!file_exists($qr_temp_dir)) {
    mkdir($qr_temp_dir, 0777, true);
}

// Use the new session variable for the user's ID
$user_id = $_SESSION['user_id'];

// --- Fetch all events the user is registered for ---
$sql = "SELECT r.qr_code_data, e.event_name, e.event_date, e.venue 
        FROM registrations r 
        JOIN events e ON r.event_id = e.event_id 
        WHERE r.student_id = ?
        ORDER BY e.event_date ASC";

$registrations = [];
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $registrations[] = $row;
    }
    $stmt->close();
}

// --- Generate Lunch Token QR if applicable ---
$has_registrations = count($registrations) > 0;
$lunch_qr_file = '';
if ($has_registrations) {
    // Use the new session variable for the user's name
    $lunch_qr_data = "LUNCH_TOKEN;SID=" . $user_id . ";NAME=" . rawurlencode($_SESSION['user_name']);
    $lunch_qr_filename = 'lunch_sid_' . $user_id . '.png';
    $lunch_qr_file = $qr_temp_dir . $lunch_qr_filename;
    
    QRcode::png($lunch_qr_data, $lunch_qr_file, QR_ECLEVEL_L, 5);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
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
    <nav class="glass-panel shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center text-xl font-bold text-indigo-600">User Portal</div>
                <div class="flex items-center">
                    <a href="browse_events.php" class="text-slate-600 hover:text-indigo-600 px-3 py-2 rounded-md text-sm font-medium">Browse Events</a>
                    <a href="logout.php" class="ml-4 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-md text-sm font-medium">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Use the new session variable for the user's name -->
        <h1 class="text-3xl font-bold text-slate-800 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <p class="text-slate-500 mb-8">This is your central hub. Here you can find all your QR codes for event entry and lunch.</p>
        
        <?php if($has_registrations): ?>
        <div class="bg-green-100 border-l-4 border-green-500 p-6 rounded-r-lg shadow-md mb-8">
            <h2 class="text-2xl font-bold mb-4 text-green-800">Your Universal Lunch Token</h2>
            <p class="mb-4 text-green-700">Show this QR code at the food counter to redeem your lunch. This token is valid only once.</p>
            <div class="flex justify-center md:justify-start">
                 <img src="<?php echo $lunch_qr_file; ?>?t=<?php echo time(); ?>" alt="Lunch Token QR Code" class="border-4 border-white rounded-lg shadow-lg">
            </div>
        </div>
        <?php endif; ?>

        <div class="glass-panel p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold text-slate-800 mb-4">Your Registered Events</h2>
            <?php if(empty($registrations)): ?>
                <div class="text-center py-12">
                    <p class="text-slate-500">You haven't registered for any events yet.</p>
                    <a href="browse_events.php" class="mt-4 inline-block bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">Browse & Register for Events</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach($registrations as $reg): 
                        $qr_filename = 'event_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $reg['qr_code_data']) . '.png';
                        $qr_code_file_path = $qr_temp_dir . $qr_filename;
                        QRcode::png($reg['qr_code_data'], $qr_code_file_path, QR_ECLEVEL_L, 4);
                    ?>
                    <div class="border border-slate-100 p-4 rounded-lg flex flex-col items-center text-center shadow-sm">
                        <h3 class="font-bold text-lg text-slate-900"><?php echo htmlspecialchars($reg['event_name']); ?></h3>
                        <p class="text-sm text-slate-500 mt-1"><?php echo htmlspecialchars($reg['venue']); ?></p>
                        <p class="text-sm text-slate-500"><?php echo date("D, M j, Y - g:i A", strtotime($reg['event_date'])); ?></p>
                        <img src="<?php echo $qr_code_file_path; ?>?t=<?php echo time(); ?>" alt="Event Entry QR Code" class="mt-4 border-2 border-slate-200 p-1 rounded-md">
                        <p class="text-xs text-center mt-2 font-semibold">Event Entry Pass</p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div></body>
</html>