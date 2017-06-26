<?php

namespace AltiumParser;

use AltiumParser\Component\Component;
use AltiumParser\PropertyRecords\BaseRecord;

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
        $objects    = [];
        $components = [];
        for ($i = 1; $i < count($records); $i++) {
            $r     = BaseRecord::parseRecord($records[ $i ]);
            $index = $r->getInteger('INDEXINSHEET', -1);

            if (isset($objects[ $index ])) {
                $objects[ $index ][] = $r;
            } else if (isset($components[ $index ])) {
                $components[ $index ][] = $r;
            } else if ($r instanceof PropertyRecords\Component) {
                $components[ $index ] = [$r];
            } else {
                $objects[ $index ] = [$r];
            }
        }

        foreach ($components as $c) {
            $component          = Component::create($c);
            $this->components[] = $component;

            $libraryReference = $component->getLibraryReference();
            if (!isset($this->componentsGrouped[ $libraryReference ])) {
                $this->componentsGrouped[ $libraryReference ] = [$component];
            } else {
                $this->componentsGrouped[ $libraryReference ][] = $component;
            }
        }
    }
}
