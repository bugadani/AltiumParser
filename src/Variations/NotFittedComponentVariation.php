<?php

namespace AltiumParser\Variations;

use AltiumParser\Component\Component;

class NotFittedComponentVariation extends ComponentVariation
{
    public function apply(Component $component)
    {
        return null;
    }
}