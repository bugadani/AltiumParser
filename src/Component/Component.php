<?php

namespace AltiumParser\Component;

use AltiumParser\PropertyRecords\BaseRecord;
use AltiumParser\PropertyRecords\Implementation;
use AltiumParser\RawRecord;

/**
 * Higher level Component class
 *
 * @package AltiumParser\Component
 */
class Component
{

    public static function create(array $records)
    {
        /** @var Component $component */
        $component = null;

        $getSubpart = function ($partId) use (&$component) {
            if (!isset($component->subparts[ $partId ])) {
                $component->subparts[ $partId ] = new Subpart();
            }

            return $component->subparts[ $partId ];
        };

        /** @var RawRecord $record */
        foreach ($records as $i => $record) {

            switch ($record->getProperty('RECORD')) {
                case BaseRecord::RECORD_COMPONENT:
                    $component = new Component($record);
                    break;

                case BaseRecord::RECORD_PARAMETER:
                    $component->addParameter(new Parameter($record));
                    break;

                case BaseRecord::RECORD_PIN:
                    $partId = $record->getInteger('OWNERPARTID');

                    $getSubpart($partId)->addPin(new Pin($record));
                    break;

                case BaseRecord::RECORD_ARC:
                case BaseRecord::RECORD_ELLIPTICAL_ARC:
                case BaseRecord::RECORD_ELLIPSE:
                case BaseRecord::RECORD_RECTANGLE:
                case BaseRecord::RECORD_ROUND_RECTANGLE:
                case BaseRecord::RECORD_LINE:
                case BaseRecord::RECORD_PIECHART:
                case BaseRecord::RECORD_POLYGON:
                case BaseRecord::RECORD_POLYLINE:
                case BaseRecord::RECORD_HYPERLINK:
                case BaseRecord::RECORD_WARNING_SIGN:
                case BaseRecord::RECORD_TEXT_FRAME:
                case BaseRecord::RECORD_IEEE_SYMBOL:
                case BaseRecord::RECORD_BEZIER:
                    $partId = $record->getInteger('OWNERPARTID');

                    $getSubpart($partId)->addDrawingPrimitive(new DrawingPrimitive($record));
                    break;

                case BaseRecord::RECORD_IMPLEMENTATION:
                    /** @var Implementation $record */
                    if ($record->getProperty('MODELTYPE') === 'PCBLIB') {
                        $component->addFootprint($record);
                    }
                    break;
            }
        }

        return $component;
    }

    public static function createFromArray(array $data)
    {
        if (!isset($data['component'])) {
            throw new \InvalidArgumentException('Component data is not set');
        }
        if (!isset($data['parameters'])) {
            $data['parameters'] = [];
        }
        if (!isset($data['drawing'])) {
            $data['drawing'] = [];
        }

        $component = new Component(new \AltiumParser\PropertyRecords\Component($data['component']));
        foreach ($data['parameters'] as $parameter) {
            $component->addParameter(new Parameter(new \AltiumParser\PropertyRecords\Parameter($parameter)));
        }

        return $component;
    }

    /**
     * @var \AltiumParser\PropertyRecords\Component
     */
    private $componentProperties;

    /**
     * @var Parameter[]
     */
    private $parameters = [];

    /**
     * @var Subpart[]
     */
    private $subparts = [];

    /**
     * @var Implementation[]
     */
    private $footprints = [];

    /**
     * @var Implementation
     */
    private $currentFootprint;

    public function __construct(\AltiumParser\PropertyRecords\Component $properties)
    {
        $this->componentProperties = $properties;
    }

    public function getUniqueId()
    {
        return $this->componentProperties->getProperty('UNIQUEID');
    }

    public function createSubpart($id)
    {
        $this->subparts[ $id ] = new Subpart();
    }

    public function addParameter(Parameter $param)
    {
        $this->parameters[ $param->getName() ] = $param;
    }

    /**
     * @param $parameterName
     * @return bool
     */
    public function hasParameter($parameterName)
    {
        return isset($this->parameters[ $parameterName ]);
    }

    public function getParameter($parameterName)
    {
        if (!$this->hasParameter($parameterName)) {
            throw new \InvalidArgumentException("Unknown parameter: {$parameterName}");
        }

        return $this->parameters[ $parameterName ];
    }

    public function getParameterValue($parameterName, $default = null)
    {
        if (!$this->hasParameter($parameterName)) {
            if ($default === null) {
                throw new \InvalidArgumentException("Unknown parameter: {$parameterName}");
            }

            return $default;
        }

        return $this->parameters[ $parameterName ]->getText();
    }

    public function listParameters()
    {
        foreach ($this->parameters as $parameter) {
            yield $parameter->getName() => $parameter;
        }
    }

    public function getPartCount()
    {
        return count($this->subparts);
    }

    public function getSubPart($id)
    {
        if (isset($this->subparts[ $id ])) {
            return $this->subparts[ $id ];
        }

        throw new \InvalidArgumentException("Part '{$id}' not found");
    }

    public function getLibraryPath()
    {
        return $this->componentProperties->getProperty('SOURCELIBRARYNAME');
    }

    public function getLibraryReference()
    {
        return $this->componentProperties->getProperty('LIBREFERENCE');
    }

    public function getIndexInSheet()
    {
        return $this->componentProperties->getProperty('INDEXINSHEET', -1);
    }

    public function addFootprint(Implementation $footprint)
    {
        $this->footprints[] = $footprint;
        if (count($this->footprints) === 1 || $footprint->getBoolean('ISCURRENT')) {
            $this->currentFootprint = $footprint;
        }
    }

    public function getCurrentFootprint()
    {
        return $this->currentFootprint;
    }
}