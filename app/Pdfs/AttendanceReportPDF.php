<?php

namespace App\Pdfs;

use TCPDF;
use Carbon\Carbon;

class AttendanceReportPDF
{
  // ── Colours ───────────────────────────────────────────────────────────────
  const COLOR_PRIMARY  = [105, 108, 255]; // #696cff  (brand purple)
  const COLOR_LATE     = [255, 159,  67]; // #ff9f43  (orange)
  const COLOR_ABSENT   = [234,  84,  85]; // #ea5455  (red)
  const COLOR_LEAVE    = [105, 108, 255]; // #696cff  (purple)
  const COLOR_PRESENT  = [40, 199, 111]; // #28c76f  (green)
  const COLOR_MUTED    = [168, 170, 174]; // #a8aaae
  const COLOR_BORDER   = [222, 222, 226]; // #dedee2
  const COLOR_ROW_ALT  = [248, 248, 251]; // #f8f8fb  (zebra stripe)
  const COLOR_HEADER_BG = [245, 245, 250]; // table head bg

  // ── Layout ────────────────────────────────────────────────────────────────
  const PAGE_W   = 210; // A4 width  (mm)
  const MARGIN_H = 14;  // horizontal margin
  const MARGIN_V = 14;  // vertical   margin
  const CONTENT_W = self::PAGE_W - (self::MARGIN_H * 2); // 182 mm

  /**
   * Generate and stream the Daily Attendance Report PDF.
   *
   * @param  string  $date        YYYY-MM-DD
   * @param  array   $summary     Keys: total_employees, present, late, on_leave, absent, pending
   * @param  array   $late        [ ['name','pos','dept','time_in','late_duration','late_minutes'], ... ]
   * @param  array   $onLeave     [ ['name','pos','dept','leave_type','leave_days'], ... ]
   * @param  array   $absent      [ ['name','pos','dept'], ... ]
   * @param  array   $pending     [ ['name','pos','dept'], ... ]
   * @param  bool    $cutoffPassed
   * @return \Illuminate\Http\Response
   */
  public function generate(
    string $date,
    array  $summary,
    array  $late,
    array  $onLeave,
    array  $absent,
    array  $pending,
    bool   $cutoffPassed
  ) {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // ── Document meta ─────────────────────────────────────────────────────
    $pdf->SetCreator('HRIS System');
    $pdf->SetAuthor('Rural Bank of Hindang');
    $pdf->SetTitle('Daily Attendance Report – ' . Carbon::parse($date)->format('F j, Y'));
    $pdf->SetSubject('Attendance Report');

    // ── Disable default header/footer ─────────────────────────────────────
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // ── Margins ───────────────────────────────────────────────────────────
    $pdf->SetMargins(self::MARGIN_H, self::MARGIN_V, self::MARGIN_H);
    $pdf->SetAutoPageBreak(true, self::MARGIN_V);

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 9);

    $this->drawHeader($pdf, $date, $summary);
    $this->drawSummaryCards($pdf, $summary);
    $this->drawLateSection($pdf, $late);
    $this->drawLeaveSection($pdf, $onLeave);
    $this->drawAbsentSection($pdf, $absent, $cutoffPassed);
    $this->drawFooter($pdf);

    $filename    = 'attendance_report_' . $date . '.pdf';
    $pdfContent  = $pdf->Output($filename, 'S');

    return response($pdfContent, 200, [
      'Content-Type'        => 'application/pdf',
      'Content-Disposition' => 'inline; filename="' . $filename . '"',
    ]);
  }

  // ── HEADER ────────────────────────────────────────────────────────────────

  private function drawHeader(TCPDF $pdf, string $date, array $summary): void
  {
    $x  = self::MARGIN_H;
    $cw = self::CONTENT_W;

    // Purple top accent bar
    $pdf->SetFillColor(...self::COLOR_PRIMARY);
    $pdf->Rect($x, self::MARGIN_V, $cw, 1.2, 'F');

    $y = self::MARGIN_V + 5;

    // ── Left: company + title ─────────────────────────────────────────────
    $pdf->SetXY($x, $y);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(...self::COLOR_PRIMARY);
    $pdf->Cell(100, 5, 'Rural Bank of Hindang', 0, 2, 'L');

    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(...self::COLOR_MUTED);
    $pdf->SetX($x);
    $pdf->Cell(100, 4, 'Human Resource Information System', 0, 2, 'L');

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->SetX($x);
    $pdf->Cell(120, 9, 'Daily Attendance Report', 0, 2, 'L');

    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(...self::COLOR_MUTED);
    $pdf->SetX($x);
    $pdf->Cell(120, 5, Carbon::parse($date)->format('l, F j, Y'), 0, 0, 'L');

    // ── Right: generated info ─────────────────────────────────────────────
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(...self::COLOR_MUTED);
    $pdf->SetXY($x + 120, $y);
    $pdf->Cell($cw - 120, 5, 'Generated: ' . now()->format('M j, Y  g:i A'), 0, 2, 'R');
    $pdf->SetX($x + 120);
    $pdf->Cell($cw - 120, 5, 'Prepared by: ' . (auth()->user()?->name ?? 'System'), 0, 0, 'R');

    // Divider
    $pdf->SetDrawColor(...self::COLOR_BORDER);
    $pdf->SetLineWidth(0.3);
    $pdf->Line($x, $y + 27, $x + $cw, $y + 27);

    $pdf->SetY($y + 30);
  }

  // ── SUMMARY CARDS ─────────────────────────────────────────────────────────

  private function drawSummaryCards(TCPDF $pdf, array $summary): void
  {
    $x   = self::MARGIN_H;
    $y   = $pdf->GetY();
    $cw  = self::CONTENT_W;

    // Section label
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(...self::COLOR_MUTED);
    $pdf->SetXY($x, $y);
    $pdf->Cell($cw, 5, 'ATTENDANCE SUMMARY', 0, 2, 'L');
    $y += 6;

    $cards = [
      ['label' => 'Total Employees', 'value' => $summary['total_employees'], 'color' => self::COLOR_PRIMARY],
      ['label' => 'Present',          'value' => $summary['present'],         'color' => self::COLOR_PRESENT],
      ['label' => 'Absent',           'value' => $summary['absent'],          'color' => self::COLOR_ABSENT],
      ['label' => 'Late',             'value' => $summary['late'],            'color' => self::COLOR_LATE],
      ['label' => 'On Leave',         'value' => $summary['on_leave'],        'color' => self::COLOR_LEAVE],
    ];

    $cardW   = ($cw - (count($cards) - 1) * 3) / count($cards); // equal width with 3 mm gaps
    $cardH   = 18;
    $gap     = 3;

    foreach ($cards as $i => $card) {
      $cx = $x + $i * ($cardW + $gap);

      // Card background
      $pdf->SetFillColor(245, 245, 250);
      $pdf->SetDrawColor(...self::COLOR_BORDER);
      $pdf->SetLineWidth(0.2);
      $pdf->RoundedRect($cx, $y, $cardW, $cardH, 2, '1111', 'FD');

      // Coloured left accent stripe
      $pdf->SetFillColor(...$card['color']);
      $pdf->Rect($cx, $y, 2, $cardH, 'F');

      // Value (large)
      $pdf->SetFont('helvetica', 'B', 14);
      $pdf->SetTextColor(...$card['color']);
      $pdf->SetXY($cx + 4, $y + 2);
      $pdf->Cell($cardW - 4, 8, (string) $card['value'], 0, 0, 'L');

      // Label
      $pdf->SetFont('helvetica', '', 7.5);
      $pdf->SetTextColor(...self::COLOR_MUTED);
      $pdf->SetXY($cx + 4, $y + 10);
      $pdf->Cell($cardW - 4, 5, $card['label'], 0, 0, 'L');
    }

    $pdf->SetY($y + $cardH + 8);
  }

  // ── LATE SECTION ──────────────────────────────────────────────────────────

  private function drawLateSection(TCPDF $pdf, array $list): void
  {
    $x  = self::MARGIN_H;
    $cw = self::CONTENT_W;

    $this->drawSectionHeading($pdf, 'Late Employees', count($list), self::COLOR_LATE);

    if (empty($list)) {
      $this->drawEmptyState($pdf, 'No late arrivals on this day.');
      return;
    }

    // Table header columns: Name | Office/Dept | Time In | Late By
    $cols = [
      ['label' => 'EMPLOYEE NAME',     'w' => 65, 'align' => 'L'],
      ['label' => 'OFFICE / DEPT',     'w' => 55, 'align' => 'L'],
      ['label' => 'TIME IN',           'w' => 28, 'align' => 'C'],
      ['label' => 'LATE BY',           'w' => 34, 'align' => 'C'],
    ];

    $this->drawTableHeader($pdf, $cols);

    foreach ($list as $i => $emp) {
      $this->drawTableRow($pdf, $cols, [
        $emp['name'],
        $emp['pos'] . ' · ' . $emp['dept'],
        $emp['time_in'],
        $emp['late_duration'],
      ], $i, [
        3 => [
          'color' => ($emp['late_minutes'] ?? 0) >= 60
            ? self::COLOR_ABSENT
            : self::COLOR_LATE,
          'bold'  => true,
        ],
      ]);
    }

    $pdf->Ln(6);
  }

  // ── ON LEAVE SECTION ──────────────────────────────────────────────────────

  private function drawLeaveSection(TCPDF $pdf, array $list): void
  {
    $this->drawSectionHeading($pdf, 'Employees on Leave', count($list), self::COLOR_LEAVE);

    if (empty($list)) {
      $this->drawEmptyState($pdf, 'No employees on leave on this day.');
      return;
    }

    // Group by leave type
    $groups = [];
    foreach ($list as $emp) {
      $groups[$emp['leave_type']][] = $emp;
    }

    $leaveColors = [
      'vacation'  => self::COLOR_PRESENT,
      'sick'      => self::COLOR_ABSENT,
      'service'   => self::COLOR_LEAVE,
      'incentive' => self::COLOR_LEAVE,
    ];

    foreach ($groups as $leaveType => $employees) {
      $color = self::COLOR_PRIMARY;
      foreach ($leaveColors as $keyword => $c) {
        if (stripos($leaveType, $keyword) !== false) {
          $color = $c;
          break;
        }
      }

      // Sub-group label
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetTextColor(...$color);
      $pdf->SetX(self::MARGIN_H);
      $pdf->Cell(
        self::CONTENT_W,
        5,
        strtoupper($leaveType) . '  (' . count($employees) . ' ' . (count($employees) === 1 ? 'employee' : 'employees') . ')',
        0,
        2,
        'L'
      );

      $cols = [
        ['label' => 'EMPLOYEE NAME', 'w' => 80, 'align' => 'L'],
        ['label' => 'POSITION',      'w' => 55, 'align' => 'L'],
        ['label' => 'DEPARTMENT',    'w' => 47, 'align' => 'L'],
      ];

      $this->drawTableHeader($pdf, $cols);

      foreach ($employees as $i => $emp) {
        $this->drawTableRow($pdf, $cols, [
          $emp['name'],
          $emp['pos'],
          $emp['dept'],
        ], $i);
      }

      $pdf->Ln(4);
    }

    $pdf->Ln(2);
  }

  // ── ABSENT SECTION ────────────────────────────────────────────────────────

  private function drawAbsentSection(TCPDF $pdf, array $list, bool $cutoffPassed): void
  {
    $label = $cutoffPassed ? 'Absent Employees' : 'Pending (Not Yet Clocked In)';
    $color = $cutoffPassed ? self::COLOR_ABSENT : self::COLOR_MUTED;

    $this->drawSectionHeading($pdf, $label, count($list), $color);

    if (!$cutoffPassed) {
      // Informational note
      $pdf->SetFont('helvetica', 'I', 8);
      $pdf->SetTextColor(...self::COLOR_MUTED);
      $pdf->SetX(self::MARGIN_H);
      $pdf->Cell(
        self::CONTENT_W,
        5,
        'Note: Employees below had not clocked in at the time this report was generated. They will be marked Absent after 12:00 PM.',
        0,
        2,
        'L'
      );
    }

    if (empty($list)) {
      $msg = $cutoffPassed ? 'No absences recorded.' : 'All employees have clocked in.';
      $this->drawEmptyState($pdf, $msg);
      return;
    }

    $cols = [
      ['label' => 'EMPLOYEE NAME', 'w' => 80, 'align' => 'L'],
      ['label' => 'POSITION',      'w' => 55, 'align' => 'L'],
      ['label' => 'DEPARTMENT',    'w' => 47, 'align' => 'L'],
    ];

    $this->drawTableHeader($pdf, $cols);

    foreach ($list as $i => $emp) {
      $this->drawTableRow($pdf, $cols, [
        $emp['name'],
        $emp['pos'],
        $emp['dept'],
      ], $i, [
        0 => ['color' => $color, 'bold' => false],
      ]);
    }

    $pdf->Ln(6);
  }

  // ── PAGE FOOTER ───────────────────────────────────────────────────────────

  private function drawFooter(TCPDF $pdf): void
  {
    $pageH = $pdf->getPageHeight();
    $x     = self::MARGIN_H;
    $cw    = self::CONTENT_W;
    $y     = $pageH - 14;

    $pdf->SetDrawColor(...self::COLOR_BORDER);
    $pdf->SetLineWidth(0.3);
    $pdf->Line($x, $y, $x + $cw, $y);

    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetTextColor(...self::COLOR_MUTED);
    $pdf->SetXY($x, $y + 2);
    $pdf->Cell($cw / 2, 4, 'Rural Bank of Hindang – HRIS', 0, 0, 'L');
    $pdf->Cell($cw / 2, 4, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 0, 'R');
  }

    // ── HELPERS ───────────────────────────────────────────────────────────────

  /**
   * Draw a bold coloured section heading with a count badge.
   */
  private function drawSectionHeading(TCPDF $pdf, string $title, int $count, array $color): void
  {
    $x  = self::MARGIN_H;
    $cw = self::CONTENT_W;
    $y  = $pdf->GetY();

    // Left colour stripe
    $pdf->SetFillColor(...$color);
    $pdf->Rect($x, $y, 2.5, 7, 'F');

    // Title
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(30, 30, 30);
    $pdf->SetXY($x + 5, $y + 0.5);
    $pdf->Cell(80, 6, $title, 0, 0, 'L');

    // Count pill
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetTextColor(...$color);
    $pdf->SetXY($x + 5, $y + 0.5);
    $pdf->Cell($cw - 5, 6, '(' . $count . ')', 0, 0, 'R');

    $pdf->Ln(9);
  }

  /**
   * Draw a table header row.
   *
   * @param array $cols  [ ['label'=>'', 'w'=>mm, 'align'=>'L|C|R'], ... ]
   */
  private function drawTableHeader(TCPDF $pdf, array $cols): void
  {
    $x = self::MARGIN_H;
    $pdf->SetXY($x, $pdf->GetY());

    $pdf->SetFillColor(...self::COLOR_HEADER_BG);
    $pdf->SetDrawColor(...self::COLOR_BORDER);
    $pdf->SetLineWidth(0.2);
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetTextColor(...self::COLOR_MUTED);

    foreach ($cols as $col) {
      $pdf->Cell($col['w'], 6, $col['label'], 'B', 0, $col['align'], true);
    }
    $pdf->Ln();
  }

  /**
   * Draw a single data row with optional zebra striping and per-cell colour overrides.
   *
   * @param array $cols     Column definitions (same as drawTableHeader)
   * @param array $values   Cell values in column order
   * @param int   $rowIndex Used for zebra striping (even/odd)
   * @param array $overrides  [ colIndex => ['color' => [r,g,b], 'bold' => bool], ... ]
   */
  private function drawTableRow(
    TCPDF $pdf,
    array $cols,
    array $values,
    int   $rowIndex,
    array $overrides = []
  ): void {
    $x   = self::MARGIN_H;
    $pdf->SetXY($x, $pdf->GetY());

    $isAlt = $rowIndex % 2 === 1;
    $pdf->SetFillColor(...($isAlt ? self::COLOR_ROW_ALT : [255, 255, 255]));
    $pdf->SetDrawColor(...self::COLOR_BORDER);
    $pdf->SetLineWidth(0.1);

    foreach ($cols as $i => $col) {
      $override  = $overrides[$i] ?? null;
      $bold      = $override['bold'] ?? false;
      $textColor = $override['color'] ?? [50, 50, 50];

      $pdf->SetFont('helvetica', $bold ? 'B' : '', 8.5);
      $pdf->SetTextColor(...$textColor);
      $pdf->Cell($col['w'], 7, $values[$i] ?? '', 'B', 0, $col['align'], true);
    }
    $pdf->Ln();
  }

  /**
   * Draw a centred empty-state message.
   */
  private function drawEmptyState(TCPDF $pdf, string $message): void
  {
    $pdf->SetFont('helvetica', 'I', 8.5);
    $pdf->SetTextColor(...self::COLOR_MUTED);
    $pdf->SetFillColor(248, 248, 251);
    $pdf->SetX(self::MARGIN_H);
    $pdf->Cell(self::CONTENT_W, 10, $message, 0, 2, 'C', true);
    $pdf->Ln(4);
  }
}
