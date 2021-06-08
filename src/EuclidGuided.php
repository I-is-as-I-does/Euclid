<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;

use SSITU\Jack\Jack;

class EuclidGuided implements EuclidGuided_i
{
    private $constructMenu = [1 => 'Just call class constructor', 2 => 'Set a method to call afterward'];
    private $EuclidCore;
    private $classMenu;
    private $Companion;
    private $Parser;

    public function __construct($EuclidCore, $Companion, $Parser)
    {
        $this->EuclidCore = $EuclidCore;
        $this->Companion = $Companion;
        $this->Parser = $Parser;
    }
    public function classMenu()
    {
        $this->EuclidCore->goBackTo = 'modeMenu';
        if (empty($this->classMenu)) {
            $this->classMenu = $this->EuclidCore->getNavigation() + Jack::Arrays()->reIndex(array_keys($this->EuclidCore->cmdMap), 1);
        } else {
            $this->EuclidCore->resetAll();
        }
        $this->Companion->set_callableMap($this->classMenu);
        $requestk = $this->Companion->printCallableAndListen('Pick a class > ');
        $dispatch = $this->processGuidedRequest($requestk);
        if ($dispatch !== true) {
            return $this->EuclidCore->$dispatch();
        }
        $classKey = $this->classMenu[$requestk];
        $setclass = $this->EuclidCore->checkAndSetClassData($classKey);
        if ($setclass !== true) {
            $this->Companion::msg($setclass['err'], 'red');
            return $this->classMenu();
        }
        return $this->methodMenu();
    }

    private function processGuidedRequest($requestk)
    {
        if ($requestk == '.') {
            return 'goBack';
        } elseif ($requestk == '*') {
            return 'editMap';
        }
        return true;
    }

    public function methodMenu()
    {

        if (empty($this->EuclidCore->classKey)) {
            return $this->classMenu();
        }
        $this->EuclidCore->goBackTo = 'classMenu';
        $this->EuclidCore->resetMethodParam();
        $methodMenu = $this->EuclidCore->getNavigation() + Jack::Arrays()->reIndex(array_keys($this->EuclidCore->classData['cmdList']), 1);
        $this->Companion->set_callableMap($methodMenu);
        $requestk = $this->Companion->printCallableAndListen('Pick a method > ');
        $dispatch = $this->processGuidedRequest($requestk);
        if ($dispatch !== true) {
            return $this->EuclidCore->$dispatch();
        }

        if ($methodMenu[$requestk] == '__construct') {
            $this->EuclidCore->hasConstructor = true;
            $this->EuclidCore->classArgm = [];
            if (empty($this->EuclidCore->classData['cmdList']['__construct'])) {
                return $this->handleConstructor();
            }
        } else {
            $this->EuclidCore->methodName = $methodMenu[$requestk];
            if (empty($this->EuclidCore->classData['cmdList'][$this->EuclidCore->methodName])) {
                return $this->EuclidCore->handleCmd();
            }
        }
        return $this->argmMenu();
    }

    public function argmMenu()
    {
        if (empty($this->EuclidCore->classKey)) {
            return $this->classMenu();
        }
        if (empty($this->EuclidCore->methodName)) {
            if (!$this->EuclidCore->hasConstructor) {
                return $this->methodMenu();
            }
            $methodName = '__construct';
        } else {
            $methodName = $this->EuclidCore->methodName;
        }
        $this->EuclidCore->goBackTo = 'methodMenu';
        $this->Companion->set_callableMap([]);
        $this->Companion::msg($this->GuidedModeReadMe(), 'blue');

        $methoddata = $this->EuclidCore->classData['cmdList'][$methodName];
        $argm = [];
        foreach ($methoddata as $c => $param) {
            echo $this->Companion::msg('[param' . ($c + 1) . ']', 'cyan', false);
            $this->Companion::msg(' $' . $param, 'blue');
            echo $this->Companion::msg('Enter argument > ', 'white', false);
            $input = $this->Companion->listenToRequest();
            $dispatch = $this->processGuidedRequest($input);
            if ($dispatch !== true) {
                return $this->EuclidCore->$dispatch();
            }

            $argm[] = $this->Parser->simplePrcArgm($input);
        }

        if ($methodName == '__construct') {
            $this->EuclidCore->classArgm = $argm;
            return $this->handleConstructor();
        }
        $this->EuclidCore->methodArgm = $argm;
        return $this->EuclidCore->handleCmd();
    }

    private function handleConstructor()
    {
        $this->Companion->set_callableMap($this->constructMenu);
        $requestk = $this->Companion->printCallableAndListen('Pick an option > ');
        if ($requestk == 1) {
            return $this->EuclidCore->handleCmd();
        }
        return $this->methodMenu();
    }

    private function GuidedModeReadMe()
    {
        $helptext = [
            '# Escape strings using double quotes if required',
            '# Pass arrays as follow: ' . $this->EuclidCore->arrExample,
            '$ exit | . back' . PHP_EOL];
        return implode(PHP_EOL, $helptext);
    }

}
