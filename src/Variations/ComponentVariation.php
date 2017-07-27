<?php

namespace AltiumParser\Variations;

abstract class ComponentVariation
{
    private $designator;
    private $uniqueId;
    protected $parameters;

    public function __construct(array $variation)
    {
        $this->designator = $variation['Designator'];
        $this->uniqueId   = $variation['UniqueId'];
        $this->parameters = $variation;
    }

    /**
     * @return mixed
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * @return mixed
     */
    public function getDesignator()
    {
        return $this->designator;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}