<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockUserRequest;
use App\Http\Requests\Admin\UpdateUserAdminNoteRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\BetaFeedback;
use App\Models\ModerationFlag;
use App\Models\Order;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use App\Models\UserAdminEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filters($request);

        $users = User::query()
            ->with('performerProfile')
            ->withCount(['customerOrders', 'performerOrders', 'tasks', 'services'])
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $query) use ($filters): void {
                    $query
                        ->where('name', 'like', "%{$filters['q']}%")
                        ->orWhere('email', 'like', "%{$filters['q']}%");
                });
            })
            ->when($filters['role'] !== 'all', fn (Builder $query) => $query->where('role', $filters['role']))
            ->when($filters['status'] !== 'all', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['has_performer_profile'] === 'yes', fn (Builder $query) => $query->whereHas('performerProfile'))
            ->when($filters['has_orders'] === 'yes', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->whereHas('customerOrders')->orWhereHas('performerOrders');
                });
            });

        $this->applySort($users, $filters['sort']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users->paginate(50)->withQueryString()->through(fn (User $user): array => $this->userRow($user)),
            'filters' => $filters,
            'summary' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('status', User::STATUS_ACTIVE)->count(),
                'blocked' => User::query()->where('status', User::STATUS_BLOCKED)->count(),
                'admins' => User::query()->where('role', User::ROLE_ADMIN)->count(),
            ],
            'labels' => [
                'roles' => User::roleLabels(),
                'statuses' => User::statusLabels(),
            ],
        ]);
    }

    public function show(User $user): Response
    {
        $user->load(['performerProfile', 'blockedBy']);

        return Inertia::render('Admin/Users/Show', [
            'user' => $this->userDetail($user),
            'related' => $this->relatedPayload($user),
            'events' => $user->adminEvents()
                ->with('actorUser')
                ->limit(20)
                ->get()
                ->map(fn (UserAdminEvent $event): array => $this->eventPayload($event)),
            'labels' => [
                'roles' => User::roleLabels(),
                'statuses' => User::statusLabels(),
                'events' => $this->eventLabels(),
            ],
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Edit', [
            'user' => $this->userForm($user),
            'roleOptions' => $this->optionPayload(User::roleLabels()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $oldValues = [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'admin_note' => $user->admin_note,
        ];

        DB::transaction(function () use ($request, $user, $validated, $oldValues): void {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $validated['role'],
                'admin_note' => $validated['admin_note'] ?? null,
            ]);

            if ($oldValues['role'] !== $user->role) {
                $this->recordEvent(
                    $user,
                    $request->user(),
                    UserAdminEvent::TYPE_ROLE_CHANGED,
                    ['role' => $oldValues['role']],
                    ['role' => $user->role],
                );
            }

            if (($oldValues['admin_note'] ?? null) !== ($user->admin_note ?? null)) {
                $this->recordEvent(
                    $user,
                    $request->user(),
                    UserAdminEvent::TYPE_ADMIN_NOTE_UPDATED,
                    ['admin_note' => $oldValues['admin_note']],
                    ['admin_note' => $user->admin_note],
                );
            }
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Пользователь обновлен.');
    }

    public function block(BlockUserRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $this->ensureCanBlock($actor, $user);

        $validated = $request->validated();
        $oldValues = $this->statusValues($user);

        DB::transaction(function () use ($actor, $user, $validated, $oldValues): void {
            $user->forceFill([
                'status' => User::STATUS_BLOCKED,
                'blocked_at' => now(),
                'blocked_by' => $actor->id,
                'block_reason' => $validated['reason'],
            ])->save();

            $this->recordEvent(
                $user,
                $actor,
                UserAdminEvent::TYPE_USER_BLOCKED,
                $oldValues,
                $this->statusValues($user),
                $validated['reason'],
            );
        });

        return back()->with('success', 'Пользователь заблокирован.');
    }

    public function unblock(Request $request, User $user): RedirectResponse
    {
        $oldValues = $this->statusValues($user);

        DB::transaction(function () use ($request, $user, $oldValues): void {
            $user->forceFill([
                'status' => User::STATUS_ACTIVE,
                'blocked_at' => null,
                'blocked_by' => null,
                'block_reason' => null,
            ])->save();

            $this->recordEvent(
                $user,
                $request->user(),
                UserAdminEvent::TYPE_USER_UNBLOCKED,
                $oldValues,
                $this->statusValues($user),
            );
        });

        return back()->with('success', 'Пользователь разблокирован.');
    }

    public function updateAdminNote(UpdateUserAdminNoteRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $oldNote = $user->admin_note;

        DB::transaction(function () use ($request, $user, $validated, $oldNote): void {
            $user->update(['admin_note' => $validated['admin_note'] ?? null]);

            if (($oldNote ?? null) !== ($user->admin_note ?? null)) {
                $this->recordEvent(
                    $user,
                    $request->user(),
                    UserAdminEvent::TYPE_ADMIN_NOTE_UPDATED,
                    ['admin_note' => $oldNote],
                    ['admin_note' => $user->admin_note],
                );
            }
        });

        return back()->with('success', 'Админская заметка обновлена.');
    }

    /**
     * @return array{q: string, role: string, status: string, has_performer_profile: string, has_orders: string, sort: string}
     */
    private function filters(Request $request): array
    {
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();
        $profile = $request->string('has_performer_profile')->toString();
        $orders = $request->string('has_orders')->toString();
        $sort = $request->string('sort')->toString();

        return [
            'q' => trim($request->string('q')->toString()),
            'role' => in_array($role, ['all', ...User::roles()], true) ? $role : 'all',
            'status' => in_array($status, ['all', ...User::statuses()], true) ? $status : 'all',
            'has_performer_profile' => in_array($profile, ['all', 'yes'], true) ? $profile : 'all',
            'has_orders' => in_array($orders, ['all', 'yes'], true) ? $orders : 'all',
            'sort' => in_array($sort, ['newest', 'last_login', 'email', 'role'], true) ? $sort : 'newest',
        ];
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'last_login' => $query->orderByDesc('last_login_at')->orderBy('email'),
            'email' => $query->orderBy('email'),
            'role' => $query->orderBy('role')->orderBy('email'),
            default => $query->latest(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function userRow(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => User::roleLabels()[$user->role] ?? $user->role,
            'status' => $user->status,
            'status_label' => User::statusLabels()[$user->status] ?? $user->status,
            'created_at' => $this->formatDate($user->created_at),
            'last_login_at' => $this->formatDate($user->last_login_at),
            'customer_orders_count' => $user->customer_orders_count,
            'performer_orders_count' => $user->performer_orders_count,
            'services_count' => $user->services_count,
            'tasks_count' => $user->tasks_count,
            'performer_rating' => $user->performer_rating,
            'has_performer_profile' => $user->performerProfile !== null,
            'show_url' => route('admin.users.show', $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userDetail(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'role_label' => User::roleLabels()[$user->role] ?? $user->role,
            'status' => $user->status,
            'status_label' => User::statusLabels()[$user->status] ?? $user->status,
            'created_at' => $this->formatDate($user->created_at),
            'last_login_at' => $this->formatDate($user->last_login_at),
            'last_login_ip' => $this->maskIp($user->last_login_ip),
            'admin_note' => $user->admin_note,
            'blocked_at' => $this->formatDate($user->blocked_at),
            'blocked_by' => $user->blockedBy?->name,
            'block_reason' => $user->block_reason,
            'performer_rating' => $user->performer_rating,
            'performer_reviews_count' => $user->performer_reviews_count,
            'performer_completed_orders_count' => $user->performer_completed_orders_count,
            'performer_profile' => $user->performerProfile ? [
                'id' => $user->performerProfile->id,
                'display_name' => $user->performerProfile->display_name,
                'headline' => $user->performerProfile->headline,
                'verification_status' => $user->performerProfile->verification_status,
                'is_public' => $user->performerProfile->is_public,
                'public_url' => route('performers.show', $user),
            ] : null,
            'edit_url' => route('admin.users.edit', $user),
            'block_url' => route('admin.users.block', $user),
            'unblock_url' => route('admin.users.unblock', $user),
            'admin_note_url' => route('admin.users.admin-note', $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userForm(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'status_label' => User::statusLabels()[$user->status] ?? $user->status,
            'admin_note' => $user->admin_note,
            'update_url' => route('admin.users.update', $user),
            'show_url' => route('admin.users.show', $user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function relatedPayload(User $user): array
    {
        return [
            'counts' => [
                'customer_orders' => $user->customerOrders()->count(),
                'performer_orders' => $user->performerOrders()->count(),
                'tasks' => $user->tasks()->count(),
                'services' => $user->services()->count(),
                'given_reviews' => $user->givenReviews()->count(),
                'received_reviews' => $user->receivedReviews()->count(),
                'opened_disputes' => $user->openedDisputes()->count(),
                'resolved_disputes' => $user->resolvedDisputes()->count(),
                'beta_feedback' => $user->betaFeedback()->count(),
                'moderation_flags' => $user->moderationFlags()->count(),
            ],
            'customer_orders' => $this->ordersPayload($user->customerOrders()->latest()->limit(5)->get()),
            'performer_orders' => $this->ordersPayload($user->performerOrders()->latest()->limit(5)->get()),
            'tasks' => $user->tasks()->latest()->limit(5)->get()->map(fn (Task $task): array => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'created_at' => $this->formatDate($task->created_at),
            ]),
            'services' => $user->services()->latest()->limit(5)->get()->map(fn (Service $service): array => [
                'id' => $service->id,
                'title' => $service->title,
                'status' => $service->status,
                'created_at' => $this->formatDate($service->created_at),
            ]),
            'beta_feedback' => $user->betaFeedback()->latest()->limit(5)->get()->map(fn (BetaFeedback $feedback): array => [
                'id' => $feedback->id,
                'title' => $feedback->title,
                'status' => $feedback->status,
                'created_at' => $this->formatDate($feedback->created_at),
            ]),
            'moderation_flags' => $user->moderationFlags()->latest()->limit(5)->get()->map(fn (ModerationFlag $flag): array => [
                'id' => $flag->id,
                'reason' => $flag->reason,
                'status' => $flag->status,
                'created_at' => $this->formatDate($flag->created_at),
            ]),
        ];
    }

    /**
     * @param  iterable<Order>  $orders
     * @return array<int, array<string, mixed>>
     */
    private function ordersPayload(iterable $orders): array
    {
        return collect($orders)
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'title' => $order->title,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'price' => $order->price,
                'created_at' => $this->formatDate($order->created_at),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(UserAdminEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type,
            'type_label' => $this->eventLabels()[$event->type] ?? $event->type,
            'actor' => $event->actorUser?->name,
            'old_values' => $event->old_values,
            'new_values' => $event->new_values,
            'comment' => $event->comment,
            'created_at' => $this->formatDate($event->created_at),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function eventLabels(): array
    {
        return [
            UserAdminEvent::TYPE_ROLE_CHANGED => 'Роль изменена',
            UserAdminEvent::TYPE_USER_BLOCKED => 'Пользователь заблокирован',
            UserAdminEvent::TYPE_USER_UNBLOCKED => 'Пользователь разблокирован',
            UserAdminEvent::TYPE_ADMIN_NOTE_UPDATED => 'Админская заметка обновлена',
        ];
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

    private function ensureCanBlock(User $actor, User $target): void
    {
        if ($target->is($actor)) {
            throw ValidationException::withMessages([
                'reason' => 'Нельзя заблокировать самого себя.',
            ]);
        }

        if ($target->isAdmin() && $target->status === User::STATUS_ACTIVE && ! $this->anotherActiveAdminExists($target)) {
            throw ValidationException::withMessages([
                'reason' => 'Нельзя заблокировать последнего активного администратора.',
            ]);
        }
    }

    private function anotherActiveAdminExists(User $target): bool
    {
        return User::query()
            ->whereKeyNot($target->id)
            ->where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function statusValues(User $user): array
    {
        return [
            'status' => $user->status,
            'blocked_at' => $this->formatDate($user->blocked_at),
            'blocked_by' => $user->blocked_by,
            'block_reason' => $user->block_reason,
        ];
    }

    private function recordEvent(User $target, User $actor, string $type, ?array $oldValues = null, ?array $newValues = null, ?string $comment = null): void
    {
        UserAdminEvent::create([
            'target_user_id' => $target->id,
            'actor_user_id' => $actor->id,
            'type' => $type,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'comment' => $comment,
        ]);
    }

    private function maskIp(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);

            return "{$parts[0]}.{$parts[1]}.*.*";
        }

        $parts = array_values(array_filter(explode(':', $ip), fn (string $part): bool => $part !== ''));
        $prefix = array_slice($parts, 0, 2);

        return $prefix === [] ? '*:*' : implode(':', $prefix).':*:*';
    }

    private function formatDate(mixed $value): ?string
    {
        return $value?->format('d.m.Y H:i');
    }
}
