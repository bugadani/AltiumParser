<?php

namespace AltiumParser\Variations;

use AltiumParser\ProjPcbParser;

class ProjectVariation
{
    private $variations = [];

    public function __construct(array $raw)
    {
        for ($i = 1; $i < $raw['VariationCount']; $i++) {
            if (!isset($raw["Variation{$i}"])) {
                throw new \UnexpectedValueException("Project file seems corrupted. No data for 'Variation{$i}'");
            }
            $variation = ProjPcbParser::parseRecord($raw["Variation{$i}"]);

            switch ($variation['Kind']) {
                case 1:
                    $this->variations[] = new NotFittedComponentVariation($variation);
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

                    $this->variations[] = new AlternativeComponentVariation($variation, $parameters);
                    break;

                default:
                    throw new \UnexpectedValueException("Unknown variation kind: '{$variation['Kind']}'");
                    break;
            }
        }
    }
}