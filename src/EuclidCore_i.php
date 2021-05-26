<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */

namespace SSITU\Euclid;

interface EuclidCore_i
{
    public function __construct($inputs = array(
    ), $mapClassOrPath = false);
    public function goBack();
    public function getNavigation($isModeMenu = false);
    public function editMap();
    public function modeMenu();
    public function resetAll();
    public function resetMethodParam();
    public function checkAndSetClassData($classKey);
    public function handleCmd();
}
