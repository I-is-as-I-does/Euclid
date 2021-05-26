<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;

interface EuclidMap_i
{
    public function __construct($path = null);
    public function initMap($path);
    public function addToMap($key, $className, $methodHook = null, $prepend = false);
    public function rmvFromMap($key);
    public function updtMap($key, $value, $prop); //@doc: $prop is either 'className' or 'methodHook';
    public function saveMap($anotherPath = false);
    public function getMap();
    public function getLogErr();
    public function addSelf();
}
