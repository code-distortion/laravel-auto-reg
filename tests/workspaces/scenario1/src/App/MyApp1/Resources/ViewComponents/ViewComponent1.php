<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Scenario1App\MyApp1\Resources\ViewComponents;

use Illuminate\View\Component;

/**
 * A view-component for testing purposes.
 */
class ViewComponent1 extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return 'VIEW COMPONENT 1';
    }
}
