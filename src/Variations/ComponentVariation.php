<?php

namespace AltiumParser\Variations;

abstract class ComponentVariation
{
    private $designator;
    private $uniqueId;

    public function __construct(array $variation)
    {
        $this->designator = $variation['Designator'];
        $this->uniqueId   = $variation['UniqueId'];
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
}