<?php

namespace App\Mail;

use App\Models\Penjualan;
use App\Models\ProfilePerusahaan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoicePenjualanMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Penjualan $penjualan,
        public ?ProfilePerusahaan $profile = null,
        public ?string $messageNote = null,
    ) {}

    public function build(): static
    {
        $pdf = Pdf::loadView('penjualan.invoice-simple', [
            'penjualan' => $this->penjualan,
            'profile' => $this->profile,
        ]);

        return $this->subject('Invoice ' . $this->penjualan->no_nota)
            ->view('emails.invoice-penjualan', [
                'penjualan' => $this->penjualan,
                'profile' => $this->profile,
                'messageNote' => $this->messageNote,
            ])
            ->attachData($pdf->output(), 'Invoice-' . $this->penjualan->no_nota . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
