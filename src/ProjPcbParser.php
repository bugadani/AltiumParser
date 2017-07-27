<?php

namespace AltiumParser;

use AltiumParser\Variations\AlternativeComponentVariation;
use AltiumParser\Variations\NotFittedComponentVariation;
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
    private $path;
    private $filename;

    private $design = [];

    /**
     * @var SchDocParser[]
     */
    private $schematicDocuments = [];
    private $pcbDocuments = [];
    private $otherDocuments = [];
    private $generatedDocuments = [];
    private $configurations = [];

    /**
     * @var ProjectVariation[]
     */
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
        if (substr($filename, 0, 6) === 'zip://') {
            if (strpos($filename, '#') === false) {
                throw new \InvalidArgumentException('Discovering project files is not supported. Please provide a path to the project file.');
            }
            list($archive, $projectFile) = explode('#', $filename, 2);
            $path = pathinfo($projectFile, PATHINFO_DIRNAME);
            if ($path === '.') {
                $path = '';
            } else {
                $path .= '/';
            }
            $this->path = $archive . '#' . $path;
        } else {
            $this->path = pathinfo($filename, PATHINFO_DIRNAME);
        }
    }

    private function ensureFileParsed()
    {
        if (!$this->fileParsed) {
            $this->fileParsed = true;
            $this->parse();
        }
    }

    public function parse()
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
            $document = $ini["Document{$i}"];
            $ext      = pathinfo($document['DocumentPath'], PATHINFO_EXTENSION);

            switch ($ext) {
                case 'SchDoc':
                    $this->schematicDocuments[] = new SchDocParser($this->path . $document['DocumentPath']);
                    break;

                case 'PcbDoc':
                    $this->pcbDocuments[] = $document;
                    break;

                default:
                    $this->otherDocuments[] = $document;
                    break;
            }
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

        $this->projectVariants['[No Variations]'] = new ProjectVariation(['VariationCount' => 0, 'Description' => '[No Variations]']);
        for ($i = 1; isset($ini["ProjectVariant{$i}"]); $i++) {
            $projectVariation                                             = new ProjectVariation($ini["ProjectVariant{$i}"]);
            $this->projectVariants[ $projectVariation->getDescription() ] = $projectVariation;
            unset($ini["ProjectVariant{$i}"]);
        }

        if (!empty($ini)) {
            throw new \Exception('Debug: missed ini keys: ' . implode(', ', array_keys($ini)));
        }
    }

    /**
     * @param string $variant
     *
     * @return array Libref -> [unique id] array
     */
    public function getProjectBOM($variant = '[No Variations]')
    {
        $this->ensureFileParsed();

        if (!isset($this->projectVariants[ $variant ])) {
            throw new \InvalidArgumentException("Variation {$variant} does not exist");
        }

        $components = [];
        foreach ($this->schematicDocuments as $schDoc) {
            $c = $schDoc->listComponents();

            $components = array_merge_recursive($components, $c);
        }

        $idsToRemove = [];
        $idsToAdd    = [];

        foreach ($this->projectVariants[ $variant ]->getVariations() as $variation) {
            if ($variation instanceof NotFittedComponentVariation) {
                $idsToRemove[] = $variation->getUniqueId();
            } else if ($variation instanceof AlternativeComponentVariation) {
                $libraryReference = $variation->getParameters()['AltLibLink_LibraryIdentifier'] . '|' . $variation->getParameters()['Library Reference'];
                if (!isset($idsToAdd[ $libraryReference ])) {
                    $idsToAdd[ $libraryReference ] = [];
                }
                $uniqueId = $variation->getUniqueId();

                $pos = strrpos($uniqueId, '\\');
                if ($pos !== false) {
                    $uniqueId = substr($uniqueId, $pos + 1);
                }

                $idsToRemove[]                   = $uniqueId;
                $idsToAdd[ $libraryReference ][] = $uniqueId;
            }
        }

        foreach ($components as $libref => $ids) {
            $components[ $libref ] = array_diff($ids, $idsToRemove);
            if (empty($components[ $libref ])) {
                unset($components[ $libref ]);
            }
        }

        $components = array_merge_recursive($components, $idsToAdd);

        return $components;
    }

    public function listVariations()
    {
        $this->ensureFileParsed();

        return array_keys($this->projectVariants);
    }
}