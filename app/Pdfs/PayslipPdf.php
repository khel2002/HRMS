<?php

namespace App\Pdfs;

use App\Models\Employee;
use App\Models\Payroll;
use TCPDF;

class PayslipPdf
{
  private string $companyName = 'RURAL BANK OF HINDANG (LEYTE), INC.';
  private string $preparedBy = '';
  private string $preparedByTitle = 'Tax and Admin Bookkeeper';

  public function generate(
    Employee $employee,
    ?Payroll $first,
    ?Payroll $second,
    array $meta,
    bool $download = false,
  ) {
    [$filename, $content] = $this->renderBinary($employee, $first, $second, $meta);

    return response($content, 200, [
      'Content-Type' => 'application/pdf',
      'Content-Disposition' => ($download ? 'attachment' : 'inline') . "; filename=\"{$filename}\"",
    ]);
  }

  public function renderBinary(
    Employee $employee,
    ?Payroll $first,
    ?Payroll $second,
    array $meta,
  ): array {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(18, 18, 18);
    $pdf->SetAutoPageBreak(true, 12);
    $pdf->AddPage();
    $pdf->SetTitle('Payslip');

    $html = '';

    if ($first) {
      $html .= $this->buildPayslipBlock($employee, $first);
    }

    if ($second) {
      $html .= '<div style="height:28px;"></div>';
      $html .= $this->buildPayslipBlock($employee, $second);
    }

    if (! $first && ! $second) {
      $html .= '<p>No payroll record available for this period.</p>';
    }

    $pdf->writeHTML($html, true, false, true, false, '');

    $year = $meta['year'] ?? now()->year;
    $month = str_pad((string) ($meta['month'] ?? now()->month), 2, '0', STR_PAD_LEFT);

    $slug = preg_replace('/[^A-Za-z0-9_]+/', '_', strtolower($employee->full_name ?? 'employee'));
    $filename = "payslip_{$slug}_{$year}_{$month}.pdf";

    return [$filename, $pdf->Output($filename, 'S')];
  }

  private function buildPayslipBlock(Employee $employee, Payroll $payroll): string
  {
    $name = htmlspecialchars($employee->full_name ?? '—', ENT_QUOTES);

    $period = $this->periodLabel($payroll);

    $basicSalary = $this->money($payroll->basic_salary ?? 0);
    $grossPay = (float) ($payroll->gross_pay ?? 0);
    $totalDeductions = (float) ($payroll->total_deductions ?? 0);
    $netPay = (float) ($payroll->net_pay ?? 0);

    $earnings = $this->itemsHtml($payroll, 'earning');
    $deductions = $this->itemsHtml($payroll, 'deduction');

    $earningSection = '';

    if ($earnings['hasItems']) {
      $earningSection = <<<HTML
                <tr>
                    <td style="width:55%;">Add: {$earnings['firstLabel']}</td>
                    <td style="width:20%; text-align:right;">{$this->money($earnings['firstAmount'])}</td>
                    <td style="width:25%; text-align:right;"></td>
                </tr>
                {$earnings['remainingRows']}
                <tr>
                    <td>Total</td>
                    <td></td>
                    <td style="text-align:right; border-top:1px solid #000;">{$this->money($grossPay)}</td>
                </tr>
            HTML;
    }

    return <<<HTML
            <table cellpadding="0" cellspacing="0" style="width:100%; font-size:11px; color:#000;">
                <tr>
                    <td style="font-size:12px; font-weight:bold;">{$this->companyName}</td>
                </tr>
                <tr><td style="height:14px;"></td></tr>
                <tr><td>{$name}</td></tr>
                <tr><td>For the period of {$period}</td></tr>
                <tr><td style="height:28px;"></td></tr>
            </table>

            <table cellpadding="1" cellspacing="0" style="width:100%; font-size:11px; color:#000;">
                <tr>
                    <td style="width:55%;">Basic Salary</td>
                    <td style="width:20%; text-align:right;"></td>
                    <td style="width:25%; text-align:right;">{$basicSalary}</td>
                </tr>

                {$earningSection}

                <tr>
                    <td>Less: Deductions</td>
                    <td></td>
                    <td></td>
                </tr>

                {$deductions['rows']}

                <tr>
                    <td style="font-weight:bold;">NETPAY</td>
                    <td></td>
                    <td style="text-align:right; font-weight:bold; border-top:1px solid #000; border-bottom:3px double #000;">
                        {$this->money($netPay)}
                    </td>
                </tr>
            </table>

            <table cellpadding="0" cellspacing="0" style="width:100%; font-size:11px; color:#000;">
                <tr><td style="height:34px;"></td></tr>
                <tr><td>Prepared by:</td></tr>
                <tr><td style="height:28px;"></td></tr>
                <tr><td style="text-decoration:underline;">{$this->preparedBy}</td></tr>
                <tr><td>{$this->preparedByTitle}</td></tr>
            </table>
        HTML;
  }

  private function itemsHtml(Payroll $payroll, string $category): array
  {
    $items = $payroll->items
      ? $payroll->items
      ->filter(fn($item) => ($item->category ?? '') === $category)
      ->sortBy('sort_order')
      ->values()
      : collect();

    if ($items->isEmpty()) {
      if ($category === 'deduction') {
        return [
          'rows' => '
                        <tr>
                            <td style="padding-left:16px; color:#777;">No deductions</td>
                            <td style="text-align:right;">0.00</td>
                            <td style="text-align:right;">0.00</td>
                        </tr>
                    ',
          'hasItems' => false,
        ];
      }

      return [
        'hasItems' => false,
        'firstLabel' => '',
        'firstAmount' => 0,
        'remainingRows' => '',
      ];
    }

    if ($category === 'earning') {
      $first = $items->first();
      $remaining = $items->slice(1);

      $remainingRows = $remaining->map(function ($item) {
        $label = htmlspecialchars((string) ($item->label ?? '—'), ENT_QUOTES);
        $amount = $this->money($item->amount ?? 0);

        return "
                    <tr>
                        <td>Add: {$label}</td>
                        <td style=\"text-align:right;\">{$amount}</td>
                        <td></td>
                    </tr>
                ";
      })->implode('');

      return [
        'hasItems' => true,
        'firstLabel' => htmlspecialchars((string) ($first->label ?? '—'), ENT_QUOTES),
        'firstAmount' => (float) ($first->amount ?? 0),
        'remainingRows' => $remainingRows,
      ];
    }

    $total = $items->sum(fn($item) => (float) ($item->amount ?? 0));

    $rows = $items->map(function ($item) {
      $label = htmlspecialchars((string) ($item->label ?? '—'), ENT_QUOTES);
      $amount = $this->money($item->amount ?? 0);

      return "
                <tr>
                    <td style=\"padding-left:16px;\">{$label}</td>
                    <td style=\"text-align:right;\">{$amount}</td>
                    <td></td>
                </tr>
            ";
    })->implode('');

    $rows .= "
            <tr>
                <td></td>
                <td style=\"text-align:right; border-top:1px solid #000;\">{$this->money($total)}</td>
                <td></td>
            </tr>
        ";

    return [
      'rows' => $rows,
      'hasItems' => true,
    ];
  }

  private function periodLabel(Payroll $payroll): string
  {
    $period = $payroll->period ?? $payroll->payrollPeriod ?? null;

    if (! $period) {
      return '—';
    }

    $from = $period->date_from
      ? \Carbon\Carbon::parse($period->date_from)->format('F d, Y')
      : null;

    $to = $period->date_to
      ? \Carbon\Carbon::parse($period->date_to)->format('F d, Y')
      : null;

    if ($from && $to) {
      return "{$from} to {$to}";
    }

    return $period->name ?? '—';
  }

  private function money(mixed $value): string
  {
    return number_format((float) $value, 2);
  }
}
