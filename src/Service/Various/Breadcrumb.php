<?php

namespace App\Service\Various;

class Breadcrumb
{
    protected $items = array();

    public function add(string $text, ?string $url = null): self
    {
        array_push($this->items, array('text' => $text, 'url' => $url));
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
