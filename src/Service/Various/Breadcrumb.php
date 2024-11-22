<?php

namespace App\Service\Various;

class Breadcrumb
{
    /**
     * @var array<array{text: string, url: ?string}>
     */
    protected $items = [];

    public function add(string $text, ?string $url = null): self
    {
        array_push($this->items, ['text' => $text, 'url' => $url]);
        return $this;
    }

    /**
     * @return array<array{text: string, url: ?string}>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array<array{text: string, url: ?string}> $items
     */
    public function setItems($items): void
    {
        $this->items = $items;
    }
}
