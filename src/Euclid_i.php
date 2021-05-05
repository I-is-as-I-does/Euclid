<?php
/* This file is part of Euclid | ExoProject | (c) 2021 I-is-as-I-does | MIT License */
namespace ExoProject\Euclid;

interface Euclid_i
{
    public function __construct($argm, $cmdMap = null);
    public function handleRequest($argm);
}
