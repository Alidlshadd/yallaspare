<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AuthSplitLayout extends Component
{
    public string $panelTitle;
    public string $panelSubtitle;
    public ?string $panelTag;
    public ?string $panelButtonText;
    public ?string $panelButtonHref;
    public string $panelButtonAction;
    public ?string $panelExitDirection;
    public string $panelTheme;
    public string $heading;
    public string $formPosition;
    public string $enterDirection;

    public function __construct(
        string $panelTitle = 'Welcome Back',
        string $panelSubtitle = 'Access the YallaSpare Management System using your authorized credentials.',
        ?string $panelTag = null,
        ?string $panelButtonText = null,
        ?string $panelButtonHref = null,
        string $panelButtonAction = 'none',
        ?string $panelExitDirection = null,
        string $panelTheme = 'login',
        string $heading = '',
        string $formPosition = 'right',
        string $enterDirection = 'right'
    ) {
        $this->panelTitle = $panelTitle;
        $this->panelSubtitle = $panelSubtitle;
        $this->panelTag = $panelTag;
        $this->panelButtonText = $panelButtonText;
        $this->panelButtonHref = $panelButtonHref;
        $this->panelButtonAction = $panelButtonAction;
        $this->panelExitDirection = $panelExitDirection;
        $this->panelTheme = $panelTheme;
        $this->heading = $heading;
        $this->formPosition = $formPosition;
        $this->enterDirection = $enterDirection;
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.auth-split');
    }
}
