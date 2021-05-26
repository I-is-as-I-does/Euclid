<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;

interface EuclidCompanion_i
{
    public function __construct();
    public static function echoDfltNav();
    public static function inst();
    public function setEuclidCore($euclid);
    public function goToEuclid();
    public function set_callableMap(array $callableMap);
    public function printRslt($rslt, $exit = false, $prompt = true, $newCallableMap = false);
    public function printCallableAndListen($introMsg = null, $listcolor = 'blue');
    public function listenToRequest();

    public static function parseArrayArgm($array);
    public static function msg($msg, $color = 'white', $echomsg = true);
    public static function output($out, $color = 'auto', $brackets = true);
    public static function autoColor($k, $v, $color);

}
