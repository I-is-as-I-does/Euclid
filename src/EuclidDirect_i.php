<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */

namespace SSITU\Euclid;

interface EuclidDirect_i
{
    public function __construct($EuclidCore, $Companion, $Parser);
    public function setHelpList();
    public function outputHelpList();
    public function outputReadMe();
    public function dispatchRequest($inputs);
}
