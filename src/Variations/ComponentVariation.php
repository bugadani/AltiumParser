<?php

namespace AltiumParser\Variations;

use AltiumParser\Component\Component;

abstract class ComponentVariation
{
    private $designator;
    private $uniqueId;
    private $variationInfo;

    public function __construct(array $variation)
    {
        $this->designator = $variation['Designator'];
        $uniqueId         = $variation['UniqueId'];
        $pos = strrpos($uniqueId, '\\');
        if ($pos !== false) {
            $uniqueId = substr($uniqueId, $pos + 1);
        }
        $this->uniqueId   = $uniqueId;
        $this->variationInfo = $variation;
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
    public function getInfo()
    {
        return $this->variationInfo;
    }

    public abstract function apply(Component $component);
}