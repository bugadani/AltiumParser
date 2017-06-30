<?php

namespace AltiumParser;

use AltiumParser\Component\Component;
use AltiumParser\PropertyRecords\BaseRecord;

class SchLibParser extends LibraryParser
{
    const SCHLIB_HEADER = 'Protel for Windows - Schematic Library Editor Binary File Version 5.0';

    /**
     * @var Component[]
     */
    private $components = [];

    public function __construct($filename)
    {
        parent::__construct($filename, self::SCHLIB_HEADER);
    }

    /**
     * @param $name
     * @return Component
     */
    public function getComponent($name)
    {
        $this->ensureFileParsed();

        $nameLower = strtolower($name);
        if (!isset($this->components[ $nameLower ])) {
            throw new \OutOfBoundsException("Component not found: {$name}");
        }

        return $this->components[ $nameLower ];
    }

    public function listComponentNames()
    {
        $this->ensureFileParsed();

        return array_map(function (Component $component) {
            return $component->getLibraryReference();
        }, $this->components);
    }

    public function listComponents()
    {
        $this->ensureFileParsed();

        foreach ($this->components as $component) {
            yield $component;
        }
    }

    protected function parse()
    {
        $ole = $this->getOleFile();

        $components = $ole->getRootDirectory()->listFiles();
        foreach ($components as $component) {
            if ($component == 'Storage' || $component == self::HEADER_FILE_NAME) {
                continue;
            }

            $records = RawRecord::getRecords($ole->getFile("{$component}/Data")->getContents());

            $parsedRecords = [];
            foreach ($records as $record) {
                /** @var BaseRecord $r */
                $parsedRecords[] = BaseRecord::parseRecord($record);
            }

            $componentObject = Component::create($parsedRecords);

            $this->components[ strtolower($componentObject->getLibraryReference()) ] = $componentObject;
        }
    }
}
