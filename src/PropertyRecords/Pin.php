<?php

namespace AltiumParser\PropertyRecords;

use AltiumParser\RawRecord;
use OLEReader\Utils;

/**
 * Base class that holds most of the pin information. This one is different from other record classes because in some
 * versions of Altium Designer, the pin record is stored in a binary format.
 *
 * @todo maybe handle the case where RECORD=2 is in plaintext?
 *
 * @package AltiumParser\PropertyRecords
 */
class Pin extends BaseRecord
{
    const OFFSET_OWNER_PART_ID = 5;

    const OFFSET_INSIDE_EDGE_SYMBOL = 8;
    const OFFSET_OUTSIDE_EDGE_SYMBOL = 9;
    const OFFSET_INSIDE_SYMBOL = 10;
    const OFFSET_OUTSIDE_SYMBOL = 11;
    const OFFSET_DESCRIPTION_LENGTH = 12;
    const OFFSET_DESCRIPTION = 13;

    const OFFSET_PIN_ELECTRIC_TYPE = 14;
    const OFFSET_PIN_CONGLOMERATE = 15;
    const OFFSET_PIN_LENGTH = 16;
    const OFFSET_COORDINATE_X = 18;
    const OFFSET_COORDINATE_Y = 20;

    const OFFSET_NAME_LENGTH = 26;
    const OFFSET_NAME = 27;

    const OFFSET_DESIGNATOR_LENGTH = 27;
    const OFFSET_DESIGNATOR = 28;

    const PIN_ELECTRIC_TYPE_INPUT = 0;
    const PIN_ELECTRIC_TYPE_IO = 1;
    const PIN_ELECTRIC_TYPE_OUTPUT = 2;
    const PIN_ELECTRIC_TYPE_OPEN_COLLECTOR = 3;
    const PIN_ELECTRIC_TYPE_PASSIVE = 4;
    const PIN_ELECTRIC_TYPE_HiZ = 5;
    const PIN_ELECTRIC_TYPE_OPEN_EMITTER = 6;
    const PIN_ELECTRIC_TYPE_POWER = 7;

    public function __construct(RawRecord $record)
    {
        $recordString = $record->getData();

        /*
         * Binary record format - incomplete
         * Byte 0: RECORD=2
         * Byte 1: 0
         * Byte 2: 0
         * Byte 3: 0
         * Byte 4: 0
         * Byte 5: Part id
         * Byte 6: 0
         * Byte 7: 0
         * Byte 8: inside edge 3=clock
         * Byte 9: outside edge symbol 1=dot
         * Byte 10: inside symbol
         * Byte 11: outside symbol
         * Byte 12: Description (string)
         * Byte 13: formaltype? in that case, fixed 1
         * Byte 14: pin electric type
         * byte 15: Pin Conglomerate: 0, Locked, ?, Designator, Name, Hide, Orientation
         * Byte 16: Pin length
         * Byte 17: 0
         * Byte 18-19: X
         * Byte 20-21: Y
         * Byte 22-23-24: Color
         * Byte 25: 0
         * Byte 26: Name (stirng)
         * Byte 27: Designator (string)
         *
         * More data stored in accompanying files
         */
        $descriptionLength = ord($recordString[ self::OFFSET_DESCRIPTION_LENGTH ]);
        $description       = substr($recordString, self::OFFSET_DESCRIPTION, $descriptionLength);

        $nameLength = ord($recordString[ self::OFFSET_NAME_LENGTH + $descriptionLength ]);
        $name       = substr($recordString, self::OFFSET_NAME + $descriptionLength, $nameLength);

        $designatorLength = ord($recordString[ self::OFFSET_DESIGNATOR_LENGTH + $nameLength + $descriptionLength ]);
        $designator       = substr($recordString, self::OFFSET_DESIGNATOR + $nameLength + $descriptionLength, $designatorLength);

        $parameters = [
            'RECORD'           => 2,
            'OWNERPARTID'      => ord($recordString[ self::OFFSET_OWNER_PART_ID ]),
            'SYMBOL_INNEREDGE' => ord($recordString[ self::OFFSET_INSIDE_EDGE_SYMBOL ]),
            'SYMBOL_OUTEREDGE' => ord($recordString[ self::OFFSET_OUTSIDE_EDGE_SYMBOL ]),
            'SYMBOL_OUTER'     => ord($recordString[ self::OFFSET_OUTSIDE_SYMBOL ]),
            'SYMBOL_INNER'     => ord($recordString[ self::OFFSET_INSIDE_SYMBOL ]),
            'ELECTRICAL'       => ord($recordString[ self::OFFSET_PIN_ELECTRIC_TYPE + $descriptionLength ]),
            'PINCONGLOMERATE'  => ord($recordString[ self::OFFSET_PIN_CONGLOMERATE + $descriptionLength ]),
            'PINLENGTH'        => ord($recordString[ self::OFFSET_PIN_LENGTH + $descriptionLength ]),
            'LOCATION.X'       => Utils::getInt2d($recordString, self::OFFSET_COORDINATE_X + $descriptionLength),
            'LOCATION.Y'       => Utils::getInt2d($recordString, self::OFFSET_COORDINATE_Y + $descriptionLength),
            'DESCRIPTION'      => $description,
            'NAME'             => $name,
            'DESIGNATOR'       => $designator,
        ];

        parent::__construct($parameters);
    }
}