<?php
require_once '../config/db_connect.php';

// Security Check
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: ../auth.php?action=login");
    exit;
}

// Fetch Meets for the filter
$meets_result = $conn->query("SELECT meet_id, meet_name FROM meets ORDER BY created_at DESC");

// Get the selected meet and event
$meet_id = filter_input(INPUT_GET, 'meet_id', FILTER_VALIDATE_INT);
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

// Fetch Events for the selected meet
$events_result = null;
if ($meet_id) {
    $events_stmt = $conn->prepare("SELECT event_id, event_name FROM events WHERE meet_id = ? ORDER BY event_name ASC");
    $events_stmt->bind_param("i", $meet_id);
    $events_stmt->execute();
    $events_result = $events_stmt->get_result();
    $events_stmt->close();
}

$report_data = [];

// Determine which events to show in the report
$events_to_show = [];
if ($event_id) {
    $events_to_show[] = $event_id;
} else if ($meet_id) {
    // If only meet is selected, fetch all events for that meet
    $all_events_stmt = $conn->prepare("SELECT event_id FROM events WHERE meet_id = ?");
    $all_events_stmt->bind_param("i", $meet_id);
    $all_events_stmt->execute();
    $res = $all_events_stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $events_to_show[] = $row['event_id'];
    }
    $all_events_stmt->close();
}

// Fetch report data for the selected events
if (!empty($events_to_show)) {
    foreach ($events_to_show as $eid) {
        $event_data = [
            'details' => null,
            'coordinators' => [],
            'participants' => [],
            'winners' => [1 => [], 2 => [], 3 => []]
        ];

        // Event details
        $detail_stmt = $conn->prepare("SELECT event_name, event_date, venue, event_type FROM events WHERE event_id = ?");
        $detail_stmt->bind_param("i", $eid);
        $detail_stmt->execute();
        $event_data['details'] = $detail_stmt->get_result()->fetch_assoc();
        $detail_stmt->close();

        // Coordinators
        $coord_stmt = $conn->prepare("SELECT u.full_name, u.roll_number, u.email FROM event_assignments a JOIN users u ON a.user_id = u.student_id WHERE a.event_id = ?");
        $coord_stmt->bind_param("i", $eid);
        $coord_stmt->execute();
        $res = $coord_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $event_data['coordinators'][] = $row;
        }
        $coord_stmt->close();

        // Participants
        $part_stmt = $conn->prepare("SELECT u.full_name, u.roll_number, u.email, r.attendance_marked FROM registrations r JOIN users u ON r.student_id = u.student_id WHERE r.event_id = ? ORDER BY u.full_name");
        $part_stmt->bind_param("i", $eid);
        $part_stmt->execute();
        $res = $part_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $event_data['participants'][] = $row;
        }
        $part_stmt->close();

        // Winners
        $win_stmt = $conn->prepare("SELECT rw.position, u.full_name, u.roll_number FROM result_winners rw JOIN results r ON rw.result_id = r.result_id JOIN users u ON rw.user_id = u.student_id WHERE r.event_id = ? ORDER BY rw.position ASC");
        $win_stmt->bind_param("i", $eid);
        $win_stmt->execute();
        $res = $win_stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $event_data['winners'][$row['position']][] = $row;
        }
        $win_stmt->close();

        $report_data[$eid] = $event_data;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
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

        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08);
            color: #1e293b;
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

        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }

            .glass-panel {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }

            body {
                background: white !important;
            }

            .report-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="bg-slate-50 text-slate-800 min-h-screen relative overflow-x-hidden font-sans">
    <!-- Background Animated Gradients -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none no-print">
        <div class="absolute top-[-20%] left-[-10%] w-[50%] h-[50%] rounded-full bg-indigo-300/30 blur-[120px]"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[60%] h-[60%] rounded-full bg-purple-300/30 blur-[150px]">
        </div>
        <div class="absolute top-[40%] left-[50%] w-[40%] h-[40%] rounded-full bg-pink-300/30 blur-[120px]"></div>
    </div>

    <div class="relative z-10 w-full h-full">
        <div class="flex h-screen">
            <div class="no-print w-64 flex-shrink-0 h-full">
                <?php include '_sidebar.php'; ?>
            </div>

            <div class="flex-1 p-10 overflow-y-auto">
                <div class="flex justify-between items-center mb-6 no-print">
                    <h1 class="text-3xl font-bold text-slate-800">Comprehensive Reports</h1>
                    <?php if (!empty($report_data)): ?>
                        <button onclick="window.print()"
                            class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition">
                            Print / Save PDF
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Filter Form -->
                <div class="glass-panel p-6 rounded-lg shadow-md mb-8 no-print">
                    <form action="reports.php" method="GET" class="grid md:grid-cols-2 gap-4 items-end">
                        <div>
                            <label for="meet_id" class="block text-sm font-medium text-slate-600">Select Meet</label>
                            <select name="meet_id" id="meet_id"
                                class="mt-1 block w-full p-2 border border-slate-200 rounded-md"
                                onchange="this.form.submit()">
                                <option value="">-- Choose a Meet --</option>
                                <?php while ($meet = $meets_result->fetch_assoc()): ?>
                                    <option value="<?php echo $meet['meet_id']; ?>" <?php if ($meet_id == $meet['meet_id'])
                                           echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($meet['meet_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <?php if ($meet_id && $events_result): ?>
                            <div>
                                <label for="event_id" class="block text-sm font-medium text-slate-600">Select Event
                                    (Optional - All Events shown if unselected)</label>
                                <select name="event_id" id="event_id"
                                    class="mt-1 block w-full p-2 border border-slate-200 rounded-md"
                                    onchange="this.form.submit()">
                                    <option value="">-- All Events --</option>
                                    <?php while ($event = $events_result->fetch_assoc()): ?>
                                        <option value="<?php echo $event['event_id']; ?>" <?php if ($event_id == $event['event_id'])
                                               echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($event['event_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Report Content -->
                <?php if (empty($report_data)): ?>
                    <div class="glass-panel p-10 rounded-lg shadow-md text-center text-slate-500">
                        <p class="text-xl">Please select a Meet to view the report.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($report_data as $eid => $data): ?>
                        <div class="glass-panel p-8 rounded-lg shadow-md mb-10 report-section animate-reveal">
                            <div class="border-b-2 border-indigo-100 pb-4 mb-6">
                                <h2 class="text-3xl font-bold text-indigo-700 mb-2">
                                    <?php echo htmlspecialchars($data['details']['event_name']); ?></h2>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-slate-600">
                                    <p><strong>Date:</strong>
                                        <?php echo date("M j, Y, g:i A", strtotime($data['details']['event_date'])); ?></p>
                                    <p><strong>Venue:</strong> <?php echo htmlspecialchars($data['details']['venue']); ?></p>
                                    <p><strong>Type:</strong> <span
                                            class="capitalize"><?php echo htmlspecialchars($data['details']['event_type']); ?></span>
                                    </p>
                                </div>
                            </div>

                            <!-- Coordinators -->
                            <div class="mb-8">
                                <h3 class="text-xl font-bold text-slate-800 mb-4 border-l-4 border-indigo-500 pl-3">Assigned
                                    Coordinators</h3>
                                <?php if (empty($data['coordinators'])): ?>
                                    <p class="text-slate-500 italic">No coordinators assigned.</p>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>ID / Roll No</th>
                                                    <th>Email</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data['coordinators'] as $coord): ?>
                                                    <tr>
                                                        <td class="font-medium"><?php echo htmlspecialchars($coord['full_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($coord['roll_number']); ?></td>
                                                        <td><?php echo htmlspecialchars($coord['email']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Winners -->
                            <div class="mb-8">
                                <h3 class="text-xl font-bold text-slate-800 mb-4 border-l-4 border-green-500 pl-3">Event Winners
                                </h3>
                                <?php
                                $has_winners = !empty($data['winners'][1]) || !empty($data['winners'][2]) || !empty($data['winners'][3]);
                                if (!$has_winners):
                                    ?>
                                    <p class="text-slate-500 italic">Results not published yet.</p>
                                <?php else: ?>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <!-- First Place -->
                                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 shadow-sm">
                                            <h4 class="font-bold text-yellow-700 flex items-center mb-3">
                                                <span class="text-2xl mr-2">🥇</span> First Place
                                            </h4>
                                            <?php if (empty($data['winners'][1])): ?>
                                                <p class="text-sm text-slate-500 italic">-</p>
                                            <?php else: ?>
                                                <ul class="space-y-2">
                                                    <?php foreach ($data['winners'][1] as $w): ?>
                                                        <li class="font-medium text-slate-800">
                                                            <?php echo htmlspecialchars($w['full_name']); ?> <span
                                                                class="text-xs text-slate-500 ml-1">(<?php echo htmlspecialchars($w['roll_number']); ?>)</span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Second Place -->
                                        <div class="bg-slate-100 p-4 rounded-lg border border-slate-300 shadow-sm">
                                            <h4 class="font-bold text-slate-600 flex items-center mb-3">
                                                <span class="text-2xl mr-2">🥈</span> Second Place
                                            </h4>
                                            <?php if (empty($data['winners'][2])): ?>
                                                <p class="text-sm text-slate-500 italic">-</p>
                                            <?php else: ?>
                                                <ul class="space-y-2">
                                                    <?php foreach ($data['winners'][2] as $w): ?>
                                                        <li class="font-medium text-slate-800">
                                                            <?php echo htmlspecialchars($w['full_name']); ?> <span
                                                                class="text-xs text-slate-500 ml-1">(<?php echo htmlspecialchars($w['roll_number']); ?>)</span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Third Place -->
                                        <div class="bg-orange-50 p-4 rounded-lg border border-orange-200 shadow-sm">
                                            <h4 class="font-bold text-orange-700 flex items-center mb-3">
                                                <span class="text-2xl mr-2">🥉</span> Third Place
                                            </h4>
                                            <?php if (empty($data['winners'][3])): ?>
                                                <p class="text-sm text-slate-500 italic">-</p>
                                            <?php else: ?>
                                                <ul class="space-y-2">
                                                    <?php foreach ($data['winners'][3] as $w): ?>
                                                        <li class="font-medium text-slate-800">
                                                            <?php echo htmlspecialchars($w['full_name']); ?> <span
                                                                class="text-xs text-slate-500 ml-1">(<?php echo htmlspecialchars($w['roll_number']); ?>)</span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Participants -->
                            <div>
                                <h3 class="text-xl font-bold text-slate-800 mb-4 border-l-4 border-blue-500 pl-3">Participant
                                    List (<?php echo count($data['participants']); ?>)</h3>
                                <?php if (empty($data['participants'])): ?>
                                    <p class="text-slate-500 italic">No participants registered.</p>
                                <?php else: ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Roll Number</th>
                                                    <th>Email</th>
                                                    <th>Attendance</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($data['participants'] as $part): ?>
                                                    <tr>
                                                        <td class="font-medium"><?php echo htmlspecialchars($part['full_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($part['roll_number']); ?></td>
                                                        <td><?php echo htmlspecialchars($part['email']); ?></td>
                                                        <td>
                                                            <?php if ($part['attendance_marked']): ?>
                                                                <span
                                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Present</span>
                                                            <?php else: ?>
                                                                <span
                                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-600">Absent</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>

</html>