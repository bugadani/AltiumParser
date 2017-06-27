<?php

namespace AltiumParser;

use AltiumParser\Component\Component;
use AltiumParser\Component\Pin;
use AltiumParser\PropertyRecords\BaseRecord;
use AltiumParser\PropertyRecords\Parameter;

class SchDocParser extends LibraryParser
{
    const SCHLIB_HEADER = 'Protel for Windows - Schematic Capture Binary File Version 5.0';
    const HEADER_FILE_NAME = 'FileHeader';

    /**
     * @var bool
     */
    private $fileParsed = false;

    /**
     * @var Component[]
     */
    private $components = [];

    /**
     * @var Component[][]
     */
    private $componentsGrouped = [];

    /**
     * @return Component[][]
     */
    public function listComponents()
    {
        if (!$this->fileParsed) {
            $this->parseFile();
        }

        return $this->componentsGrouped;
    }

    /**
     * @return Component[]
     */
    public function listAllComponents()
    {
        if (!$this->fileParsed) {
            $this->parseFile();
        }

        return $this->components;
    }

    private function parseFile()
    {
        $this->fileParsed = true;

        $ole = $this->getOleFile();

        if (!$ole->hasFile('' . self::HEADER_FILE_NAME . '')) {
            throw new \UnexpectedValueException('File is not a valid schematic library');
        }

        $header  = $ole->getFile(self::HEADER_FILE_NAME)->getContents();
        $records = RawRecord::getRecords($header);

        if (empty($records)) {
            throw new \UnexpectedValueException('File is not a valid schematic library');
        }

        $headerRecord = BaseRecord::parseRecord($records[0]);
        if ($headerRecord->getProperty('HEADER') != self::SCHLIB_HEADER) {
            throw new \UnexpectedValueException('File is not a valid schematic library');
        }

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
            $r     = BaseRecord::parseRecord($records[ $i ]);
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
            if ($node['record'] instanceof \AltiumParser\PropertyRecords\Component) {
                $component          = $this->parseComponent($node);
                $this->components[] = $component;

                $libraryReference = strtolower($component->getLibraryReference());
                if (!isset($this->componentsGrouped[ $libraryReference ])) {
                    $this->componentsGrouped[ $libraryReference ] = [$component];
                } else {
                    $this->componentsGrouped[ $libraryReference ][] = $component;
                }
            }
        }
    }

    public function getComponentsByLibRef($libRef)
    {
        if (!$this->fileParsed) {
            $this->parseFile();
        }

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

                if (!in_array($pin->getSubpartId(), $subpartsDefined)) {
                    $component->createSubpart($pin->getSubpartId());
                    $subpartsDefined[] = $pin->getSubpartId();
                }

                $component->getSubPart($pin->getSubpartId())->addPin($pin);

            } else if ($child['record'] instanceof Parameter) {
                $param = $this->parseComponentParameter($child);
                $component->addParameter($param);
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
        return new \AltiumParser\Component\Parameter($node['record']);
    }
}
