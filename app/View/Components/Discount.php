<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Discount extends Component
{
    public $basket;
    public $price;

    public function __construct($basket, $price)
    {
        $this->basket = isset($basket->uniq_id) ? $basket : json_decode($basket);
        $this->price = $price;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.discount');
    }
}
