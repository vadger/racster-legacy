<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Stripe\StripeClient;

class ScheduledHtmlMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
		public string $subjectLine,
		public string $htmlContent,
		public ?string $htmlHeading = null,
		public ?string $htmlCTAName = null,
		public ?string $htmlCTALink = null,
		public ?string $invoiceId = null
	)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
		return new Content(
			view: 'emails.scheduled_html',
			with: [
				'htmlContent' => $this->htmlContent,
				'htmlHeading' => $this->htmlHeading,
				'htmlCTAName' => $this->htmlCTAName,
				'htmlCTALink' => $this->htmlCTALink,
				'subjectLine' => $this->subjectLine,
			],
		);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {

		if (empty($this->invoiceId)){
			return [];
		}
		
		$stripe = new StripeClient(env('STRIPE_SECRET'));
		$invoice = $stripe->invoices->retrieve($this->invoiceId, []);

		// Ensure the invoice is finalized
		if (empty($invoice->invoice_pdf)) {
			return [];
		}

		// Download the PDF from Stripe public link
		$response = Http::get($invoice->invoice_pdf);
		if (!$response->ok()) {
			return [];
		}

		$filename = 'invoice-' . ($invoice->number ?? $this->invoiceId) . '.pdf';
		$pdfBytes = $response->body();

		return [
			Attachment::fromData(fn () => $pdfBytes, $filename)
				->withMime('application/pdf'),
		];

    }
}
