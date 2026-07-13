<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence;

use App\Modules\Payment\Application\DTO\PaymentInstructions;
use App\Modules\Payment\Application\DTO\PaymentResult;
use App\Modules\Payment\Application\Port\PaymentInstructionStore;
use Illuminate\Database\ConnectionInterface;

final readonly class DatabasePaymentInstructionStore implements PaymentInstructionStore
{
    public function __construct(private ConnectionInterface $database) {}

    public function save(string $paymentId, PaymentResult $result): void
    {
        if ($result->redirectUrl === null && $result->pixPayload === null && $result->pixEncodedImage === null) {
            return;
        }

        $this->database->table('payment_instructions')->updateOrInsert(['payment_id' => $paymentId], [
            'payment_url' => $result->redirectUrl,
            'pix_payload' => $result->pixPayload,
            'pix_encoded_image' => $result->pixEncodedImage,
            'pix_expiration_at' => $result->pixExpirationDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function find(string $paymentId): ?PaymentInstructions
    {
        $record = $this->database->table('payment_instructions')->where('payment_id', $paymentId)->first();

        return $record === null ? null : new PaymentInstructions(
            $record->payment_url === null ? null : (string) $record->payment_url,
            $record->pix_payload === null ? null : (string) $record->pix_payload,
            $record->pix_encoded_image === null ? null : (string) $record->pix_encoded_image,
            $record->pix_expiration_at === null ? null : (string) $record->pix_expiration_at,
        );
    }
}
