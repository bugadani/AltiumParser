<?php

namespace AltiumParser\Component;

use AltiumParser\PropertyRecords\BaseRecord;
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
        $component = new Component(null);

        $getSubpart = function ($partId) use ($component) {
            if (!isset($component->subparts[ $partId ])) {
                $component->subparts[ $partId ] = new Subpart();
            }

            return $component->subparts[ $partId ];
        };

        /** @var RawRecord $record */
        foreach ($records as $i => $record) {

            switch ($record->getProperty('RECORD')) {
                case BaseRecord::RECORD_COMPONENT:
                    $component->componentProperties = $record;
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
            }
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

    public function __construct(\AltiumParser\PropertyRecords\Component $properties)
    {
        $this->componentProperties = $properties;
    }

    public function createSubpart($id)
    {
        $this->subparts[$id] = new Subpart();
    }

    public function addParameter(Parameter $param)
    {
        $this->parameters[ $param->getName() ] = $param;
    }

    public function getParameter($parameterName)
    {
        if (!isset($this->parameters[ $parameterName ])) {
            throw new \InvalidArgumentException("Unknown parameter: {$parameterName}");
        }

        return $this->parameters[ $parameterName ];
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

    public function getLibraryReference()
    {
        return $this->componentProperties->getProperty('LIBREFERENCE');
    }

    public function getIndexInSheet()
    {
        return $this->componentProperties->getProperty('INDEXINSHEET', -1);
    }
}