<?php

namespace App\Http\Controllers\Performer;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Services\Payments\PaymentLedgerService;
use Inertia\Inertia;
use Inertia\Response;

class PerformerFinanceController extends Controller
{
    public function __invoke(PaymentLedgerService $ledger): Response
    {
        $performer = request()->user();

        $entries = LedgerEntry::query()
            ->with('order')
            ->where('user_id', $performer->id)
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (LedgerEntry $entry): array => [
                'id' => $entry->id,
                'order_id' => $entry->order_id,
                'order_title' => $entry->order?->title,
                'order_url' => $entry->order ? route('performer.orders.show', $entry->order) : null,
                'account' => $entry->account,
                'direction' => $entry->direction,
                'amount' => $entry->amount,
                'currency' => $entry->currency,
                'description' => $entry->description,
                'posted_at' => $entry->posted_at?->format('d.m.Y H:i'),
            ]);

        return Inertia::render('Performer/Finance/Index', [
            'summary' => $ledger->getPerformerFinanceSummary($performer),
            'ledgerEntries' => $entries,
            'labels' => [
                'accounts' => LedgerEntry::accountLabels(),
                'directions' => LedgerEntry::directionLabels(),
            ],
        ]);
    }
}
