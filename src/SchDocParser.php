<?php

namespace AltiumParser;

use AltiumParser\Component\Component;
use AltiumParser\Component\Parameter;
use AltiumParser\Component\Pin;
use AltiumParser\PropertyRecords\ImplementationList;

class SchDocParser extends LibraryParser
{
    const SCHLIB_HEADER = 'Protel for Windows - Schematic Capture Binary File Version 5.0';

    /**
     * @var Component[]
     */
    private $components = [];

    /**
     * @var string[][]
     */
    private $componentsGrouped = [];

    /**
     * @var PropertyRecords\Sheet
     */
    private $sheetProperties;

    /**
     * @var PropertyRecords\SheetSymbol[]
     */
    private $sheetSymbols = [];

    public function __construct($filename)
    {
        parent::__construct($filename, self::SCHLIB_HEADER);
    }

    /**
     * @return string[][]
     */
    public function listComponents()
    {
        $this->ensureFileParsed();

        return $this->componentsGrouped;
    }

    /**
     * @return Component[]
     */
    public function getComponentInfoList()
    {
        $this->ensureFileParsed();

        return $this->components;
    }

    /**
     * @param $uniqueId
     * @return bool
     */
    public function hasComponent($uniqueId)
    {
        $this->ensureFileParsed();

        return isset($this->components[ $uniqueId ]);
    }

    /**
     * @param string $uniqueId
     * @return Component
     */
    public function getComponentInfo($uniqueId)
    {
        if ($this->hasComponent($uniqueId)) {
            return $this->components[ $uniqueId ];
        }

        throw new \InvalidArgumentException("{$uniqueId} not found");
    }

    protected function parse()
    {
        $records = $this->getHeaderRecords();

        // Create a tree from the records
        $objects      = [];
        $objectOwners = [];

        $getOwners = function ($ownerId) use (&$objectOwners) {
            $chain = [];

            $currentOwner = $ownerId;
            while ($currentOwner !== -1) {
                array_unshift($chain, $currentOwner);
                $currentOwner = $objectOwners[ $currentOwner ];
            }

            return $chain;
        };

        for ($i = 1; $i < count($records); $i++) {
            $r     = PropertyRecords\BaseRecord::parseRecord($records[ $i ]);
            $owner = $r->getInteger('OWNERINDEX', -1);

            $objectOwners[ $i - 1 ] = $owner;
            $ownerChain             = $getOwners($owner);

            $object =& $objects;
            foreach ($ownerChain as $id) {
                $object =& $object['children'][ $id ];
            }
            $object['children'][ $i - 1 ] = [
                'record'   => $r,
                'children' => []
            ];
        }

        // Parse the tree
        foreach ($objects['children'] as $node) {
            if ($node['record'] instanceof PropertyRecords\Sheet) {
                $this->sheetProperties = $node['record'];
            } else if ($node['record'] instanceof PropertyRecords\Component) {
                $component                                     = $this->parseComponent($node);
                $this->components[ $component->getUniqueId() ] = $component;

                $libraryReference = $component->getLibraryPath() . '|' . $component->getLibraryReference();
                if (!isset($this->componentsGrouped[ $libraryReference ])) {
                    $this->componentsGrouped[ $libraryReference ] = [];
                }
                $this->componentsGrouped[ $libraryReference ][] = $component->getUniqueId();
            } else if ($node['record'] instanceof PropertyRecords\Wire) {
            } else if ($node['record'] instanceof PropertyRecords\Junction) {
            } else if ($node['record'] instanceof PropertyRecords\NetLabel) {
            } else if ($node['record'] instanceof PropertyRecords\Port) {
            } else if ($node['record'] instanceof PropertyRecords\PowerPort) {
            } else if ($node['record'] instanceof PropertyRecords\Parameter) {
            } else if ($node['record'] instanceof PropertyRecords\NoERC) {
            } else if ($node['record'] instanceof PropertyRecords\Label) {
            } else if ($node['record'] instanceof PropertyRecords\SheetSymbol) {
                $this->sheetSymbols[] = $node['record'];
            } else if ($node['record'] instanceof PropertyRecords\SheetEntry) {
            } else if ($node['record'] instanceof PropertyRecords\Bus) {
            } else if ($node['record'] instanceof PropertyRecords\BusEntry) {
            } else if ($node['record'] instanceof PropertyRecords\Directive) {
            } else if ($node['record'] instanceof PropertyRecords\Ellipse) {
            } else if ($node['record'] instanceof PropertyRecords\Rectangle) {
            } else if ($node['record'] instanceof PropertyRecords\Polyline) {
            } else if ($node['record'] instanceof PropertyRecords\Polygon) {
            } else if ($node['record'] instanceof PropertyRecords\Line) {
            } else if ($node['record'] instanceof PropertyRecords\Arc) {
            } else if ($node['record'] instanceof PropertyRecords\EllipticalArc) {
            } else if ($node['record'] instanceof PropertyRecords\RoundRectangle) {
            } else if ($node['record'] instanceof PropertyRecords\Piechart) {
            } else if ($node['record'] instanceof PropertyRecords\Bezier) {
            } else {
                throw new \UnexpectedValueException("Unexpected record: {$node['record']->getProperty('RECORD')}");
            }
        }
    }

    public function getComponentsByLibRef($libRef)
    {
        $this->ensureFileParsed();

        $libraryReference = strtolower($libRef);

        if (!isset($this->componentsGrouped[ $libraryReference ])) {
            throw new \OutOfBoundsException("Component '{$libRef}' not found in sheet");
        } else {
            return $this->componentsGrouped[ $libraryReference ];
        }
    }

    private function parseComponent($node)
    {
        $component       = new Component($node['record']);
        $subpartsDefined = [];
        foreach ($node['children'] as $child) {
            if ($child['record'] instanceof PropertyRecords\Pin) {
                $pin = $this->parsePin($child);

                $id = $pin->getSubpartId();
                if (!in_array($id, $subpartsDefined)) {
                    $component->createSubpart($id);
                    $subpartsDefined[] = $id;
                }

                $component->getSubPart($id)->addPin($pin);

            } else if ($child['record'] instanceof PropertyRecords\Parameter) {
                $param = $this->parseComponentParameter($child);
                $component->addParameter($param);
            } else if ($child['record'] instanceof ImplementationList) {
                foreach ($child['children'] as $implementation) {
                    switch ($implementation['record']->getProperty('MODELTYPE')) {
                        case 'PCBLIB':
                            $component->addFootprint($implementation['record']);
                            break;
                    }
                }
            }
        }

        return $component;
    }

    private function parsePin($node)
    {
        $pin = new Pin($node['record']);

        /*foreach($node['children'] as $child) {
            if($child['record']->getProperty('NAME') !== 'PinUniqueId') {

            }
        }*/

        return $pin;
    }

    private function parseComponentParameter($node)
    {
        return new Parameter($node['record']);
    }

    /**
     * @return PropertyRecords\Sheet
     */
    public function getSheetProperties()
    {
        $this->ensureFileParsed();

        return $this->sheetProperties;
    }

    /**
     * @return PropertyRecords\SheetSymbol[]
     */
    public function getSheetSymbols()
    {
        $this->ensureFileParsed();

        return $this->sheetSymbols;
    }
}
