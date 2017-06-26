<?php

namespace AltiumParser;

use AltiumParser\Component\Component;
use AltiumParser\PropertyRecords\BaseRecord;

class SchLibParser extends LibraryParser
{
    const SCHLIB_HEADER = 'Protel for Windows - Schematic Library Editor Binary File Version 5.0';
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
     * @param $name
     * @return Component
     */
    public function getComponent($name)
    {
        if (!$this->fileParsed) {
            $this->parseFile();
        }

        $nameLower = strtolower($name);
        if (!isset($this->components[ $nameLower ])) {
            throw new \OutOfBoundsException("Component not found: {$name}");
        }

        return $this->components[ $nameLower ];
    }

    private function parseFile()
    {
        $this->fileParsed = true;

        $ole        = $this->getOleFile();
        $components = $ole->getRootDirectory()->listFiles();

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