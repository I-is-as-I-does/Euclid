<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */

namespace SSITU\Euclid;

interface EuclidParser_i
{
    public function process($input);
    public function reset();
    public function simplePrcArgm($inp);
    public function getResult();
}
