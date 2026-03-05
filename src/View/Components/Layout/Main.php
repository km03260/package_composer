<?php

namespace DevOps213\SSOauthenticated\View\Components\Layout;

use Illuminate\View\Component;

class Main extends Component
{
    public $title;

    /**
     * Create a new component instance.
     *
     * @param string $title
     */
    public function __construct($title = 'User Module')
    {
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('ssoauth::layout.main');
    }
}
