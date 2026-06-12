<?php

namespace App\Http\Controllers\Messages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreDisputeMessageRequest;
use App\Http\Requests\Order\StoreOrderMessageRequest;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\User;
use App\Services\Messages\ConversationReadService;
use App\Services\Messages\MessageDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    private const PER_PAGE = 12;

    public function index(Request $request, ConversationReadService $reads): Response
    {
        $user = $request->user();
        $filters = $this->filters($request);
        $conversations = collect();

        if ($filters['tab'] !== 'disputes') {
            $conversations = $conversations->merge($this->orderConversations($user, $reads, $filters));
        }

        if ($filters['tab'] !== 'orders') {
            $conversations = $conversations->merge($this->disputeConversations($user, $reads, $filters));
        }

        $conversations = $this->filterConversationPayloads($conversations, $filters);
        $conversations = $this->sortConversationPayloads($conversations, $filters['sort']);
        $paginator = $this->paginate($conversations, $request);

        return Inertia::render('Messages/Index', [
            'conversations' => $paginator->items(),
            'pagination' => $this->paginationPayload($paginator),
            'filters' => $filters,
            'tabs' => [
                ['value' => 'all', 'label' => 'Все'],
                ['value' => 'unread', 'label' => 'Непрочитанные'],
                ['value' => 'orders', 'label' => 'Заказы'],
                ['value' => 'disputes', 'label' => 'Споры'],
            ],
            'orderStatusOptions' => $this->optionPayload(Order::statusLabels()),
            'sortOptions' => [
                ['value' => 'newest', 'label' => 'Сначала новые'],
                ['value' => 'unread', 'label' => 'Сначала непрочитанные'],
            ],
        ]);
    }

    public function showOrder(Request $request, Order $order, ConversationReadService $reads): Response
    {
        Gate::authorize('viewWorkspace', $order);

        $reads->markOrderRead($request->user(), $order);

        $order->load([
            'customer',
            'performer',
            'activeDispute',
            'orderMessages.user',
        ]);

        return Inertia::render('Messages/OrderShow', [
            'conversation' => $this->orderDetailPayload($request->user(), $order),
        ]);
    }

    public function storeOrder(
        StoreOrderMessageRequest $request,
        Order $order,
        MessageDeliveryService $messages,
    ): RedirectResponse {
        $messages->sendOrderMessage($request->user(), $order, $request->validated('body'));

        return redirect()
            ->route('messages.orders.show', $order)
            ->with('success', 'Сообщение отправлено.');
    }

    public function markOrderRead(Request $request, Order $order, ConversationReadService $reads): RedirectResponse
    {
        Gate::authorize('viewWorkspace', $order);

        $reads->markOrderRead($request->user(), $order);

        return back()->with('success', 'Диалог отмечен прочитанным.');
    }

    public function showDispute(Request $request, Dispute $dispute, ConversationReadService $reads): Response
    {
        Gate::authorize('view', $dispute);

        $reads->markDisputeRead($request->user(), $dispute);

        $dispute->load([
            'openedBy',
            'resolvedBy',
            'messages.user',
            'order.customer',
            'order.performer',
        ]);

        return Inertia::render('Messages/DisputeShow', [
            'conversation' => $this->disputeDetailPayload($request->user(), $dispute),
        ]);
    }

    public function storeDispute(
        StoreDisputeMessageRequest $request,
        Dispute $dispute,
        MessageDeliveryService $messages,
    ): RedirectResponse {
        $messages->sendDisputeMessage($request->user(), $dispute, $request->validated('body'));

        return redirect()
            ->route('messages.disputes.show', $dispute)
            ->with('success', 'Сообщение отправлено.');
    }

    public function markDisputeRead(Request $request, Dispute $dispute, ConversationReadService $reads): RedirectResponse
    {
        Gate::authorize('view', $dispute);

        $reads->markDisputeRead($request->user(), $dispute);

        return back()->with('success', 'Диалог отмечен прочитанным.');
    }

    /**
     * @return array<string, string|int>
     */
    private function filters(Request $request): array
    {
        $tab = (string) $request->query('tab', 'all');
        $sort = (string) $request->query('sort', 'newest');
        $status = (string) $request->query('status', '');

        if (! in_array($tab, ['all', 'unread', 'orders', 'disputes'], true)) {
            $tab = 'all';
        }

        if (! in_array($sort, ['newest', 'unread'], true)) {
            $sort = 'newest';
        }

        if ($status !== '' && ! in_array($status, Order::statuses(), true)) {
            $status = '';
        }

        $search = Str::of((string) $request->query('q', ''))->trim()->limit(120, '')->toString();
        $activeCount = 0;
        $activeCount += $tab !== 'all' ? 1 : 0;
        $activeCount += $search !== '' ? 1 : 0;
        $activeCount += $status !== '' ? 1 : 0;
        $activeCount += $sort !== 'newest' ? 1 : 0;

        return [
            'tab' => $tab,
            'q' => $search,
            'status' => $status,
            'sort' => $sort,
            'active_count' => $activeCount,
        ];
    }

    /**
     * @param  array<string, string|int>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function orderConversations(User $user, ConversationReadService $reads, array $filters): Collection
    {
        $orders = Order::query()
            ->with(['customer', 'performer'])
            ->where(function ($query) use ($user): void {
                $query
                    ->where('customer_id', $user->id)
                    ->orWhere('performer_id', $user->id);
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->latest('updated_at')
            ->limit(200)
            ->get();

        return $orders->map(fn (Order $order): array => $this->orderConversationPayload($user, $order, $reads));
    }

    /**
     * @param  array<string, string|int>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function disputeConversations(User $user, ConversationReadService $reads, array $filters): Collection
    {
        $disputes = Dispute::query()
            ->with(['order.customer', 'order.performer', 'openedBy'])
            ->whereHas('order', function ($query) use ($user, $filters): void {
                $query->when($filters['status'] !== '', fn ($statusQuery) => $statusQuery->where('status', $filters['status']));

                if (! $user->isModerator() && ! $user->isAdmin()) {
                    $query->where(function ($participantQuery) use ($user): void {
                        $participantQuery
                            ->where('customer_id', $user->id)
                            ->orWhere('performer_id', $user->id);
                    });
                }
            })
            ->latest('updated_at')
            ->limit(200)
            ->get();

        return $disputes->map(fn (Dispute $dispute): array => $this->disputeConversationPayload($user, $dispute, $reads));
    }

    /**
     * @return array<string, mixed>
     */
    private function orderConversationPayload(User $user, Order $order, ConversationReadService $reads): array
    {
        $latest = OrderMessage::query()
            ->with('user')
            ->where('order_id', $order->id)
            ->latest()
            ->first();
        $participant = $this->otherOrderParticipant($user, $order);
        $lastActivity = $latest?->created_at ?? $order->updated_at ?? $order->created_at;

        return [
            'type' => 'order',
            'type_label' => 'Заказ',
            'id' => $order->id,
            'title' => $order->title,
            'subtitle' => $order->source_type === Order::SOURCE_TASK_OFFER ? 'Заказ из отклика' : 'Заказ из услуги',
            'participant' => $participant,
            'status' => $order->status,
            'status_label' => Order::statusLabels()[$order->status] ?? $order->status,
            'payment_status_label' => Order::paymentStatusLabels()[$order->payment_status] ?? $order->payment_status,
            'last_message' => $latest ? Str::limit($latest->body, 180) : 'Сообщений пока нет.',
            'last_message_author' => $latest ? $this->orderRoleLabel($latest->user, $order) : null,
            'last_message_at' => $lastActivity?->format('d.m.Y H:i'),
            'last_activity_ts' => $lastActivity?->getTimestamp() ?? 0,
            'unread_count' => $reads->unreadOrderCount($user, $order),
            'url' => route('messages.orders.show', $order),
            'mark_read_url' => route('messages.orders.mark-read', $order),
            'search_text' => $this->searchText($user, [
                $order->title,
                $participant['name'],
                $user->isModerator() || $user->isAdmin() ? $order->customer?->email : null,
                $user->isModerator() || $user->isAdmin() ? $order->performer?->email : null,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function disputeConversationPayload(User $user, Dispute $dispute, ConversationReadService $reads): array
    {
        $latest = DisputeMessage::query()
            ->with('user')
            ->where('dispute_id', $dispute->id)
            ->latest()
            ->first();
        $order = $dispute->order;
        $participant = $this->disputeParticipantSummary($user, $order);
        $lastActivity = $latest?->created_at ?? $dispute->updated_at ?? $dispute->created_at;

        return [
            'type' => 'dispute',
            'type_label' => 'Спор',
            'id' => $dispute->id,
            'title' => $order->title,
            'subtitle' => Dispute::reasonLabels()[$dispute->reason] ?? 'Спор по заказу',
            'participant' => $participant,
            'status' => $order->status,
            'status_label' => Order::statusLabels()[$order->status] ?? $order->status,
            'dispute_status_label' => Dispute::statusLabels()[$dispute->status] ?? $dispute->status,
            'payment_status_label' => Order::paymentStatusLabels()[$order->payment_status] ?? $order->payment_status,
            'last_message' => $latest ? Str::limit($latest->body, 180) : 'Сообщений пока нет.',
            'last_message_author' => $latest ? ($latest->is_system ? 'Система' : $this->orderRoleLabel($latest->user, $order)) : null,
            'last_message_at' => $lastActivity?->format('d.m.Y H:i'),
            'last_activity_ts' => $lastActivity?->getTimestamp() ?? 0,
            'unread_count' => $reads->unreadDisputeCount($user, $dispute),
            'url' => route('messages.disputes.show', $dispute),
            'mark_read_url' => route('messages.disputes.mark-read', $dispute),
            'search_text' => $this->searchText($user, [
                $order->title,
                $participant['name'],
                Dispute::reasonLabels()[$dispute->reason] ?? null,
                $user->isModerator() || $user->isAdmin() ? $order->customer?->email : null,
                $user->isModerator() || $user->isAdmin() ? $order->performer?->email : null,
            ]),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $conversations
     * @param  array<string, string|int>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function filterConversationPayloads(Collection $conversations, array $filters): Collection
    {
        if ($filters['tab'] === 'unread') {
            $conversations = $conversations->filter(fn (array $conversation): bool => $conversation['unread_count'] > 0);
        }

        if ($filters['q'] !== '') {
            $needle = Str::lower((string) $filters['q']);
            $conversations = $conversations->filter(
                fn (array $conversation): bool => str_contains($conversation['search_text'], $needle),
            );
        }

        return $conversations->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $conversations
     * @return Collection<int, array<string, mixed>>
     */
    private function sortConversationPayloads(Collection $conversations, string $sort): Collection
    {
        return $conversations
            ->sort(function (array $left, array $right) use ($sort): int {
                if ($sort === 'unread' && $left['unread_count'] !== $right['unread_count']) {
                    return $right['unread_count'] <=> $left['unread_count'];
                }

                return $right['last_activity_ts'] <=> $left['last_activity_ts'];
            })
            ->values()
            ->map(function (array $conversation): array {
                unset($conversation['search_text'], $conversation['last_activity_ts']);

                return $conversation;
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function paginate(Collection $items, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->query('page', 1));
        $total = $items->count();
        $pageItems = $items->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $total,
            self::PER_PAGE,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function paginationPayload(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
            'prev_page_url' => $paginator->previousPageUrl(),
            'next_page_url' => $paginator->nextPageUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderDetailPayload(User $user, Order $order): array
    {
        return [
            'id' => $order->id,
            'title' => $order->title,
            'description' => $order->description,
            'status' => $order->status,
            'status_label' => Order::statusLabels()[$order->status] ?? $order->status,
            'payment_status_label' => Order::paymentStatusLabels()[$order->payment_status] ?? $order->payment_status,
            'source_label' => Order::sourceTypeLabels()[$order->source_type] ?? $order->source_type,
            'price' => $order->price,
            'participant' => $this->otherOrderParticipant($user, $order),
            'customer' => $this->userPayload($order->customer),
            'performer' => $this->userPayload($order->performer),
            'workspace_url' => $this->workspaceUrlFor($user, $order),
            'order_url' => $this->orderUrlFor($user, $order),
            'active_dispute_url' => $order->activeDispute ? $this->disputeUrlFor($user, $order->activeDispute) : null,
            'message_url' => route('messages.orders.store', $order),
            'mark_read_url' => route('messages.orders.mark-read', $order),
            'can_reply' => Gate::allows('sendMessage', $order),
            'messages' => $order->orderMessages->map(fn (OrderMessage $message): array => $this->orderMessagePayload($user, $message, $order))->values(),
            'warning' => 'Не передавайте контакты, ссылки на оплату и договоренности вне Таскоры. Сообщения проходят защитную проверку ContactGuard.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function disputeDetailPayload(User $user, Dispute $dispute): array
    {
        $order = $dispute->order;

        return [
            'id' => $dispute->id,
            'status' => $dispute->status,
            'status_label' => Dispute::statusLabels()[$dispute->status] ?? $dispute->status,
            'reason_label' => Dispute::reasonLabels()[$dispute->reason] ?? $dispute->reason,
            'description' => $dispute->description,
            'resolution_label' => $dispute->resolution ? (Dispute::resolutionLabels()[$dispute->resolution] ?? $dispute->resolution) : null,
            'moderator_comment' => $dispute->moderator_comment,
            'order' => [
                'id' => $order->id,
                'title' => $order->title,
                'status_label' => Order::statusLabels()[$order->status] ?? $order->status,
                'payment_status_label' => Order::paymentStatusLabels()[$order->payment_status] ?? $order->payment_status,
                'price' => $order->price,
                'workspace_url' => $this->workspaceUrlFor($user, $order),
                'order_url' => $this->orderUrlFor($user, $order),
            ],
            'participants' => [
                'customer' => $this->userPayload($order->customer),
                'performer' => $this->userPayload($order->performer),
                'opened_by' => $this->userPayload($dispute->openedBy),
            ],
            'dispute_url' => $this->disputeUrlFor($user, $dispute),
            'message_url' => route('messages.disputes.store', $dispute),
            'mark_read_url' => route('messages.disputes.mark-read', $dispute),
            'can_reply' => Gate::allows('message', $dispute),
            'messages' => $dispute->messages->map(fn (DisputeMessage $message): array => $this->disputeMessagePayload($user, $message, $order))->values(),
            'warning' => 'Не передавайте контакты, ссылки на оплату и договоренности вне Таскоры. Спор рассматривается внутри платформы.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderMessagePayload(User $viewer, OrderMessage $message, Order $order): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'author' => $message->user?->name ?? 'Система',
            'author_role' => $this->orderRoleLabel($message->user, $order),
            'is_own' => $message->user_id === $viewer->id,
            'is_system' => $message->type === OrderMessage::TYPE_SYSTEM_MESSAGE,
            'created_at' => $message->created_at?->format('d.m.Y H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function disputeMessagePayload(User $viewer, DisputeMessage $message, Order $order): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'author' => $message->user?->name ?? 'Система',
            'author_role' => $message->is_system ? 'Система' : $this->orderRoleLabel($message->user, $order),
            'is_own' => $message->user_id === $viewer->id,
            'is_system' => $message->is_system,
            'created_at' => $message->created_at?->format('d.m.Y H:i'),
        ];
    }

    /**
     * @return array{id: int|null, name: string, role_label: string}
     */
    private function otherOrderParticipant(User $user, Order $order): array
    {
        $participant = $order->customer_id === $user->id ? $order->performer : $order->customer;

        return [
            'id' => $participant?->id,
            'name' => $participant?->name ?? 'Участник заказа',
            'role_label' => $participant ? $this->userRoleLabel($participant) : 'Участник',
        ];
    }

    /**
     * @return array{id: int|null, name: string, role_label: string}
     */
    private function disputeParticipantSummary(User $user, Order $order): array
    {
        if ($user->isModerator() || $user->isAdmin()) {
            return [
                'id' => null,
                'name' => trim(($order->customer?->name ?? 'Заказчик').' / '.($order->performer?->name ?? 'Исполнитель')),
                'role_label' => 'Участники заказа',
            ];
        }

        return $this->otherOrderParticipant($user, $order);
    }

    /**
     * @return array{id: int|null, name: string|null, role_label: string|null}
     */
    private function userPayload(?User $user): array
    {
        return [
            'id' => $user?->id,
            'name' => $user?->name,
            'role_label' => $user ? $this->userRoleLabel($user) : null,
        ];
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

        return $this->userRoleLabel($user);
    }

    private function userRoleLabel(User $user): string
    {
        return User::roleLabels()[$user->role] ?? 'Пользователь';
    }

    private function workspaceUrlFor(User $user, Order $order): ?string
    {
        if ($order->customer_id === $user->id) {
            return route('customer.orders.workspace', $order);
        }

        if ($order->performer_id === $user->id) {
            return route('performer.orders.workspace', $order);
        }

        return null;
    }

    private function orderUrlFor(User $user, Order $order): ?string
    {
        if ($order->customer_id === $user->id) {
            return route('customer.orders.show', $order);
        }

        if ($order->performer_id === $user->id) {
            return route('performer.orders.show', $order);
        }

        if ($user->isAdmin()) {
            return route('admin.orders.show', $order);
        }

        return null;
    }

    private function disputeUrlFor(User $user, Dispute $dispute): string
    {
        if ($user->isModerator() || $user->isAdmin()) {
            return route('moderator.disputes.show', $dispute);
        }

        return $user->isPerformer()
            ? route('performer.disputes.show', $dispute)
            : route('customer.disputes.show', $dispute);
    }

    /**
     * @param  array<int, string|null>  $parts
     */
    private function searchText(User $user, array $parts): string
    {
        return Str::lower(implode(' ', array_filter($parts)));
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
}
