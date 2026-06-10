<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PlatformNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $eventType,
        private readonly string $title,
        private readonly string $body,
        private readonly ?string $url = null,
        private readonly ?string $icon = null,
        private readonly string $severity = 'info',
        private readonly ?string $relatedType = null,
        private readonly ?int $relatedId = null,
        private readonly array $meta = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->eventType,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
            'severity' => $this->severity,
            'related_type' => $this->relatedType,
            'related_id' => $this->relatedId,
            'meta' => $this->meta,
        ];
    }
}
