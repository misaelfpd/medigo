<?php

namespace App\View\Components\map;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MobileBottomSheet extends Component
{
    public $faskesList;

    /**
     * Create a new component instance.
     */
    public function __construct($faskesList = [])
    {
        $this->faskesList = $faskesList ?? [];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.map.mobile-bottom-sheet');
    }
}
