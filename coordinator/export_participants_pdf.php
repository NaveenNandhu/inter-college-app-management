<?php
require_once '../config/db_connect.php';
require_once '../lib/fpdf/fpdf.php'; // Include the FPDF library

// Security check
if(!isset($_SESSION["user_loggedin"]) || $_SESSION["user_loggedin"] !== true || $_SESSION['user_type'] !== 'coordinator'){
    header("location: ../auth.php?action=login");
    exit;
}

// Get and validate the event_id from the URL
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
if (!$event_id) {
    die("Invalid Event ID.");
}

// --- Fetch necessary data ---
// Get event name
$event_sql = "SELECT event_name FROM events WHERE event_id = ?";
$event_stmt = $conn->prepare($event_sql);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_stmt->bind_result($event_name);
$event_stmt->fetch();
$event_stmt->close();

if (empty($event_name)) {
    die("Event not found.");
}

// Get participant list
$participants_sql = "SELECT u.full_name, u.roll_number, u.college_name 
                     FROM registrations r
                     JOIN users u ON r.student_id = u.student_id
                     WHERE r.event_id = ?
                     ORDER BY u.full_name ASC";
$part_stmt = $conn->prepare($participants_sql);
$part_stmt->bind_param("i", $event_id);
$part_stmt->execute();
$participants = $part_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$part_stmt->close();

// --- Create the PDF ---

// Create a custom PDF class to have a header and footer
class PDF extends FPDF {
    private $eventName = '';

    function setEventName($name) {
        $this->eventName = $name;
    }

    // Page header
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Participant List', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 12);
        $this->Cell(0, 10, $this->eventName, 0, 1, 'C');
        $this->Ln(5); // Line break
    }

    // Page footer
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 0, 'R');
    }

    // Function to create the participant table
    function CreateTable($header, $data) {
        // Column widths
        $w = array(80, 40, 70);
        // Header
        $this->SetFillColor(230, 230, 230);
        $this->SetFont('Arial', 'B', 12);
        for($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
        }
        $this->Ln();
        // Data
        $this->SetFont('Arial', '', 10);
        foreach($data as $row) {
            $this->Cell($w[0], 6, $row['full_name'], 'LR');
            $this->Cell($w[1], 6, $row['roll_number'] ?: 'N/A', 'LR');
            $this->Cell($w[2], 6, $row['college_name'], 'LR');
            $this->Ln();
        }
        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T');
    }
}

// --- Generate and Output the PDF ---
$pdf = new PDF();
$pdf->setEventName($event_name);
$pdf->AliasNbPages(); // Enable page numbering
$pdf->AddPage();

$header = array('Participant Name', 'Roll Number / ID', 'College');
$pdf->CreateTable($header, $participants);

// Force download the PDF
$safe_event_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $event_name);
$pdf->Output('D', $safe_event_name . '_Participants.pdf');
?>