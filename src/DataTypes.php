<?php

namespace AltiumParser;

class DataTypes
{
    public static function toColor($data)
    {
        if (!is_numeric($data)) {
            throw new \InvalidArgumentException("{$data} is not a valid color");
        }

        $int = (int)$data;
        $hex = sprintf("#%X%X%X", ($int & 0x0000FF), ($int & 0x00FF00) >> 8, ($int & 0xFF0000) >> 16);

        return $hex;
    }
    public static function toAltiumColor($data)
    {
        list($r, $g, $b) = sscanf($data, "#%2X%2X%2X");

        return $r & 0xFF | (($g & 0xFF) << 8) | (($b & 0xFF) << 16);
    }
}