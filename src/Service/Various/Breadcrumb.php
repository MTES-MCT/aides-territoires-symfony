<?php

namespace App\Service\Various;

class Breadcrumb
{
    protected $items = [];

    public function add(string $text, ?string $url = null): self
    {
        array_push($this->items, ['text' => $text, 'url' => $url]);
        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param array $items
     */
    public function setItems($items)
    {
        $this->items = $items;
    }
}
