<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;

interface EuclidMap_i
{
    public function setCustomConfigPath($path, $permanent = false);
    public function unsetPermanentCustomConfigPath();
    public function setMapFromConfig($path = null);
    public function addToMap($key, $className, $methodHook = null);
    public function remvFromMap($key);
    public function updateMap($key, $value, $jsonkey); //@doc: $jsonkey is either 'className' or 'methodHook';
    public function saveEdits($path = null, $content = null);
    public function buildCmdList($key, $classdata = null);
    public function getMap();
}
