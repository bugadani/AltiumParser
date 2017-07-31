<?php

namespace AltiumParser\Variations;

use AltiumParser\Component\Component;
use AltiumParser\Component\Parameter;

class AlternativeComponentVariation extends ComponentVariation
{
    private $parameters = [];
    private $parameterMap = [];

    public function __construct(array $variation, array $parameters)
    {
        parent::__construct($variation);
        foreach ($parameters as $parameter => $value) {
            $this->parameterMap[$parameter] = $value;
            $this->parameters[] = [
                'RECORD'      => 41,
                'NAME'        => $parameter,
                'TEXT'        => $value,
                'DESCRIPTION' => $value
            ];
        }
    }

    public function apply(Component $component)
    {
        return Component::createFromArray([
            'component'  => [
                'RECORD'            => 1,
                'UNIQUEID'          => $component->getUniqueId(),
                'SOURCELIBRARYNAME' => $this->getInfo()['AltLibLink_LibraryIdentifier'],
                'LIBREFERENCE'      => $this->getInfo()['AlternatePart'],
                'DESIGNATOR'        => $this->getInfo()['Designator']
            ],
            'parameters' => $this->parameters
        ]);
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameterMap;
    }
}