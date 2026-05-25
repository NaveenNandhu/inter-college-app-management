<?php
// This script will not be accessed directly.
// It will be included by other scripts that need to generate a PDF.
require_once 'fpdf/fpdf.php';

// A function to generate a certificate and save it to a file.
// It takes the database connection, user details, event details, and the type of certificate as input.
function generate_certificate($conn, $user_id, $event_id, $certificate_type = 'Participation', $rank = '')
{

    // 1. Fetch required names from the database
    $user_name = '';
    $event_name = '';

    $user_sql = "SELECT full_name FROM users WHERE student_id = ?";
    if ($stmt = $conn->prepare($user_sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($user_name);
        $stmt->fetch();
        $stmt->close();
    }

    $event_sql = "SELECT event_name FROM events WHERE event_id = ?";
    if ($stmt = $conn->prepare($event_sql)) {
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->bind_result($event_name);
        $stmt->fetch();
        $stmt->close();
    }

    if (empty($user_name) || empty($event_name)) {
        return false; // Cannot generate certificate if data is missing
    }

    // 2. Create a custom PDF class that extends FPDF
    if (!class_exists('CertificateGeneratorPDF')) {
        class CertificateGeneratorPDF extends FPDF
        {
            // Page header
            function Header()
            {
                // Set background color
                $this->SetFillColor(245, 245, 245);
                $this->Rect(0, 0, 297, 210, 'F'); // A4 Landscape size: 297x210 mm

                // Double border
                $this->Rect(5, 5, 287, 200, 'D'); // Outer border
                $this->Rect(8, 8, 281, 194, 'D'); // Inner border
            }

            // Page footer
            function Footer()
            {
                $this->SetY(-30);
                $this->SetFont('Arial', '', 12);

                // Signature lines
                $this->Cell(0, 10, '_________________________', 0, 0, 'L');
                $this->Cell(0, 10, '_________________________', 0, 1, 'R');
                $this->Cell(0, 5, 'Principal', 0, 0, 'L');
                $this->Cell(0, 5, 'HOD / Event Manager', 0, 1, 'R');
            }
        }
    }

    // 3. Create the PDF object
    $pdf = new CertificateGeneratorPDF('L', 'mm', 'A4'); // L for Landscape
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);

    // 4. Add Content to the PDF

    // College Logo (optional - if you have a logo file)
    // $pdf->Image('path/to/logo.png', 138, 15, 20);

    // College Name
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetXY(0, 30);
    $pdf->Cell(297, 10, 'Gobi Arts & Science College', 0, 1, 'C');

    // Certificate Title
    $pdf->SetFont('Arial', 'I', 16);
    $pdf->SetXY(0, 45);
    $cert_title = ($certificate_type === 'Winner') ? 'Certificate of Achievement' : 'Certificate of Participation';
    $pdf->Cell(297, 10, $cert_title, 0, 1, 'C');

    // "This is to certify that"
    $pdf->SetFont('Arial', '', 14);
    $pdf->SetXY(0, 70);
    $pdf->Cell(297, 10, 'This is to certify that', 0, 1, 'C');

    // Participant's Name
    $pdf->SetFont('Times', 'B', 36);
    $pdf->SetXY(0, 85);
    $pdf->Cell(297, 15, $user_name, 0, 1, 'C');

    // Main text
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

    // 5. Define where to save the file
    $output_dir = '../lib/certificates/';
    if (!file_exists($output_dir)) {
        mkdir($output_dir, 0777, true);
    }
    // Create a unique filename
    $filename = 'Cert_' . $event_id . '_' . $user_id . '_' . time() . '.pdf';
    $filepath = $output_dir . $filename;

    // Save the PDF to the server
    $pdf->Output('F', $filepath);

    // Return the path to the generated file
    return $filepath;
}
?>