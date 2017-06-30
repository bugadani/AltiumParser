<?php

namespace AltiumParser;

use AltiumParser\PropertyRecords\BaseRecord;
use OLEReader\OLEReader;

abstract class LibraryParser
{
    const HEADER_FILE_NAME = 'FileHeader';

    /**
     * @var OLEReader
     */
    private $ole;

    /**
     * @var bool
     */
    private $fileParsed = false;

    /**
     * @var string
     */
    private $expectedHeader;

    /**
     * @var RawRecord[]
     */
    private $headerRecords;

    protected function __construct($filename, $expectedHeader)
    {
        $this->ole            = new OLEReader($filename);
        $this->expectedHeader = $expectedHeader;
    }

    protected function getOleFile()
    {
        return $this->ole;
    }

    protected function ensureFileParsed()
    {
        if (!$this->fileParsed) {
            $this->fileParsed = true;
            $this->parseHeader();
            $this->parse();
        }
    }

    protected function getHeaderRecords()
    {
        $this->ensureFileParsed();

        return $this->headerRecords;
    }

    private function parseHeader()
    {
        $ole = $this->getOleFile();

        if (!$ole->hasFile(self::HEADER_FILE_NAME)) {
            throw new \UnexpectedValueException('File is not a valid schematic library');
        }

        $header              = $ole->getFile(self::HEADER_FILE_NAME)->getContents();
        $this->headerRecords = RawRecord::getRecords($header);

        if (empty($this->headerRecords)) {
            throw new \UnexpectedValueException('File is not a valid schematic library');
        }
        $headerRecord = BaseRecord::parseRecord($this->headerRecords[0]);
        if ($headerRecord->getProperty('HEADER') !== $this->expectedHeader) {
            throw new \UnexpectedValueException('File is not a valid schematic library');
        }
    }

    protected abstract function parse();
}