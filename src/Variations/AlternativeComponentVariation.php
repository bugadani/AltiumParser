<?php

namespace AltiumParser\Variations;

class AlternativeComponentVariation extends ComponentVariation
{
    public function __construct(array $variation, array $parameters)
    {
        parent::__construct($variation);
        $this->parameters = array_merge($this->parameters, $parameters);
    }
}