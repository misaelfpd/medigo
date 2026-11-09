<?php

namespace App\View\Components\map;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FacilityCard extends Component
{
    public $faskes;
    public $isFirst;

    /**
     * Create a new component instance.
     */
    public function __construct($faskes = [], $isFirst = false)
    {
        $this->faskes = $faskes ?? [];
        $this->isFirst = $isFirst;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.map.facility-card');
    }
}
