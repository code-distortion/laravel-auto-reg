<?php

namespace CodeDistortion\LaravelAutoReg\Tests\Scenario1App\MyApp1\Resources\ViewComponents\SubDir1\SubDir2;

use Illuminate\View\Component;

/**
 * A view-component for testing purposes.
 */
class ViewComponent2 extends Component
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
        return 'VIEW COMPONENT 2';
    }
}
