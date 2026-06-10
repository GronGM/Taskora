<?php

namespace App\Services\Payments;

use App\Models\PaymentOperation;
use App\Models\ProviderWebhookEvent;

class PaymentWebhookContract
{
    /**
     * @return array<string, string>
     */
    public static function futureEventOperationMap(): array
    {
        return [
            ProviderWebhookEvent::EVENT_PAYMENT_SUCCEEDED => PaymentOperation::TYPE_PAYMENT_HOLD,
            ProviderWebhookEvent::EVENT_PAYMENT_WAITING_FOR_CAPTURE => PaymentOperation::TYPE_WEBHOOK_RECEIVED,
            ProviderWebhookEvent::EVENT_PAYMENT_CANCELED => PaymentOperation::TYPE_WEBHOOK_RECEIVED,
            ProviderWebhookEvent::EVENT_REFUND_SUCCEEDED => PaymentOperation::TYPE_REFUND_TO_CUSTOMER,
            ProviderWebhookEvent::EVENT_PAYOUT_SUCCEEDED => PaymentOperation::TYPE_PAYOUT_STUB,
        ];
    }
}
