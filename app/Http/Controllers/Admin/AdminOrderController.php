<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BetaFeedback;
use App\Models\Dispute;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\OrderFile;
use App\Models\OrderMessage;
use App\Models\PaymentOperation;
use App\Models\Service;
use App\Models\Task;
use App\Models\TaskOffer;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $orders = Order::query()
            ->with(['customer', 'performer', 'activeDispute'])
            ->withCount(['orderMessages', 'orderFiles', 'orderEvents', 'disputes']);

        $this->applyFilters($orders, $filters);
        $this->applySort($orders, $filters['sort']);

        $paginator = $orders
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Order $order): array => $this->orderRow($order));

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $paginator,
            'filters' => $filters,
            'summary' => [
                'total' => Order::query()->count(),
                'filtered' => $paginator->total(),
                'active_disputes' => Dispute::query()->whereIn('status', Dispute::activeStatuses())->count(),
                'held_amount' => (int) Order::query()->where('payment_status', Order::PAYMENT_HELD)->sum('price'),
            ],
            'labels' => $this->labels(),
            'options' => [
                'statuses' => $this->optionPayload(['all' => 'Все статусы', ...Order::statusLabels()]),
                'payment_statuses' => $this->optionPayload(['all' => 'Все оплаты', ...Order::paymentStatusLabels()]),
                'source_types' => $this->optionPayload(['all' => 'Все источники', ...Order::sourceTypeLabels()]),
                'has_dispute' => $this->optionPayload([
                    'all' => 'Все заказы',
                    'yes' => 'Есть спор',
                    'no' => 'Без спора',
                ]),
                'sorts' => $this->optionPayload([
                    'newest' => 'Новые',
                    'oldest' => 'Старые',
                    'price_high' => 'Сначала дорогие',
                    'price_low' => 'Сначала дешевые',
                    'updated' => 'Недавно обновлены',
                    'deadline_soon' => 'Ближайший срок',
                ]),
            ],
        ]);
    }

    public function show(Order $order): Response
    {
        $order->load([
            'customer',
            'performer',
            'category',
            'service.category',
            'task.category',
            'taskOffer.task',
            'activeDispute.openedBy',
            'disputes.openedBy',
            'disputes.resolvedBy',
        ]);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $this->orderDetail($order),
            'workspace' => $this->workspaceSummary($order),
            'events' => $this->eventsQuery($order)
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (OrderEvent $event): array => $this->eventPayload($event)),
            'finance' => $this->financeSummary($order),
            'disputes' => $this->disputesPayload($order),
            'betaFeedback' => $this->betaFeedbackPayload($order),
            'labels' => $this->labels(),
            'links' => [
                'index' => route('admin.orders.index'),
                'events' => route('admin.orders.events', $order),
                'ledger' => route('admin.orders.ledger', $order),
                'finance' => route('admin.finance.index'),
                'beta_feedback' => route('admin.beta-feedback.index'),
            ],
        ]);
    }

    public function events(Request $request, Order $order): Response
    {
        $filters = $this->eventFilters($request, $order);

        $events = $this->eventsQuery($order)
            ->when($filters['type'] !== 'all', fn (Builder $query) => $query->where('type', $filters['type']));

        $filters['sort'] === 'oldest'
            ? $events->oldest()
            : $events->latest();

        return Inertia::render('Admin/Orders/Events', [
            'order' => $this->orderHeader($order),
            'events' => $events
                ->paginate(30)
                ->withQueryString()
                ->through(fn (OrderEvent $event): array => $this->eventPayload($event)),
            'filters' => $filters,
            'options' => [
                'types' => $this->optionPayload(['all' => 'Все события', ...$this->eventLabelsFor($order)]),
                'sorts' => $this->optionPayload([
                    'newest' => 'Новые',
                    'oldest' => 'Старые',
                ]),
            ],
            'links' => [
                'show' => route('admin.orders.show', $order),
                'ledger' => route('admin.orders.ledger', $order),
            ],
        ]);
    }

    public function ledger(Order $order): Response
    {
        $order->load(['paymentOperations.user', 'ledgerEntries.user']);

        return Inertia::render('Admin/Orders/Ledger', [
            'order' => $this->orderHeader($order),
            'operations' => $order->paymentOperations()
                ->with('user')
                ->latest()
                ->paginate(25, ['*'], 'operations_page')
                ->withQueryString()
                ->through(fn (PaymentOperation $operation): array => $this->paymentOperationPayload($operation)),
            'ledgerEntries' => $order->ledgerEntries()
                ->with(['user', 'paymentOperation'])
                ->latest('posted_at')
                ->paginate(40, ['*'], 'ledger_page')
                ->withQueryString()
                ->through(fn (LedgerEntry $entry): array => $this->ledgerEntryPayload($entry)),
            'accountSummary' => $this->ledgerAccountSummary($order),
            'labels' => [
                'operationTypes' => PaymentOperation::typeLabels(),
                'operationStatuses' => PaymentOperation::statusLabels(),
                'ledgerAccounts' => LedgerEntry::accountLabels(),
                'ledgerDirections' => LedgerEntry::directionLabels(),
            ],
            'links' => [
                'show' => route('admin.orders.show', $order),
                'events' => route('admin.orders.events', $order),
                'finance' => route('admin.finance.index'),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        $status = $request->string('status')->toString();
        $paymentStatus = $request->string('payment_status')->toString();
        $sourceType = $request->string('source_type')->toString();
        $hasDispute = $request->string('has_dispute')->toString();
        $sort = $request->string('sort')->toString();

        return [
            'q' => trim($request->string('q')->toString()),
            'status' => in_array($status, ['all', ...Order::statuses()], true) ? $status : 'all',
            'payment_status' => in_array($paymentStatus, ['all', ...Order::paymentStatuses()], true) ? $paymentStatus : 'all',
            'source_type' => in_array($sourceType, ['all', ...Order::sourceTypes()], true) ? $sourceType : 'all',
            'has_dispute' => in_array($hasDispute, ['all', 'yes', 'no'], true) ? $hasDispute : 'all',
            'date_from' => $this->dateFilter($request->string('date_from')->toString()),
            'date_to' => $this->dateFilter($request->string('date_to')->toString()),
            'price_min' => $this->integerFilter($request->string('price_min')->toString()),
            'price_max' => $this->integerFilter($request->string('price_max')->toString()),
            'customer_id' => $this->integerFilter($request->string('customer_id')->toString()),
            'performer_id' => $this->integerFilter($request->string('performer_id')->toString()),
            'sort' => in_array($sort, ['newest', 'oldest', 'price_high', 'price_low', 'updated', 'deadline_soon'], true) ? $sort : 'newest',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $q = $filters['q'];

                $query->where(function (Builder $query) use ($q): void {
                    $query
                        ->where('title', 'like', "%{$q}%")
                        ->orWhereHas('customer', fn (Builder $query) => $query->where('email', 'like', "%{$q}%"))
                        ->orWhereHas('performer', fn (Builder $query) => $query->where('email', 'like', "%{$q}%"));

                    if (ctype_digit($q)) {
                        $query->orWhere('id', (int) $q);
                    }
                });
            })
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['payment_status'] !== 'all', fn (Builder $query) => $query->where('payment_status', $filters['payment_status']))
            ->when($filters['source_type'] !== 'all', fn (Builder $query) => $query->where('source_type', $filters['source_type']))
            ->when($filters['has_dispute'] === 'yes', fn (Builder $query) => $query->whereHas('disputes'))
            ->when($filters['has_dispute'] === 'no', fn (Builder $query) => $query->whereDoesntHave('disputes'))
            ->when($filters['date_from'] !== '', fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->when($filters['price_min'] !== '', fn (Builder $query) => $query->where('price', '>=', (int) $filters['price_min']))
            ->when($filters['price_max'] !== '', fn (Builder $query) => $query->where('price', '<=', (int) $filters['price_max']))
            ->when($filters['customer_id'] !== '', fn (Builder $query) => $query->where('customer_id', (int) $filters['customer_id']))
            ->when($filters['performer_id'] !== '', fn (Builder $query) => $query->where('performer_id', (int) $filters['performer_id']));
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'oldest' => $query->oldest(),
            'price_high' => $query->orderByDesc('price')->orderByDesc('id'),
            'price_low' => $query->orderBy('price')->orderByDesc('id'),
            'updated' => $query->orderByDesc('updated_at')->orderByDesc('id'),
            'deadline_soon' => $query->orderByRaw('case when started_at is null then 1 else 0 end')
                ->orderBy('started_at')
                ->orderBy('delivery_days')
                ->orderByDesc('id'),
            default => $query->latest(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function orderRow(Order $order): array
    {
        return [
            'id' => $order->id,
            'title' => $order->title,
            'status' => $order->status,
            'status_label' => Order::statusLabels()[$order->status] ?? $order->status,
            'payment_status' => $order->payment_status,
            'payment_status_label' => Order::paymentStatusLabels()[$order->payment_status] ?? $order->payment_status,
            'source_type' => $order->source_type,
            'source_type_label' => Order::sourceTypeLabels()[$order->source_type] ?? $order->source_type,
            'customer' => $this->userSummary($order->customer),
            'performer' => $this->userSummary($order->performer),
            'price' => $order->price,
            'platform_fee_amount' => $order->platform_fee_amount,
            'performer_amount' => $order->performer_amount,
            'delivery_days' => $order->delivery_days,
            'created_at' => $this->formatDate($order->created_at),
            'updated_at' => $this->formatDate($order->updated_at),
            'submitted_at' => $this->formatDate($order->submitted_at),
            'review_hold_until' => $this->formatDate($order->review_hold_until),
            'has_active_dispute' => $order->activeDispute !== null,
            'active_dispute_id' => $order->activeDispute?->id,
            'messages_count' => $order->order_messages_count,
            'files_count' => $order->order_files_count,
            'events_count' => $order->order_events_count,
            'disputes_count' => $order->disputes_count,
            'show_url' => route('admin.orders.show', $order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderDetail(Order $order): array
    {
        return [
            ...$this->orderHeader($order),
            'description' => $order->description,
            'platform_fee_percent' => $order->platform_fee_percent,
            'started_at' => $this->formatDate($order->started_at),
            'submitted_at' => $this->formatDate($order->submitted_at),
            'completed_at' => $this->formatDate($order->completed_at),
            'canceled_at' => $this->formatDate($order->canceled_at),
            'review_hold_started_at' => $this->formatDate($order->review_hold_started_at),
            'review_hold_until' => $this->formatDate($order->review_hold_until),
            'auto_release_at' => $this->formatDate($order->auto_release_at),
            'released_at' => $this->formatDate($order->released_at),
            'release_reason' => $order->release_reason,
            'release_reason_label' => Order::releaseReasonLabel($order->release_reason),
            'customer' => $this->participantPayload($order->customer),
            'performer' => $this->participantPayload($order->performer),
            'source' => $this->sourcePayload($order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderHeader(Order $order): array
    {
        return [
            'id' => $order->id,
            'title' => $order->title,
            'source_type' => $order->source_type,
            'source_type_label' => Order::sourceTypeLabels()[$order->source_type] ?? $order->source_type,
            'status' => $order->status,
            'status_label' => Order::statusLabels()[$order->status] ?? $order->status,
            'payment_status' => $order->payment_status,
            'payment_status_label' => Order::paymentStatusLabels()[$order->payment_status] ?? $order->payment_status,
            'price' => $order->price,
            'platform_fee_amount' => $order->platform_fee_amount,
            'performer_amount' => $order->performer_amount,
            'delivery_days' => $order->delivery_days,
            'created_at' => $this->formatDate($order->created_at),
            'updated_at' => $this->formatDate($order->updated_at),
            'show_url' => route('admin.orders.show', $order),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function participantPayload(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => User::roleLabels()[$user->role] ?? $user->role,
            'status' => $user->status,
            'status_label' => User::statusLabels()[$user->status] ?? $user->status,
            'performer_rating' => $user->performer_rating !== null ? (float) $user->performer_rating : null,
            'admin_url' => route('admin.users.show', $user),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function userSummary(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'admin_url' => route('admin.users.show', $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sourcePayload(Order $order): array
    {
        if ($order->source_type === Order::SOURCE_SERVICE) {
            return [
                'type' => $order->source_type,
                'label' => Order::sourceTypeLabels()[$order->source_type],
                'service' => $order->service ? $this->servicePayload($order->service) : null,
                'task' => null,
                'task_offer' => null,
            ];
        }

        return [
            'type' => $order->source_type,
            'label' => Order::sourceTypeLabels()[$order->source_type] ?? $order->source_type,
            'service' => null,
            'task' => $order->task ? $this->taskPayload($order->task) : null,
            'task_offer' => $order->taskOffer ? $this->taskOfferPayload($order->taskOffer) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function servicePayload(Service $service): array
    {
        return [
            'id' => $service->id,
            'title' => $service->title,
            'category' => $service->category?->name,
            'public_url' => "/services/{$service->slug}",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taskPayload(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status,
            'category' => $task->category?->name,
            'public_url' => $task->status === Task::STATUS_PUBLISHED ? "/tasks/{$task->slug}" : null,
            'customer_url' => $task->customer ? route('admin.users.show', $task->customer) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taskOfferPayload(TaskOffer $offer): array
    {
        return [
            'id' => $offer->id,
            'price' => $offer->price,
            'delivery_days' => $offer->delivery_days,
            'status' => $offer->status,
            'performer_url' => $offer->performer ? route('admin.users.show', $offer->performer) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workspaceSummary(Order $order): array
    {
        return [
            'messages_count' => $order->orderMessages()->count(),
            'files_count' => $order->orderFiles()->count(),
            'messages' => $order->orderMessages()
                ->with('user')
                ->reorder()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (OrderMessage $message): array => [
                    'id' => $message->id,
                    'body' => Str::limit($message->body, 260),
                    'type' => $message->type,
                    'author' => $message->user?->name ?? 'Система',
                    'author_role' => $this->orderRoleLabel($message->user, $order),
                    'created_at' => $this->formatDate($message->created_at),
                ]),
            'files' => $order->orderFiles()
                ->with('user')
                ->reorder()
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (OrderFile $file): array => [
                    'id' => $file->id,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'size' => $file->size,
                    'status' => $file->status,
                    'moderation_status' => $file->moderation_status,
                    'user' => $file->user?->name,
                    'user_role' => $this->orderRoleLabel($file->user, $order),
                    'created_at' => $this->formatDate($file->created_at),
                ]),
        ];
    }

    /**
     * @return Builder<OrderEvent>
     */
    private function eventsQuery(Order $order): Builder
    {
        return OrderEvent::query()
            ->with('user')
            ->where('order_id', $order->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(OrderEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type,
            'type_label' => $this->eventLabels()[$event->type] ?? $event->type,
            'actor' => $event->user?->name ?? 'Система',
            'summary' => $this->payloadSummary($event->payload),
            'created_at' => $this->formatDate($event->created_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function financeSummary(Order $order): array
    {
        return [
            'operations_count' => $order->paymentOperations()->count(),
            'ledger_entries_count' => $order->ledgerEntries()->count(),
            'operations' => $order->paymentOperations()
                ->with('user')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (PaymentOperation $operation): array => $this->paymentOperationPayload($operation)),
            'ledger_entries' => $order->ledgerEntries()
                ->with(['user', 'paymentOperation'])
                ->latest('posted_at')
                ->limit(10)
                ->get()
                ->map(fn (LedgerEntry $entry): array => $this->ledgerEntryPayload($entry)),
            'account_summary' => $this->ledgerAccountSummary($order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentOperationPayload(PaymentOperation $operation): array
    {
        return [
            'id' => $operation->id,
            'type' => $operation->type,
            'type_label' => PaymentOperation::typeLabels()[$operation->type] ?? $operation->type,
            'status' => $operation->status,
            'status_label' => PaymentOperation::statusLabels()[$operation->status] ?? $operation->status,
            'amount' => $operation->amount,
            'currency' => $operation->currency,
            'provider' => $operation->provider,
            'description' => $operation->description,
            'user' => $operation->user?->name,
            'created_at' => $this->formatDate($operation->created_at),
            'succeeded_at' => $this->formatDate($operation->succeeded_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ledgerEntryPayload(LedgerEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'payment_operation_id' => $entry->payment_operation_id,
            'account' => $entry->account,
            'account_label' => LedgerEntry::accountLabels()[$entry->account] ?? $entry->account,
            'direction' => $entry->direction,
            'direction_label' => LedgerEntry::directionLabels()[$entry->direction] ?? $entry->direction,
            'amount' => $entry->amount,
            'currency' => $entry->currency,
            'description' => $entry->description,
            'user' => $entry->user?->name,
            'posted_at' => $this->formatDate($entry->posted_at),
            'created_at' => $this->formatDate($entry->created_at),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ledgerAccountSummary(Order $order): array
    {
        return LedgerEntry::query()
            ->where('order_id', $order->id)
            ->selectRaw('account, direction, coalesce(sum(amount), 0) as amount')
            ->groupBy('account', 'direction')
            ->orderBy('account')
            ->orderBy('direction')
            ->get()
            ->map(fn (LedgerEntry $entry): array => [
                'account' => $entry->account,
                'account_label' => LedgerEntry::accountLabels()[$entry->account] ?? $entry->account,
                'direction' => $entry->direction,
                'direction_label' => LedgerEntry::directionLabels()[$entry->direction] ?? $entry->direction,
                'amount' => (int) $entry->amount,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function disputesPayload(Order $order): array
    {
        return [
            'active' => $order->activeDispute ? $this->disputePayload($order->activeDispute) : null,
            'items' => $order->disputes()
                ->with(['openedBy', 'resolvedBy'])
                ->latest()
                ->get()
                ->map(fn (Dispute $dispute): array => $this->disputePayload($dispute)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function disputePayload(Dispute $dispute): array
    {
        return [
            'id' => $dispute->id,
            'status' => $dispute->status,
            'status_label' => Dispute::statusLabels()[$dispute->status] ?? $dispute->status,
            'reason' => $dispute->reason,
            'reason_label' => Dispute::reasonLabels()[$dispute->reason] ?? $dispute->reason,
            'resolution' => $dispute->resolution,
            'resolution_label' => $dispute->resolution ? (Dispute::resolutionLabels()[$dispute->resolution] ?? $dispute->resolution) : null,
            'opened_by' => $dispute->openedBy?->name,
            'resolved_by' => $dispute->resolvedBy?->name,
            'created_at' => $this->formatDate($dispute->created_at),
            'resolved_at' => $this->formatDate($dispute->resolved_at),
            'show_url' => route('moderator.disputes.show', $dispute),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function betaFeedbackPayload(Order $order): array
    {
        return BetaFeedback::query()
            ->where(function (Builder $query) use ($order): void {
                $query
                    ->where('page_url', 'like', "%/orders/{$order->id}%")
                    ->orWhere('description', 'like', "%#{$order->id}%")
                    ->orWhere('title', 'like', "%#{$order->id}%");
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (BetaFeedback $feedback): array => [
                'id' => $feedback->id,
                'title' => $feedback->title,
                'type' => $feedback->type,
                'severity' => $feedback->severity,
                'status' => $feedback->status,
                'created_at' => $this->formatDate($feedback->created_at),
                'show_url' => route('admin.beta-feedback.show', $feedback),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{type: string, sort: string}
     */
    private function eventFilters(Request $request, Order $order): array
    {
        $types = ['all', ...array_keys($this->eventLabelsFor($order))];
        $type = $request->string('type')->toString();
        $sort = $request->string('sort')->toString();

        return [
            'type' => in_array($type, $types, true) ? $type : 'all',
            'sort' => in_array($sort, ['newest', 'oldest'], true) ? $sort : 'newest',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function eventLabelsFor(Order $order): array
    {
        $labels = $this->eventLabels();

        return $order->orderEvents()
            ->reorder()
            ->select('type')
            ->distinct()
            ->pluck('type')
            ->mapWithKeys(fn (string $type): array => [$type => $labels[$type] ?? $type])
            ->all();
    }

    private function payloadSummary(?array $payload): ?string
    {
        if ($payload === null || $payload === []) {
            return null;
        }

        return collect($payload)
            ->take(6)
            ->map(function (mixed $value, string|int $key): string {
                $rendered = is_scalar($value)
                    ? (string) $value
                    : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                return $key.': '.Str::limit($rendered ?: '', 120);
            })
            ->implode('; ');
    }

    private function orderRoleLabel(?User $user, Order $order): string
    {
        if (! $user) {
            return 'Система';
        }

        if ($user->id === $order->customer_id) {
            return 'Заказчик';
        }

        if ($user->id === $order->performer_id) {
            return 'Исполнитель';
        }

        return User::roleLabels()[$user->role] ?? $user->role;
    }

    private function dateFilter(string $value): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
    }

    private function integerFilter(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return ctype_digit($value) ? $value : '';
    }

    /**
     * @param  array<string, string>  $labels
     * @return array<int, array{value: string, label: string}>
     */
    private function optionPayload(array $labels): array
    {
        return collect($labels)
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function labels(): array
    {
        return [
            'statuses' => Order::statusLabels(),
            'paymentStatuses' => Order::paymentStatusLabels(),
            'sourceTypes' => Order::sourceTypeLabels(),
            'userStatuses' => User::statusLabels(),
            'roles' => User::roleLabels(),
            'events' => $this->eventLabels(),
            'disputeStatuses' => Dispute::statusLabels(),
            'disputeReasons' => Dispute::reasonLabels(),
            'disputeResolutions' => Dispute::resolutionLabels(),
            'operationTypes' => PaymentOperation::typeLabels(),
            'operationStatuses' => PaymentOperation::statusLabels(),
            'ledgerAccounts' => LedgerEntry::accountLabels(),
            'ledgerDirections' => LedgerEntry::directionLabels(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function eventLabels(): array
    {
        return [
            OrderEvent::TYPE_ORDER_CREATED => 'Заказ создан',
            OrderEvent::TYPE_PAYMENT_STUB_PAID => 'Оплата отмечена',
            OrderEvent::TYPE_WORK_SUBMITTED => 'Работа отправлена',
            OrderEvent::TYPE_REVIEW_HOLD_STARTED => 'Срок проверки запущен',
            OrderEvent::TYPE_REVISION_REQUESTED => 'Запрошена доработка',
            OrderEvent::TYPE_ORDER_COMPLETED => 'Заказ завершен',
            OrderEvent::TYPE_FUNDS_RELEASED => 'Оплата разблокирована',
            OrderEvent::TYPE_ORDER_CANCELED => 'Заказ отменен',
            OrderEvent::TYPE_DISPUTE_OPENED => 'Спор открыт',
            OrderEvent::TYPE_DISPUTE_MESSAGE_SENT => 'Сообщение в споре',
            OrderEvent::TYPE_DISPUTE_UNDER_REVIEW => 'Спор на рассмотрении',
            OrderEvent::TYPE_DISPUTE_RESOLVED => 'Спор решен',
            OrderEvent::TYPE_FUNDS_REFUNDED => 'Средства возвращены',
            OrderEvent::TYPE_REVISION_REQUESTED_BY_MODERATOR => 'Доработка по решению модератора',
            OrderEvent::TYPE_MESSAGE_SENT => 'Сообщение отправлено',
            OrderEvent::TYPE_FILE_UPLOADED => 'Файл загружен',
            OrderEvent::TYPE_CONTACT_BLOCKED => 'Контакт заблокирован',
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        return $value?->format('d.m.Y H:i');
    }
}
