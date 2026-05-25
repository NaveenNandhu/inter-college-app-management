<?php
require_once '../config/db_connect.php';
require_once '../lib/fpdf/fpdf.php';

// Security Check
if(!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true){
    header("location: ../auth.php?action=login");
    exit;
}

// Get IDs from the URL
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);

if (!$user_id || !$event_id) {
    die("Invalid request. User or Event ID missing.");
}

// --- Fetch required data from the database ---
$user_name = '';
$event_name = '';

$user_sql = "SELECT full_name FROM users WHERE student_id = ?";
if($stmt = $conn->prepare($user_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($user_name);
    $stmt->fetch();
    $stmt->close();
}

$event_sql = "SELECT event_name FROM events WHERE event_id = ?";
if($stmt = $conn->prepare($event_sql)) {
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $stmt->bind_result($event_name);
    $stmt->fetch();
    $stmt->close();
}

if (empty($user_name) || empty($event_name)) {
    die("Could not retrieve user or event data from the database.");
}

// --- Check if the user is a winner to determine certificate type ---
$rank = '';
$certificate_type = 'Participation';
$results_sql = "SELECT 
                    CASE WHEN rw.position = 1 THEN '1st Place'
                         WHEN rw.position = 2 THEN '2nd Place'
                         WHEN rw.position = 3 THEN '3rd Place'
                         ELSE '' END AS rank
                FROM result_winners rw
                JOIN results r ON rw.result_id = r.result_id
                WHERE r.event_id = ? AND rw.user_id = ?";
if($stmt = $conn->prepare($results_sql)) {
    $stmt->bind_param("ii", $event_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($rank_from_db);
    if ($stmt->fetch() && !empty($rank_from_db)) {
        $rank = $rank_from_db;
        $certificate_type = 'Winner';
    }
    $stmt->close();
}

// --- FPDF PDF Generation Logic ---
class PDF extends FPDF {
    function Header() {
        $this->SetFillColor(245, 245, 245);
        $this->Rect(0, 0, 297, 210, 'F');
        $this->Rect(5, 5, 287, 200, 'D');
        $this->Rect(8, 8, 281, 194, 'D');
    }
    function Footer() {
        $this->SetY(-30);
        $this->SetFont('Arial', '', 12);
        $this->Cell(130, 10, '_________________________', 0, 0, 'C');
        $this->Cell(130, 10, '_________________________', 0, 1, 'C');
        $this->SetX(10);
        $this->Cell(130, 5, 'Principal', 0, 0, 'C');
        $this->Cell(130, 5, 'HOD / Event Manager', 0, 1, 'C');
    }
}

$pdf = new PDF('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(false);

$pdf->SetFont('Arial', 'B', 24);
$pdf->SetXY(0, 30);
$pdf->Cell(297, 10, 'Gobi Arts & Science College', 0, 1, 'C');

$pdf->SetFont('Arial', 'I', 16);
$pdf->SetXY(0, 45);
$cert_title = ($certificate_type === 'Winner') ? 'Certificate of Achievement' : 'Certificate of Participation';
$pdf->Cell(297, 10, $cert_title, 0, 1, 'C');

$pdf->SetFont('Arial', '', 14);
$pdf->SetXY(0, 70);
$pdf->Cell(297, 10, 'This is to certify that', 0, 1, 'C');

$pdf->SetFont('Times', 'B', 36);
$pdf->SetXY(0, 85);
$pdf->Cell(297, 15, $user_name, 0, 1, 'C');

$pdf->SetFont('Arial', '', 14);
$pdf->SetXY(0, 110);
if ($certificate_type === 'Winner') {
    $line1 = "has secured the " . $rank . " in the event";
} else {
    $line1 = "has successfully participated in the event";
}
$pdf->Cell(297, 10, $line1, 0, 1, 'C');

$pdf->SetFont('Arial', 'B', 18);
$pdf->SetXY(0, 120);
$pdf->Cell(297, 10, '"' . $event_name . '"', 0, 1, 'C');

$pdf->SetFont('Arial', '', 14);
$pdf->SetXY(0, 130);
$pdf->Cell(297, 10, 'held on ' . date('F jS, Y'), 0, 1, 'C');

// --- Force Download ---
$safe_user_name = preg_replace("/[^a-zA-Z0-9]+/", "", $user_name);
$pdf->Output('D', 'Certificate_' . $safe_user_name . '.pdf');
?>