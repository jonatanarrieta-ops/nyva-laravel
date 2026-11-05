<?php

namespace App\Notifications;

class AdminAlert extends InternalNotification
{
    public function __construct(
        private string $alertTitle,
        private string $alertMessage,
        private ?string $alertUrl = null,
    ) {}

    protected function title(): string
    {
        return $this->alertTitle;
    }

    protected function message(): string
    {
        return $this->alertMessage;
    }

    protected function url(): ?string
    {
        return $this->alertUrl;
    }
}
