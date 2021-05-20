<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;

class EuclidTools implements EuclidTools_i
{
    public static function msg($msg, $color, $echomsg = true)
    {
        switch (strtolower($color)) {
        case 'red':
            $colorid = 31;
        break;
        case 'green':
            $colorid = 32;
        break;
        case 'yellow':
            $colorid = 33;
        break;
        case 'blue':
            $colorid = 34;
        break;
        default:
            $colorid = 39;
    }
        $prepmsg = "\e[".$colorid."m$msg\e[0m";
        if ($echomsg === true) {
            echo $prepmsg.PHP_EOL;
        } else {
            return $prepmsg;
        }
    }

    public static function parseArrayArgm($array)
    {
        if (!is_array($array)) {
            return '"'.urlencode($array).'"';
        }
        $stock = [];
        foreach ($array as $k => $v) {
            $stock[] = 'a['.urlencode($k).']='.urlencode($v);
        }
        return '"'.implode('&', $stock).'"';
    }
}
