<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayslipMail extends Mailable
{
  use Queueable, SerializesModels;

  public function __construct(
    public readonly string $subjectLine,
    public readonly string $messageBody,
    public readonly string $filename,
    public readonly string $pdfBinary
  ) {}

  public function build(): self
  {
    return $this
      ->subject($this->subjectLine)
      ->view('emails.payslip')
      ->with([
        'body' => $this->messageBody,
      ])
      ->attachData($this->pdfBinary, $this->filename, [
        'mime' => 'application/pdf',
      ]);
  }
}

