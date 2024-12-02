<?php

namespace App\Message;

class SendNotification
{
    private string $title;
    private string $message;

    public function __construct(string $title, string $message)
    {
        $this->title = $title;
        $this->message = $message;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
