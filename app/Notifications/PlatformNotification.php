<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class PlatformNotification extends Notification
{
    use Queueable;

    /**
     * Ключевые события, по которым дополнительно отправляется email.
     *
     * @var array<int, string>
     */
    public const EMAIL_EVENT_TYPES = [
        'task_offer.created',
        'order.payment_held',
        'order.work_submitted',
        'dispute.opened',
        'dispute.resolved',
    ];

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
        $channels = ['database'];

        if ($this->shouldSendEmail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = trim((string) ($notifiable->name ?? ''));

        $message = (new MailMessage)
            ->subject('Таскора: '.$this->title)
            ->greeting($name !== '' ? "Здравствуйте, {$name}!" : 'Здравствуйте!')
            ->line($this->body)
            ->salutation('Команда Таскоры');

        if ($this->url !== null && $this->url !== '') {
            $message->action('Открыть на Таскоре', $this->absoluteUrl($this->url));
        }

        $message->line('Все действия по заказу выполняйте внутри платформы: так сделка остается под защитой Таскоры.');

        return $message;
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

    private function shouldSendEmail(object $notifiable): bool
    {
        return (bool) config('notifications.email_enabled', false)
            && in_array($this->eventType, self::EMAIL_EVENT_TYPES, true)
            && is_string($notifiable->email ?? null)
            && trim($notifiable->email) !== '';
    }

    private function absoluteUrl(string $url): string
    {
        return Str::startsWith($url, ['http://', 'https://']) ? $url : url($url);
    }
}
