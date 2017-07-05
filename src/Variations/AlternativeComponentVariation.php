<?php

namespace AltiumParser\Variations;

class AlternativeComponentVariation extends ComponentVariation
{
    /**
     * Parameter array for the alternative component
     *
     * @var array
     */
    private $parameters;

    public function __construct(array $variation, array $parameters)
    {
        parent::__construct($variation);
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}