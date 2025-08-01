<?php

namespace App\View\Components;

use Illuminate\View\Component;

class StickyTable extends Component
{
    public $items;
    public $leftSticky;
    public $rightSticky;
    public $emptyMessage;
    public $pagination;

    public function __construct(
        $items = [],
        $leftSticky = 1,
        $rightSticky = 1,
        $emptyMessage = 'No data found',
        $pagination = null
    ) {
        $this->items = $items;
        $this->leftSticky = $leftSticky;
        $this->rightSticky = $rightSticky;
        $this->emptyMessage = $emptyMessage;
        $this->pagination = $pagination;
    }

    public function render()
    {
        return view('components.sticky-table');
    }
}
