<?php
require_once '../config/db_connect.php';
require_once '../lib/certificate_generator.php';
require_once '../lib/email_sender.php';

// Security Check
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

$feedback_message = '';
$feedback_type = 'error';

// --- UPDATED: Handle form submission for SMART BULK EMAILING ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_participation_certs'])) {
    $email_event_id = filter_input(INPUT_POST, 'email_event_id', FILTER_VALIDATE_INT);

    if ($email_event_id) {
        // --- Smart Logic Start ---
        
        // 1. Get all winners for this event first and store them
        $winners = [];
        $winners_sql = "SELECT rw.user_id, rw.position FROM result_winners rw JOIN results r ON rw.result_id = r.result_id WHERE r.event_id = ?";
        $win_stmt = $conn->prepare($winners_sql);
        $win_stmt->bind_param("i", $email_event_id);
        $win_stmt->execute();
        $result = $win_stmt->get_result();
        while($row = $result->fetch_assoc()) {
            $winners[$row['user_id']] = $row['position']; // Key by user_id for easy lookup
        }
        $win_stmt->close();

        // 2. Get all attendees who were checked-in AND have not received a certificate email yet
        $attendees_sql = "SELECT r.student_id, r.registration_id, u.full_name, u.email 
                          FROM registrations r
                          JOIN users u ON r.student_id = u.student_id
                          WHERE r.event_id = ? AND r.attendance_marked = 1 AND r.certificate_email_sent = 0";
        
        $stmt = $conn->prepare($attendees_sql);
        $stmt->bind_param("i", $email_event_id);
        $stmt->execute();
        $attendees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($attendees)) {
            $feedback_message = "No attendees found for this event that are pending a certificate email.";
        } else {
            $success_count = 0;
            $fail_count = 0;
            
            $event_name_sql = "SELECT event_name FROM events WHERE event_id = ?";
            $event_stmt = $conn->prepare($event_name_sql);
            $event_stmt->bind_param("i", $email_event_id);
            $event_stmt->execute();
            $event_name = $event_stmt->get_result()->fetch_assoc()['event_name'];
            $event_stmt->close();

            // 3. Loop through attendees and send the correct certificate type
            foreach ($attendees as $attendee) {
                $user_id = $attendee['student_id'];
                $cert_type = 'Participation';
                $rank_text = '';
                $subject = "Your Certificate of Participation for " . $event_name;
                $body = "Dear " . $attendee['full_name'] . ",<br><br>Thank you for your active participation in the event: <strong>" . $event_name . "</strong>.<br><br>Please find your certificate attached.<br><br>Best Regards,<br>The Event Committee";

                // Check if this attendee is a winner
                if (isset($winners[$user_id])) {
                    $cert_type = 'Winner';
                    $rank_num = $winners[$user_id];
                    $rank_text = ($rank_num == 1) ? '1st Place' : (($rank_num == 2) ? '2nd Place' : '3rd Place');
                    $subject = "Congratulations! You're a Winner in " . $event_name;
                    $body = "Dear " . $attendee['full_name'] . ",<br><br>Congratulations on securing " . $rank_text . " in the event: <strong>" . $event_name . "</strong>.<br><br>Please find your winner's certificate attached.<br><br>Best Regards,<br>The Event Committee";
                }

                $certificate_path = generate_certificate($conn, $user_id, $email_event_id, $cert_type, $rank_text);

                if ($certificate_path) {
                    if (send_certificate_email($attendee['email'], $attendee['full_name'], $subject, $body, $certificate_path)) {
                        $success_count++;
                        $update_sql = "UPDATE registrations SET certificate_email_sent = 1 WHERE registration_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("i", $attendee['registration_id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                    } else {
                        $fail_count++;
                    }
                    unlink($certificate_path);
                } else {
                    $fail_count++;
                }
            }
            $feedback_type = 'success';
            $feedback_message = "Process complete. Sent $success_count certificates. Failed to send $fail_count.";
        }
    } else {
        $feedback_message = "Please select an event before sending certificates.";
    }
}

// --- Logic for filtering and displaying the table (remains the same) ---
$meet_filter = filter_input(INPUT_GET, 'meet_filter', FILTER_VALIDATE_INT);
$event_filter = filter_input(INPUT_GET, 'event_filter', FILTER_VALIDATE_INT);
$meets_result = $conn->query("SELECT meet_id, meet_name FROM meets ORDER BY meet_name");
$events_result_for_filter = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_name");
$events_result_for_email = $conn->query("SELECT event_id, event_name FROM events ORDER BY event_name");

$sql = "SELECT u.student_id, u.full_name, u.roll_number, e.event_id, e.event_name, r.attendance_marked, r.certificate_email_sent,
               (SELECT redemption_id FROM lunch_redemptions WHERE user_id = u.student_id LIMIT 1) IS NOT NULL AS lunch_redeemed
        FROM registrations r
        JOIN users u ON r.student_id = u.student_id
        JOIN events e ON r.event_id = e.event_id";
$conditions = []; $params = []; $types = '';
if ($meet_filter) { $conditions[] = "e.meet_id = ?"; $params[] = $meet_filter; $types .= 'i'; }
if ($event_filter) { $conditions[] = "r.event_id = ?"; $params[] = $event_filter; $types .= 'i'; }
if (!empty($conditions)) { $sql .= " WHERE " . implode(' AND ', $conditions); }
$sql .= " ORDER BY u.full_name, e.event_name";
$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$participants_data = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Status</title>
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
            <h1 class="text-3xl font-bold text-slate-800 mb-6">Participant Status Tracker</h1>

            <?php if(!empty($feedback_message)): ?>
                <div class="p-3 rounded mb-6 text-center <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'; ?>">
                    <?php echo $feedback_message; ?>
                </div>
            <?php endif; ?>

            <!-- Bulk Email Section -->
            <div class="glass-panel p-6 rounded-lg shadow-md mb-8 border-l-4 border-blue-500">
                 <h2 class="text-xl font-bold mb-4">Bulk Email Certificates</h2>
                 <p class="text-slate-500 mb-4">Select an event to send certificates to all attendees who were marked "Checked-In" and haven't received one yet. Winners will automatically receive a winner certificate.</p>
                 <form action="participant_status.php" method="POST" class="grid md:grid-cols-3 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label for="email_event_id" class="block text-sm font-medium text-slate-600">Select Event</label>
                        <select name="email_event_id" id="email_event_id" class="mt-1 block w-full p-2 border border-slate-200 rounded-md" required>
                            <option value="">-- Choose an Event --</option>
                            <?php while($event = $events_result_for_email->fetch_assoc()): ?>
                                <option value="<?php echo $event['event_id']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" name="send_participation_certs" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700" onclick="return confirm('Are you sure you want to email certificates? This will only send to those pending.');">Send Certificates</button>
                    </div>
                 </form>
            </div>

            <!-- Filter Form -->
            <div class="glass-panel p-4 rounded-lg shadow-md mb-8">
                 <h2 class="text-xl font-bold mb-4">Filter Participants</h2>
                <form action="participant_status.php" method="GET" class="grid md:grid-cols-3 gap-4 items-center">
                    <div>
                        <label for="meet_filter" class="block text-sm font-medium text-slate-600">Filter by Meet</label>
                        <select name="meet_filter" id="meet_filter" class="mt-1 block w-full p-2 border border-slate-200 rounded-md" onchange="this.form.submit()">
                            <option value="">-- All Meets --</option>
                            <?php while($meet = $meets_result->fetch_assoc()): ?>
                                <option value="<?php echo $meet['meet_id']; ?>" <?php if($meet_filter == $meet['meet_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($meet['meet_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                     <div>
                        <label for="event_filter" class="block text-sm font-medium text-slate-600">Filter by Event</label>
                        <select name="event_filter" id="event_filter" class="mt-1 block w-full p-2 border border-slate-200 rounded-md" onchange="this.form.submit()">
                            <option value="">-- All Events --</option>
                            <?php while($event = $events_result_for_filter->fetch_assoc()): ?>
                                <option value="<?php echo $event['event_id']; ?>" <?php if($event_filter == $event['event_id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($event['event_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mt-5">
                        <a href="participant_status.php" class="w-full block text-center bg-gray-500 text-slate-900 py-2 px-4 rounded hover:bg-gray-600">Clear Filters</a>
                    </div>
                </form>
            </div>

            <!-- Participant Status Table -->
            <div class="glass-panel p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4">Participant List</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-50 border border-slate-200">
                            <tr>
                                <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Participant</th>
                                <th class="p-3 text-left text-xs font-medium text-slate-500 uppercase">Event</th>
                                <th class="p-3 text-center text-xs font-medium text-slate-500 uppercase">Attendance</th>
                                <th class="p-3 text-center text-xs font-medium text-slate-500 uppercase">Lunch Status</th>
                                <th class="p-3 text-center text-xs font-medium text-slate-500 uppercase">Email Status</th>
                                <th class="p-3 text-center text-xs font-medium text-slate-500 uppercase">Certificate</th>
                            </tr>
                        </thead>
                        <tbody class="glass-panel divide-y divide-gray-200">
                            <?php if ($participants_data && $participants_data->num_rows > 0): ?>
                                <?php while($row = $participants_data->fetch_assoc()): ?>
                                <tr class="hover:bg-slate-50 border border-slate-200">
                                    <td class="p-3 font-medium text-slate-900"><?php echo htmlspecialchars($row['full_name']); ?><br><span class="text-xs text-slate-500"><?php echo htmlspecialchars($row['roll_number'] ?: ''); ?></span></td>
                                    <td class="p-3 text-slate-500"><?php echo htmlspecialchars($row['event_name']); ?></td>
                                    <td class="p-3 text-center">
                                        <?php if ($row['attendance_marked']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Checked-In</span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Not Yet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                         <?php if ($row['lunch_redeemed']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Redeemed</span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-50 text-slate-800">Not Used</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                         <?php if ($row['certificate_email_sent']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Sent</span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="download_certificate.php?user_id=<?php echo $row['student_id']; ?>&event_id=<?php echo $row['event_id']; ?>" class="text-blue-600 hover:underline text-sm font-medium">Download</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center p-4 text-slate-500">No participants match the current filter.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div></body>
</html>