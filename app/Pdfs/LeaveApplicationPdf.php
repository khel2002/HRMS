<?php

namespace App\Pdfs;

use TCPDF;

class LeaveApplicationPdf
{
  public function generate(array $data = [])
  {
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 11);

    $x = 10;
    $y = 10;

    // Outer border
    $pdf->setXY($x, $y);
    $pdf->Cell(190, 266, '', 'TRL');

    // Layout variables
    $ox = 10;   // outer left
    $oy = 10;   // outer top
    $tw = 190;  // total width
    $lw = 95;   // left panel width
    $rw = 95;   // right panel width
    $mx = $ox + $lw; // center divider (dynamic, safer)

    // ✅ CENTER VERTICAL LINE
    $pdf->Line($mx, $oy, $mx, $oy + 185);

    $leaveType     = strtolower($data['leave_type']     ?? '');
    $emptyfields   = strtolower($data['For manual input only no data must pass'] ?? '');

    // Data fields
    $name          = strtoupper($data['name']           ?? '');
    $department    = strtoupper($data['department']     ?? '');
    $position      = strtoupper($data['position']       ?? '');
    $cause         = $data['cause']                     ?? '';
    $totalDays     = $data['total_days']                ?? '';
    $dateFrom      = $data['date_from']                 ?? '';
    $dateTo        = $data['date_to']                   ?? '';
    $dateFiled     = $data['date_filed']                ?? '';
    $managerStatus = strtolower($data['manager_status'] ?? '');

    // =======================
    // SECTION 1 (LEFT TOP)
    // =======================
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell($lw, 5, 'APPLICATION FOR LEAVE', 'R', 0, 'L');

    $y += 5;
    $pdf->setXY($x, $y);
    $pdf->Cell($lw, 5, 'RBH Form No. 1', 'R', 0, 'L');

    $y += 5;
    $pdf->setXY($x, $y);
    $pdf->Cell($lw, 15, '', 'RB', 0, 'L');

    // =======================
    // SECTION 2 (LEFT LOWER)
    // =======================
    $y += 15; // move BELOW section 1 properly

    $pdf->setXY($x, $y);
    $pdf->Cell($lw, 8, 'Department/Staff', 'R', 0, 'L');

    $pdf->setXY($x, $y + 20);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell($lw, 0, $department, 'R', 0, 'C');

    $y += 15;
    $pdf->setXY($x, $y);
    $pdf->Cell($lw, 25, '', 'RB', 0, 'L');

    // =======================
    // SECTION 3 (LEAVE TYPES)
    // =======================
    $y += 25;
    $pdf->SetXY($x, $y);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($lw, 5, 'LEAVE APPLIED FOR: (Please Check)', 'R', 0, 'L');

    // Checkbox helper
    $chk = function (float $cx, float $cy, bool $checked, string $label) use ($pdf) {
      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetLineWidth(0.3);

      // box
      $pdf->Rect($cx, $cy, 4, 4, 'D');

      // check mark
      if ($checked) {
        $pdf->Line($cx + 0.5, $cy + 0.5, $cx + 3.5, $cy + 3.5);
        $pdf->Line($cx + 3.5, $cy + 0.5, $cx + 0.5, $cy + 3.5);
      }

      // label
      $pdf->SetXY($cx + 5, $cy + 0.2);
      $pdf->Cell(38, 4, $label, 0, 0, 'L');
    };

    // Leave type flags
    $isVacation  = str_contains($leaveType, 'vacation');
    $isSick      = str_contains($leaveType, 'sick');
    $isPaternity = str_contains($leaveType, 'paternity') || str_contains($leaveType, 'maternity');
    $isSIL       = str_contains($leaveType, 'service') || str_contains($leaveType, 'sil');

    // Manager status flags
    $isRec    = ($managerStatus === 'recommended');
    $isNotRec = ($managerStatus === 'not_recommended');

    // Left column checkboxes—
    $chk($ox + 12, $y + 8,  $isVacation,  'Vacation');
    $chk($ox + 12, $y + 16, $isSick,      'Sick');
    $chk($ox + 12, $y + 24, $isPaternity, 'Paternity');

    // Right column checkboxes (still inside left panel)
    $chk($ox + 55, $y + 8,  $isSIL,  'Service Incentive');
    $chk($ox + 55, $y + 16, false,   'Office Notified');
    $chk($ox + 55, $y + 24, false,   'Office Not Notified');

    $y += 35;
    $pdf->setXY($x, $y);
    $pdf->Cell($lw, 20, 'No. of Days                      ________________________', 'T', 0, 'L');
    // Days value written on top without border
    if ($totalDays) {
      $pdf->setXY($x + 45, $y - 1);
      $pdf->SetFont('helvetica', 'B', 11);
      $pdf->Cell($lw + 15, 20, $totalDays . ' day' . ($totalDays != 1 ? 's' : ''), 0, 0, 'L');
      $pdf->SetFont('helvetica', '', 11);
    }

    $y += 15;
    $pdf->setXY($x, $y);
    $pdf->Cell($lw, 13, '', 'B', 0, 'L');
    // FROM / TO written on top without border
    $pdf->setXY($x + 1, $y);
    $pdf->Cell(8, 0, 'From ___________', 0, 0, 'L');
    $pdf->setXY($x + 12, $y);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 0, $dateFrom, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->setXY($x + 40, $y);
    $pdf->Cell(5, 0, 'To ___________', 0, 0, 'L');
    $pdf->setXY($x + 46, $y);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(35, 0, $dateTo, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 11);

    $empty  = str_contains($emptyfields, 'mannual input only');

    $y += 8;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($lw, 20, 'COMPUTATION                    DATE OF FILING', 0, 0, 'L');
    // Date filed value — written beside DATE OF FILING label
    if ($dateFiled) {
      $pdf->setXY($x + 48, $y + 8);
      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->Cell(40, 20, $dateFiled, 0, 0, 'C');
      $pdf->SetFont('helvetica', '', 11);
      $pdf->setXY($x + 48, $y + 8);
      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->Cell(40, 20, '___________', 0, 0, 'C');
    }
    $y += 7;
    $pdf->setXY($x, $y);
    $chk($ox + 5, $y + 8,  false, 'Requested');
    $chk($ox + 5, $y + 16, false, 'Not Requested');
    $y += 20;
    $pdf->setXY($x, $y);
    $pdf->Cell($lw, 5, '', 'B', 0, 'L');

    $y += 5;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($lw, 5, 'Action (By Dept. Manager Staff Head)', 0, 0, 'L');
    $chk($ox + 5, $y + 8,  $isRec,    'Approved Recommended');
    $chk($ox + 5, $y + 16, $isNotRec, 'Approved Not Recommended');
    $y += 20;
    $pdf->setXY($x, $y);
    $pdf->Cell($lw, 10, 'Reason', 'B', 0, 'L');

    // Right Panel (TOP RIGHT)
    $pdf->setXY($mx, $oy); // start at center line, top
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($rw, 20, '', 'B', 0, 'R');

    $pdf->setXY($mx, $oy);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($x + 45, 45, 'Name (Last) (First) (Middle)', 0, 0, 'R');

    // Name value — bold, centred in right panel
    $pdf->setXY($mx, $oy + 10);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell($rw, 8, $name, 0, 0, 'C');

    $pdf->setXY($mx, $oy);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($rw, 37, '', 'B', 0, 'R');
    $pdf->setXY($mx, $oy);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($x + 61, 80, 'Position: (Salary)                 (Monthly)', 'T', 0, 'R');

    // Position value — bold, centred in right panel
    $pdf->setXY($mx, $oy + 47);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell($rw, 8, $position, 0, 0, 'C');

    $pdf->setXY($mx, $oy);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($rw, 60, '', 'B', 0, 'R');
    $pdf->setXY($mx, $oy);
    $pdf->SetFont('helvetica', 'C', 9);
    $pdf->Cell($x + 72, 125, 'CAUSE: (Whether illness, Personal, Resignation etc. )', 'T', 0, 'R');

    // Cause value — written below the label
    if ($cause) {
      $pdf->setXY($mx + 1, $oy + 70);
      $pdf->SetFont('helvetica', '', 9);
      $pdf->MultiCell($rw - 2, 5, $cause, 0, 'L');
    }

    $pdf->setXY($mx, $oy);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($rw, 155, '', 'B', 0, 'R');
    $pdf->setXY($mx, $oy);
    $pdf->SetFont('helvetica', 'C', 9);
    $pdf->Cell($x + 40, 266, 'SIGNATURE OF APPLICANT:', 'T', 0, 'R');

    $pdf->setXY($mx, $oy);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($rw, 185, '', 'B', 0, 'R');
    $pdf->setXY($mx, $oy);


    // Start BELOW the previous section
    $signatureY = $oy + 152;

    // TEXT 1 with border
    $pdf->setXY($mx, $signatureY + 2);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($rw, 8, 'DATE:', 0, 0, 'L'); // 1 = full border

    // TEXT 2 with border
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 6, 'SIGNATURE:', 'T', 0, 'L');

    // TEXT 3 with border
    $pdf->setXY($mx, $signatureY + 25);
    $pdf->Cell($rw, 6, 'OFFICIAL TITLE:', 'T', 0, 'L');



    $signatureY = $oy + 184;
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '_____________________ ______________________', 0, 0, 'L');
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '                                           , ,', 0, 0, 'L');


    $signatureY = $oy + 189;
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '_____________________ ______________________', 0, 0, 'L');
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '                                           , ', 0, 0, 'L');


    $signatureY = $oy + 194;
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '_____________________ ______________________', 0, 0, 'L');
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '                                           , ', 0, 0, 'L');


    $signatureY = $oy + 199;
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '_____________________ ______________________', 0, 0, 'L');
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '                                           , ,', 0, 0, 'L');


    $signatureY = $oy + 204;
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '_____________________ ______________________', 0, 0, 'L');
    $pdf->setXY($mx, $signatureY + 15);
    $pdf->Cell($rw, 5, '                                           , ,', 0, 0, 'L');

    // new section
    $y += 12;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($lw, 2, 'Action (By The Administratior)', 0, 0, 'L');
    $y += 6;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($lw, 2, 'Approved For', 0, 0, 'L');

    $y += 7;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($lw, 2, '________day(s) SICK Leave with ________ pay from', 0, 0, 'L');


    $y += 5;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($lw, 2, '________day(s) Service Incentive Leave with pay from', 0, 0, 'L');

    $y += 5;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($lw, 2, '________day(s) Vacation Leave with pay from ', 0, 0, 'L');

    $y += 5;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($lw + 1, 2, '________day(s) LEAVE without pay from ', 0, 0, 'L');

    $y += 5;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell($lw + 1, 2, '________day(s) MATERNITY/PATERNITY Leave with ', 0, 0, 'L');

    $y += 5;
    $pdf->setXY($x, $y);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($lw + 1, 2, '(CLERANCE PAPER, MEDICAL CERTIFICATE, MARRIAGE CERTIFICATION, AFFIDAVIT, attached)', 0, 0, 'L');


    // Start BELOW the previous section
    $signatureY = $oy + 229;

    // TEXT 1 with border
    $pdf->setXY($mx, $signatureY);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell($rw, 5, 'DATE:', 'TL', 0, 'L'); // 1 = full border

    // TEXT 2 with border
    $pdf->setXY($mx, $signatureY + 5);
    $pdf->Cell($rw, 5, 'SIGNATURE:', 'TL', 0, 'L');

    // TEXT 3 with border
    $pdf->setXY($mx, $signatureY + 10);
    $pdf->Cell($rw, 5, 'OFFICIAL TITLE:', 'TBL', 0, 'L');

    $bottomY = $oy + 245; // adjust lower than 229 section

    $pdf->setXY($ox, $bottomY);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell($tw, 8, 'INSTRUCTIONS', 'T', 0, 'C');


    $y += 27;
    $pdf->setXY($x, $bottomY + 8);
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->Cell($lw + 1, 2, '1.  Application for vacation or sick leave for one full day or more shall be made', 0, 0, 'L');

    $pdf->setXY($x, $bottomY + 12);
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->Cell($lw + 1, 2, '2.  Application for vacation shall filed in advance or wherever possible five days going on such leave', 0, 0, 'L');

    $pdf->setXY($x, $bottomY + 17);
    $pdf->SetFont('helvetica', '', 9.5);
    $pdf->Cell($tw, 3, '3.  Application for sick leave filed in advance or exceeding three (3) days shall be accompanied by a MEDICAL CERTIFICATE', 'LRB', 0, 'L');

    // 'S' returns the PDF as a raw string — let Laravel stream it properly
    $pdfContent = $pdf->Output('leave_application.pdf', 'S');

    return response($pdfContent, 200, [
      'Content-Type'        => 'application/pdf',
      'Content-Disposition' => 'inline; filename="leave_application.pdf"',
    ]);
  }
}
