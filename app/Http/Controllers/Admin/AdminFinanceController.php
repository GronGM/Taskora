<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\PaymentOperation;
use App\Models\ProviderWebhookEvent;
use Inertia\Inertia;
use Inertia\Response;

class AdminFinanceController extends Controller
{
    public function __invoke(): Response
    {
        $operations = PaymentOperation::query()
            ->with('order')
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (PaymentOperation $operation): array => [
                'id' => $operation->id,
                'order_id' => $operation->order_id,
                'order_title' => $operation->order?->title,
                'type' => $operation->type,
                'status' => $operation->status,
                'amount' => $operation->amount,
                'currency' => $operation->currency,
                'provider' => $operation->provider,
                'created_at' => $operation->created_at?->format('d.m.Y H:i'),
            ]);

        $webhookEvents = ProviderWebhookEvent::query()
            ->latest()
            ->limit(15)
            ->get()
            ->map(fn (ProviderWebhookEvent $event): array => [
                'id' => $event->id,
                'provider' => $event->provider,
                'event_id' => $event->event_id,
                'event_type' => $event->event_type,
                'status' => $event->status,
                'processed_at' => $event->processed_at?->format('d.m.Y H:i'),
                'created_at' => $event->created_at?->format('d.m.Y H:i'),
            ]);

        return Inertia::render('Admin/Finance/Index', [
            'summary' => [
                'escrow_amount' => $this->ledgerBalance(LedgerEntry::ACCOUNT_ESCROW),
                'platform_fee_amount' => $this->ledgerBalance(LedgerEntry::ACCOUNT_PLATFORM_FEE),
                'paid_to_performers_amount' => $this->ledgerBalance(LedgerEntry::ACCOUNT_PERFORMER_AVAILABLE),
                'refunded_to_customers_amount' => $this->ledgerCreditTotal(LedgerEntry::ACCOUNT_CUSTOMER_REFUND),
                'payment_operations_count' => PaymentOperation::query()->count(),
            ],
            'operations' => $operations,
            'webhookEvents' => $webhookEvents,
            'labels' => [
                'operationTypes' => PaymentOperation::typeLabels(),
                'operationStatuses' => PaymentOperation::statusLabels(),
                'webhookStatuses' => ProviderWebhookEvent::statusLabels(),
            ],
        ]);
    }

    private function ledgerBalance(string $account): int
    {
        return (int) (LedgerEntry::query()
            ->where('account', $account)
            ->selectRaw("coalesce(sum(case when direction = 'credit' then amount else -amount end), 0) as balance")
            ->value('balance') ?? 0);
    }

    private function ledgerCreditTotal(string $account): int
    {
        return (int) LedgerEntry::query()
            ->where('account', $account)
            ->where('direction', LedgerEntry::DIRECTION_CREDIT)
            ->sum('amount');
    }
}
