<?php

declare(strict_types=1);

namespace App\Mail;

use App\Support\StoreSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class OrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, mixed> $order */
    public function __construct(public array $order, public bool $adminCopy = false) {}

    public function envelope(): Envelope
    {
        $isQuote = ($this->order['checkoutType'] ?? 'payment') === 'quote';
        $emailSettings = app(StoreSettings::class)->emails();
        $senderEmail = trim((string) ($emailSettings['senderEmail'] ?? ''));
        $senderName = trim((string) ($emailSettings['senderName'] ?? ''));

        return new Envelope(
            from: $senderEmail !== '' ? new Address($senderEmail, $senderName !== '' ? $senderName : null) : null,
            subject: $this->adminCopy
                ? ($isQuote ? 'Novo orcamento recebido: ' : 'Novo pedido recebido: ').$this->order['number']
                : ($isQuote ? 'Recebemos seu orcamento ' : 'Recebemos seu pedido ').$this->order['number'],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.orders.placed');
    }
}
