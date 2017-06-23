<?php

namespace AltiumParser\PropertyRecords;

use AltiumParser\RawRecord;

class BaseRecord
{
    const RECORD_HEADER = 0;
    const RECORD_COMPONENT = 1;
    const RECORD_PIN = 2;
    const RECORD_IEEE_SYMBOL = 3;
    const RECORD_LABEL = 4;
    const RECORD_BEZIER = 5;
    const RECORD_POLYLINE = 6;
    const RECORD_POLYGON = 7;
    const RECORD_ELLIPSE = 8;
    const RECORD_PIECHART = 9;
    const RECORD_ROUND_RECTANGLE = 10;
    const RECORD_ELLIPTICAL_ARC = 11;
    const RECORD_ARC = 12;
    const RECORD_LINE = 13;
    const RECORD_RECTANGLE = 14;
    const RECORD_SHEET_SYMBOL = 15;
    const RECORD_SHEET_ENTRY = 16;
    const RECORD_POWER_PORT = 17;
    const RECORD_PORT = 18;
    const RECORD_NO_ERC = 22;
    const RECORD_NETLABEL = 25;
    const RECORD_BUS = 26;
    const RECORD_WIRE = 27;
    const RECORD_TEXT_FRAME = 28;
    const RECORD_JUNCTION = 29;
    const RECORD_IMAGE = 30;
    const RECORD_SHEET = 31;
    const RECORD_SHEET_NAME = 32;
    const RECORD_SHEET_FILENAME = 33;
    const RECORD_DESIGNATOR = 34;
    const RECORD_BUS_ENTRY = 37;
    const RECORD_TEMPLATE = 39;
    const RECORD_PARAMETER = 41;
    const RECORD_WARNING_SIGN = 43;
    const RECORD_IMPLEMENTATION_LIST = 44;
    const RECORD_IMPLEMENTATION = 45;
    const RECORD_IMPLEMENTATION_1 = 46;
    const RECORD_IMPLEMENTATION_2 = 47;
    const RECORD_IMPLEMENTATION_3 = 48;
    const RECORD_HYPERLINK = 226;

    private static $records = [
        self::RECORD_HEADER              => Header::class,
        self::RECORD_COMPONENT           => Component::class,
        self::RECORD_PIN                 => Pin::class,
        self::RECORD_IEEE_SYMBOL         => IEEESymbol::class,
        self::RECORD_LABEL               => Label::class,
        self::RECORD_BEZIER              => Bezier::class,
        self::RECORD_POLYLINE            => Polyline::class,
        self::RECORD_POLYGON             => Polygon::class,
        self::RECORD_ELLIPSE             => Ellipse::class,
        self::RECORD_PIECHART            => Piechart::class,
        self::RECORD_ROUND_RECTANGLE     => RoundRectangle::class,
        self::RECORD_ELLIPTICAL_ARC      => EllipticalArc::class,
        self::RECORD_ARC                 => Arc::class,
        self::RECORD_LINE                => Line::class,
        self::RECORD_RECTANGLE           => Rectangle::class,
        self::RECORD_SHEET_SYMBOL        => SheetSymbol::class,
        self::RECORD_SHEET_ENTRY         => SheetEntry::class,
        self::RECORD_POWER_PORT          => PowerPort::class,
        self::RECORD_PORT                => Port::class,
        self::RECORD_NO_ERC              => NoERC::class,
        self::RECORD_NETLABEL            => NetLabel::class,
        self::RECORD_BUS                 => Bus::class,
        self::RECORD_WIRE                => Wire::class,
        self::RECORD_TEXT_FRAME          => TextFrame::class,
        self::RECORD_JUNCTION            => Junction::class,
        self::RECORD_IMAGE               => Image::class,
        self::RECORD_SHEET               => Sheet::class,
        self::RECORD_SHEET_NAME          => SheetName::class,
        self::RECORD_SHEET_FILENAME      => SheetFilename::class,
        self::RECORD_DESIGNATOR          => Designator::class,
        self::RECORD_BUS_ENTRY           => BusEntry::class,
        self::RECORD_TEMPLATE            => Template::class,
        self::RECORD_PARAMETER           => Parameter::class,
        self::RECORD_WARNING_SIGN        => WarningSign::class,
        self::RECORD_IMPLEMENTATION_LIST => ImplementationList::class,
        self::RECORD_IMPLEMENTATION      => Implementation::class,
        self::RECORD_IMPLEMENTATION_1    => Implementation1::class,
        self::RECORD_IMPLEMENTATION_2    => Implementation2::class,
        self::RECORD_IMPLEMENTATION_3    => Implementation3::class,
        self::RECORD_HYPERLINK           => Hyperlink::class
    ];

    /**
     * @param RawRecord $record
     * @return BaseRecord
     */
    public static function parseRecord(RawRecord $record)
    {
        if ($record->getType() != RawRecord::PROPERTY_RECORD) {
            throw new \InvalidArgumentException("Record at offset {$record->getOffset()} is not a valid property record");
        }

        preg_match_all('/(?:%UTF8%(?<Utf8Name>[A-Z0-9]+?)=(?<Utf8Value>.*?)[|]{3})|(?:(?<AsciiName>[A-Z0-9]+)=(?<AsciiValue>[^|]+)(?:[\|]|$))/U', $record->getData(), $matches, PREG_SET_ORDER);

        $properties = [];
        foreach ($matches as $match) {
            if (!empty($match['Utf8Name'])) {
                $properties[ $match['Utf8Name'] ] = $match['Utf8Value'];
            } else {
                $properties[ $match['AsciiName'] ] = $match['AsciiValue'];
            }
        }
        if (!isset($properties['RECORD'])) {
            $properties['RECORD'] = 0;
        }

        $recordId = trim($properties['RECORD']);

        if (!isset(self::$records[ $recordId ])) {
            throw new \UnexpectedValueException("Invalid record id: RECORD={$recordId}");
        }

        return new self::$records[ $recordId ] ($properties);
    }

    private $properties;

    protected function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public function getProperty($propertyName, $default = null)
    {
        if (!isset($this->properties[ $propertyName ])) {
            if ($default === null) {
                throw new \InvalidArgumentException("Unknown property: {$propertyName}");
            }

            return $default;
        }

        return $this->properties[ $propertyName ];
    }

    public function isPropertySet($propertyName)
    {
        return isset($this->properties[ $propertyName ]);
    }

    public function getBoolean($propertyName)
    {
        return $this->getProperty($propertyName, 'F') == 'T';
    }

    public function getColor($propertyName)
    {
        $data = $this->getProperty($propertyName);

        if (!is_numeric($data)) {
            throw new \InvalidArgumentException("{$data} is not a valid color");
        }

        $int = (int)$data;

        return sprintf("#%X%X%X", ($int & 0x0000FF), ($int & 0x00FF00) >> 8, ($int & 0xFF0000) >> 16);
    }

    public function getInteger($propertyName)
    {
        return (int)$this->getProperty($propertyName, 0);
    }

    public function getReal($propertyName)
    {
        return (float)$this->getProperty($propertyName, 0) + (float)$this->getProperty($propertyName . '_FRAC', 0);
    }

    public function getCoordinate($propertyNameX, $propertyNameY)
    {
        return [
            'x' => $this->getReal($propertyNameX),
            'y' => $this->getReal($propertyNameY)
        ];
    }
}