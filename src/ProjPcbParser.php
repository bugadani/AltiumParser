<?php

namespace AltiumParser;

use AltiumParser\Variations\ProjectVariation;

class ProjPcbParser
{
    public static function parseRecord($record)
    {
        $parts      = explode('|', $record);
        $parameters = [];
        foreach ($parts as $part) {
            list($key, $value) = explode('=', $part, 2);
            $parameters[ $key ] = $value;
        }

        return $parameters;
    }

    /**
     * @var bool
     */
    private $fileParsed = false;

    private $design = [];
    private $documents = [];
    private $generatedDocuments = [];
    private $configurations = [];
    private $projectVariants = [];
    private $outputGroups = [];
    private $erc = [];
    private $ercMatrix = [];
    private $annotate = [];
    private $smartPDF = [];
    private $preferences = [];

    // Unknown groups
    private $modificationLevels = [];
    private $differenceLevels = [];
    private $prjClassGen = [];
    private $libraryUpdateOptions = [];
    private $databaseUpdateOptions = [];
    private $comparisonOptions = [];

    public function __construct($filename)
    {
        $this->filename = $filename;
    }

    private function ensureFileParsed()
    {
        if (!$this->fileParsed) {
            $this->fileParsed = true;
            $this->parse();
        }
    }

    private function parse()
    {
        $ini                         = parse_ini_file($this->filename, true, INI_SCANNER_RAW);
        $this->design                = $ini['Design'];
        $this->erc                   = $ini['Electrical Rules Check'];
        $this->ercMatrix             = $ini['ERC Connection Matrix'];
        $this->annotate              = $ini['Annotate'];
        $this->prjClassGen           = $ini['PrjClassGen'];
        $this->libraryUpdateOptions  = $ini['LibraryUpdateOptions'];
        $this->databaseUpdateOptions = $ini['DatabaseUpdateOptions'];
        $this->comparisonOptions     = $ini['Comparison Options'];
        $this->smartPDF              = $ini['SmartPDF'];
        $this->differenceLevels      = $ini['Difference Levels'];
        $this->modificationLevels    = $ini['Modification Levels'];
        $this->preferences           = $ini['Preferences'];
        unset($ini['Design']);
        unset($ini['Electrical Rules Check']);
        unset($ini['ERC Connection Matrix']);
        unset($ini['Annotate']);
        unset($ini['PrjClassGen']);
        unset($ini['LibraryUpdateOptions']);
        unset($ini['DatabaseUpdateOptions']);
        unset($ini['Comparison Options']);
        unset($ini['SmartPDF']);
        unset($ini['Difference Levels']);
        unset($ini['Modification Levels']);
        unset($ini['Preferences']);

        for ($i = 1; isset($ini["Document{$i}"]); $i++) {
            $this->documents[] = $ini["Document{$i}"];
            unset($ini["Document{$i}"]);
        }

        for ($i = 1; isset($ini["GeneratedDocument{$i}"]); $i++) {
            $this->generatedDocuments[] = $ini["GeneratedDocument{$i}"];
            unset($ini["GeneratedDocument{$i}"]);
        }

        for ($i = 1; isset($ini["Configuration{$i}"]); $i++) {
            $this->configurations[] = $ini["Configuration{$i}"];
            unset($ini["Configuration{$i}"]);
        }

        for ($i = 1; isset($ini["OutputGroup{$i}"]); $i++) {
            $this->outputGroups[] = $ini["OutputGroup{$i}"];
            unset($ini["OutputGroup{$i}"]);
        }

        for ($i = 1; isset($ini["ProjectVariant{$i}"]); $i++) {
            $this->projectVariants[] = new ProjectVariation($ini["ProjectVariant{$i}"]);
            unset($ini["ProjectVariant{$i}"]);
        }

        if (!empty($ini)) {
            throw new \Exception('Debug: missed ini keys: ' . implode(', ', array_keys($ini)));
        }
    }
}