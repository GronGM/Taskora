<?php

namespace App\Http\Middleware;

use App\Services\Messages\ConversationReadService;
use App\Services\Notifications\NotificationService;
use App\Support\BetaAccess;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ] : null,
                'dashboard_url' => $user?->dashboardPath(),
            ],
            'notifications' => fn (): array => $user
                ? [
                    'unread_count' => app(NotificationService::class)->unreadCount($user),
                    'latest' => app(NotificationService::class)->latestPayload($user),
                ]
                : [
                    'unread_count' => 0,
                    'latest' => [],
                ],
            'messages' => fn (): array => $user
                ? [
                    'unread_count' => app(ConversationReadService::class)->unreadCount($user),
                ]
                : [
                    'unread_count' => 0,
                ],
            'account' => fn (): ?array => $user
                ? [
                    'avatar_url' => $user->accountAvatarUrl(),
                    'wallet' => $this->walletPayload($user),
                ]
                : null,
            'testMode' => [
                'enabled' => BetaAccess::shouldShowTestModeBanner(),
                'message' => BetaAccess::BANNER_TEXT,
                'debug_warning' => BetaAccess::debugWarning(),
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'status' => fn (): ?string => $request->session()->get('status'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function walletPayload(\App\Models\User $user): ?array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            "wallet:{$user->id}",
            30,
            function () use ($user): ?array {
                if ($user->isPerformer()) {
                    $summary = app(\App\Services\Payments\PaymentLedgerService::class)->getPerformerFinanceSummary($user);

                    return [
                        'total' => (int) $summary['available_amount'] + (int) $summary['pending_amount'],
                        'rows' => [
                            ['label' => 'Доступно к выводу', 'amount' => (int) $summary['available_amount'], 'tone' => 'emerald'],
                            ['label' => 'Ожидает разблокировки', 'amount' => (int) $summary['pending_amount'], 'tone' => 'amber'],
                        ],
                        'url' => route('performer.finance.index'),
                    ];
                }

                if ($user->isCustomer()) {
                    $reserved = (int) $user->customerOrders()
                        ->where('payment_status', \App\Models\Order::PAYMENT_HELD)
                        ->sum('price');

                    return [
                        'total' => $reserved,
                        'rows' => [
                            ['label' => 'Зарезервировано в заказах', 'amount' => $reserved, 'tone' => 'amber'],
                        ],
                        'url' => route('customer.orders.index'),
                    ];
                }

                return null;
            },
        );
    }
}
