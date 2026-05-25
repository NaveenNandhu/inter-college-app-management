<?php
require_once '../config/db_connect.php';
require_once '../lib/certificate_generator.php';
require_once '../lib/email_sender.php';

// Security Check
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: ../auth.php?action=login");
    exit;
}
$admin_id = $_SESSION['admin_id'];

$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    header("location: release_results.php");
    exit;
}

$feedback_message = '';
$feedback_type = 'error';

// --- Handle form submission for MULTIPLE winners ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();

    try {
        // Find or create the main result entry
        $result_id = null;
        $check_sql = "SELECT result_id FROM results WHERE event_id = ?";
        if ($check_stmt = $conn->prepare($check_sql)) {
            $check_stmt->bind_param("i", $event_id);
            $check_stmt->execute();
            $check_stmt->bind_result($result_id);
            $check_stmt->fetch();
            $check_stmt->close();
        }

        if (!$result_id) {
            $insert_result_sql = "INSERT INTO results (event_id, submitted_by_coordinator_id) VALUES (?, NULL)";
            $insert_stmt = $conn->prepare($insert_result_sql);
            $insert_stmt->bind_param("i", $event_id);
            $insert_stmt->execute();
            $result_id = $insert_stmt->insert_id;
            $insert_stmt->close();
        }

        // Clear old winners and insert new ones
        $delete_winners_sql = "DELETE FROM result_winners WHERE result_id = ?";
        $delete_stmt = $conn->prepare($delete_winners_sql);
        $delete_stmt->bind_param("i", $result_id);
        $delete_stmt->execute();
        $delete_stmt->close();

        $positions = [
            1 => $_POST['first_place_users'] ?? [],
            2 => $_POST['second_place_users'] ?? [],
            3 => $_POST['third_place_users'] ?? []
        ];

        $insert_winner_sql = "INSERT INTO result_winners (result_id, user_id, position) VALUES (?, ?, ?)";
        $winner_stmt = $conn->prepare($insert_winner_sql);

        $all_winner_ids = [];
        foreach ($positions as $position => $user_ids) {
            foreach ($user_ids as $user_id) {
                $winner_stmt->bind_param("iii", $result_id, $user_id, $position);
                $winner_stmt->execute();
                if (!in_array($user_id, $all_winner_ids)) {
                    $all_winner_ids[] = $user_id;
                }
            }
        }
        $winner_stmt->close();

        $conn->commit();
        $feedback_type = 'success';
        $feedback_message = "Results saved successfully!";

        // --- EFFICIENT EMAIL LOGIC ---
        if (!empty($all_winner_ids)) {
            // 1. Fetch event name once
            $event_name_res = $conn->query("SELECT event_name FROM events WHERE event_id = $event_id");
            $event_name = $event_name_res->fetch_assoc()['event_name'];

            // 2. Fetch all winners' details in a single query
            $id_placeholders = implode(',', array_fill(0, count($all_winner_ids), '?'));
            $user_info_sql = "SELECT student_id, full_name, email FROM users WHERE student_id IN ($id_placeholders)";
            $user_stmt = $conn->prepare($user_info_sql);
            $user_stmt->bind_param(str_repeat('i', count($all_winner_ids)), ...$all_winner_ids);
            $user_stmt->execute();
            $winners_data_result = $user_stmt->get_result();
            $winners_details = [];
            while ($row = $winners_data_result->fetch_assoc()) {
                $winners_details[$row['student_id']] = $row;
            }
            $user_stmt->close();

            // 3. Loop through winners and send emails
            foreach ($positions as $rank_num => $user_ids) {
                $rank_text = ($rank_num == 1) ? '1st Place' : (($rank_num == 2) ? '2nd Place' : '3rd Place');
                foreach ($user_ids as $user_id) {
                    $winner = $winners_details[$user_id];
                    $certificate_path = generate_certificate($conn, $user_id, $event_id, 'Winner', $rank_text);
                    if ($certificate_path) {
                        $subject = "Congratulations! You're a Winner!";
                        $body = "Dear " . $winner['full_name'] . ",<br><br>Congratulations on securing " . $rank_text . " in the event: <strong>" . $event_name . "</strong>.<br><br>Please find your certificate attached.<br><br>Best Regards,<br>The Event Committee";
                        if (send_certificate_email($winner['email'], $winner['full_name'], $subject, $body, $certificate_path)) {
                            $feedback_message .= "<br>Emailed certificate to " . $winner['full_name'] . ".";
                        } else {
                            $feedback_message .= "<br><span class='text-red-500'>Failed to email " . $winner['full_name'] . ".</span>";
                        }
                        unlink($certificate_path);
                    }
                }
            }
        }
    } catch (Exception $e) {
        $conn->rollback();
        $feedback_message = "An error occurred: " . $e->getMessage();
    }
}

// --- Fetch data for the page (remains the same) ---
$event_name_res = $conn->query("SELECT event_name FROM events WHERE event_id = $event_id");
$event_name = $event_name_res ? $event_name_res->fetch_assoc()['event_name'] : 'Unknown Event';

$participants_sql = "SELECT u.student_id, u.full_name FROM users u JOIN registrations r ON u.student_id = r.student_id WHERE r.event_id = ? AND r.attendance_marked = 1 ORDER BY u.full_name";
$part_stmt = $conn->prepare($participants_sql);
$part_stmt->bind_param("i", $event_id);
$part_stmt->execute();
$participants = $part_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$part_stmt->close();

$current_winners = [1 => [], 2 => [], 3 => []];
$winners_sql = "SELECT rw.user_id, rw.position FROM result_winners rw JOIN results r ON rw.result_id = r.result_id WHERE r.event_id = ?";
$win_stmt = $conn->prepare($winners_sql);
$win_stmt->bind_param("i", $event_id);
$win_stmt->execute();
$result = $win_stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $current_winners[$row['position']][] = $row['user_id'];
}
$win_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Team Results</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }

        /* Reveal Animations */
        @keyframes reveal {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-reveal {
            animation: reveal 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes float {
            0% {
                transform: translateY(0px) rotate(0deg);
            }

            50% {
                transform: translateY(-20px) rotate(5deg);
            }

            100% {
                transform: translateY(0px) rotate(0deg);
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animate-float-delayed {
            animation: float 8s ease-in-out infinite;
            animation-delay: 2s;
        }

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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
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
        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]),
        select,
        textarea {
            background: #ffffff !important;
            border: 1px solid #cbd5e1 !important;
            color: #1e293b !important;
            transition: all 0.3s ease !important;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.02) !important;
        }

        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]):hover,
        select:hover,
        textarea:hover {
            border-color: #6366f1 !important;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.05) !important;
        }

        input:not([type="checkbox"]):not([type="submit"]):not([type="hidden"]):focus,
        select:focus,
        textarea:focus {
            outline: none !important;
            background: #ffffff !important;
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2) !important;
        }

        input::placeholder,
        textarea::placeholder {
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
        <div class="flex h-screen">
            <?php include '_sidebar.php'; ?>
            <main class="flex-1 p-10 overflow-y-auto">
                <div class="glass-panel p-8 rounded-lg shadow-md max-w-4xl mx-auto">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h1 class="text-3xl font-bold text-slate-800">Edit/Release Team Results</h1>
                            <p class="text-xl text-indigo-600 font-semibold">
                                <?php echo htmlspecialchars($event_name); ?></p>
                        </div>
                        <a href="release_results.php" class="text-blue-500 hover:underline">&larr; Back to Event
                            Selection</a>
                    </div>

                    <?php if (!empty($feedback_message)): ?>
                        <div
                            class="p-3 rounded mb-6 text-center <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo $feedback_message; ?>
                        </div>
                    <?php endif; ?>

                    <form action="edit_results.php?event_id=<?php echo $event_id; ?>" method="post" class="space-y-8">
                        <?php if (empty($participants)): ?>
                            <p class="text-center text-slate-500 py-10">No participants registered for this event.</p>
                        <?php else: ?>
                            <!-- 1st Place Multi-Select -->
                            <div>
                                <label for="first_place_users" class="block text-lg font-medium text-slate-600">🥇 First
                                    Place Winners</label>
                                <p class="text-sm text-slate-500">Hold Ctrl (or Cmd on Mac) to select multiple students for
                                    a team prize.</p>
                                <select name="first_place_users[]" id="first_place_users" multiple
                                    class="mt-1 block w-full p-2 border rounded-md h-40">
                                    <?php foreach ($participants as $p): ?>
                                        <option value="<?php echo $p['student_id']; ?>" <?php echo in_array($p['student_id'], $current_winners[1]) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- 2nd Place Multi-Select -->
                            <div>
                                <label for="second_place_users" class="block text-lg font-medium text-slate-600">🥈 Second
                                    Place Winners</label>
                                <select name="second_place_users[]" id="second_place_users" multiple
                                    class="mt-1 block w-full p-2 border rounded-md h-40">
                                    <?php foreach ($participants as $p): ?>
                                        <option value="<?php echo $p['student_id']; ?>" <?php echo in_array($p['student_id'], $current_winners[2]) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- 3rd Place Multi-Select -->
                            <div>
                                <label for="third_place_users" class="block text-lg font-medium text-slate-600">🥉 Third
                                    Place Winners</label>
                                <select name="third_place_users[]" id="third_place_users" multiple
                                    class="mt-1 block w-full p-2 border rounded-md h-40">
                                    <?php foreach ($participants as $p): ?>
                                        <option value="<?php echo $p['student_id']; ?>" <?php echo in_array($p['student_id'], $current_winners[3]) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="pt-4">
                                <button type="submit"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-md text-lg">
                                    Save Results & Email Winners
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </main>
        </div>
    </div>
</body>

</html>