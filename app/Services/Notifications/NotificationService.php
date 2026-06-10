<?php

namespace App\Services\Notifications;

use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;

class NotificationService
{
    private const SEVERITIES = ['info', 'success', 'warning', 'danger'];

    /**
     * @param  array<string, mixed>  $meta
     */
    public function notifyUser(
        User $user,
        string $type,
        string $title,
        string $body,
        ?string $url = null,
        array $meta = [],
    ): void {
        $actorId = $this->actorId($meta);

        if ($actorId !== null && $actorId === $user->id) {
            return;
        }

        $severity = $meta['severity'] ?? 'info';
        $severity = in_array($severity, self::SEVERITIES, true) ? $severity : 'info';

        $user->notify(new PlatformNotification(
            eventType: $type,
            title: $title,
            body: $body,
            url: $url,
            icon: $meta['icon'] ?? null,
            severity: $severity,
            relatedType: $meta['related_type'] ?? null,
            relatedId: isset($meta['related_id']) ? (int) $meta['related_id'] : null,
            meta: Arr::except($meta, ['severity', 'icon', 'related_type', 'related_id']),
        ));
    }

    /**
     * @param  iterable<int, User|null>  $users
     * @param  array<string, mixed>  $meta
     */
    public function notifyUsers(
        iterable $users,
        string $type,
        string $title,
        string $body,
        ?string $url = null,
        array $meta = [],
    ): void {
        $sent = [];

        foreach ($users as $user) {
            if (! $user instanceof User || isset($sent[$user->id])) {
                continue;
            }

            $this->notifyUser($user, $type, $title, $body, $url, $meta);
            $sent[$user->id] = true;
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function notifyModeratorsAndAdmins(
        string $type,
        string $title,
        string $body,
        ?string $url = null,
        array $meta = [],
    ): void {
        $this->notifyUsers(
            User::query()
                ->whereIn('role', [User::ROLE_MODERATOR, User::ROLE_ADMIN])
                ->get(),
            $type,
            $title,
            $body,
            $url,
            $meta,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestPayload(User $user, int $limit = 5): array
    {
        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->payload($notification))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allPayload(User $user, int $limit = 100): array
    {
        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->payload($notification))
            ->all();
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? $notification->type,
            'title' => $data['title'] ?? 'Уведомление',
            'body' => $data['body'] ?? '',
            'url' => $data['url'] ?? null,
            'icon' => $data['icon'] ?? null,
            'severity' => $data['severity'] ?? 'info',
            'related_type' => $data['related_type'] ?? null,
            'related_id' => $data['related_id'] ?? null,
            'is_read' => $notification->read_at !== null,
            'read_at' => $notification->read_at?->format('d.m.Y H:i'),
            'created_at' => $notification->created_at?->format('d.m.Y H:i'),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function actorId(array $meta): ?int
    {
        return isset($meta['actor_id']) ? (int) $meta['actor_id'] : null;
    }
}
