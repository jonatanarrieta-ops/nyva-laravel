<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

abstract class InternalNotification extends Notification
{
    abstract protected function title(): string;

    abstract protected function message(): string;

    abstract protected function url(): ?string;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->url(),
        ];
    }
}
