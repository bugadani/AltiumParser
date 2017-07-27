<?php

namespace AltiumParser\Variations;

use AltiumParser\Component\Component;
use AltiumParser\ProjPcbParser;

class ProjectVariation
{
    /**
     * @var ComponentVariation[]
     */
    private $variations = [];
    private $description;

    public function __construct(array $raw)
    {
        $this->description = $raw['Description'];
        for ($i = 1; $i <= $raw['VariationCount']; $i++) {
            if (!isset($raw["Variation{$i}"])) {
                throw new \UnexpectedValueException("Project file seems corrupted. No data for 'Variation{$i}'");
            }
            $variation = ProjPcbParser::parseRecord($raw["Variation{$i}"]);

            switch ($variation['Kind']) {
                case 1:
                    $variationObject                                     = new NotFittedComponentVariation($variation);
                    $this->variations[ $variationObject->getUniqueId() ] = $variationObject;
                    break;

                case 2:
                    $parameters = [];
                    for ($j = 1; $j < $raw['ParamVariationCount']; $j++) {
                        if (!isset($raw["ParamVariation{$j}"])) {
                            throw new \UnexpectedValueException("Project file seems corrupted. No data for 'ParamVariation{$j}'");
                        }
                        if ($raw["ParamDesignator{$j}"] === $variation['Designator']) {
                            $parameter                                 = ProjPcbParser::parseRecord($raw["ParamVariation{$j}"]);
                            $parameters[ $parameter['ParameterName'] ] = $parameter['VariantValue'];
                        }
                    }

                    $variationObject                                     = new AlternativeComponentVariation($variation, $parameters);
                    $this->variations[ $variationObject->getUniqueId() ] = $variationObject;
                    break;

                default:
                    throw new \UnexpectedValueException("Unknown variation kind: '{$variation['Kind']}'");
                    break;
            }
        }
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return ComponentVariation[]
     */
    public function getVariations()
    {
        return $this->variations;
    }
}