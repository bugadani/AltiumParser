<?php

namespace AltiumParser;

use OLEReader\Utils;

class RawRecord
{
    const PROPERTY_RECORD = 0;

    /**
     * @param $data
     * @return RawRecord[]
     */
    public static function getRecords($data)
    {
        $records = [];

        $pos = 0;
        while ($pos < strlen($data)) {
            $record    = new RawRecord($data, $pos);
            $pos       += $record->getLength() + 4;
            $records[] = $record;
        }

        return $records;
    }

    private $type;
    private $length;
    private $offset;
    private $data;

    public function __construct($data, $offset)
    {
        $len  = Utils::getInt2d($data, $offset);
        $zero = ord($data[ $offset + 2 ]);

        if ($zero !== 0) {
            throw new \UnexpectedValueException("Data is not a valid Altium record");
        }

        $this->offset = $offset;
        $this->length = $len;
        $this->type   = ord($data[ $offset + 3 ]);
        $this->data   = $data;
    }

    public function getData()
    {
        return substr($this->data, $this->offset + 4, $this->length);
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return mixed
     */
    public function getOffset()
    {
        return $this->offset;
    }

    public function __toString()
    {
        return "({$this->getType()}, len: {$this->getLength()}) -> {$this->getData()}";
    }
}