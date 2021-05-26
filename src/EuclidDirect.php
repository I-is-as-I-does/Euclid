<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;

class EuclidDirect implements EuclidDirect_i
{
    private $helplist;
    private $EuclidCore;
    private $Companion;
    private $Parser;

    private $reservedChar = ["%", "?", "#", "*"];

    public function __construct($EuclidCore, $Companion, $Parser)
    {
        $this->EuclidCore = $EuclidCore;
        $this->Companion = $Companion;
        $this->Parser = $Parser;
    }

    public function setHelpList()
    {

        $helplist = ['#   Cmd List   #' . PHP_EOL];

        foreach ($this->EuclidCore->cmdMap as $key => $classdata) {
            if (!empty($classdata['cmdList'])) {
                $main = $key;
                if (!empty($classdata['cmdList']['__construct'])) {
                    $main .= ' ' . implode(' ', $classdata['cmdList']['__construct']);
                    unset($classdata['cmdList']['__construct']);
                }
                foreach ($classdata['cmdList'] as $methodname => $methodparam) {
                    if (!empty($methodname)) {
                        $cmd = $main . ' ->' . $methodname;
                        if (!empty($methodparam)) {
                            $cmd .= ' ' . implode(' ', $methodparam);
                        }
                        $helplist[] = $cmd;
                    }
                }
            }
        }
        $helplist[] = PHP_EOL . '* edit map | ? readme | $ exit | % change mode';
        $this->helplist = implode(PHP_EOL, $helplist);
        return $this->helplist;
    }

    public function outputHelpList()
    {
        if (empty($this->helplist)) {
            $this->helplist = $this->setHelpList();
        }
        $this->Companion->set_callableMap([]);
        $this->Companion::msg($this->helplist, 'blue');
        echo $this->Companion::msg('Enter your cmd > ', 'white', false);
        $requestk = $this->Companion->listenToRequest();
        return $this->dispatchRequest($requestk);
    }

    public function outputReadMe()
    {
        $this->Companion->set_callableMap([]);
        $this->Companion::msg($this->DirectModeReadMe(), 'blue');
        echo $this->Companion::msg('Enter your cmd > ', 'white', false);
        $requestk = $this->Companion->listenToRequest();
        return $this->dispatchRequest($requestk);
    }

    private function dispatchMenuRequest(string $keyword)
    {
        switch ($keyword) {
            case '*':
                $this->EuclidCore->editMap();
                break;
            case '?':
                $this->outputReadMe();
                break;
            case '#':
                $this->outputHelpList();
                break;
            case '%':
                $this->EuclidCore->guidedMode->classMenu();
                break;
            default:
                $this->EuclidCore->modeMenu();
        }
        return;
    }

    public function dispatchRequest($inputs)
    {
        $this->EuclidCore->resetAll();
        $this->goBackTo = 'modeMenu';

        if (is_string($inputs) && strlen($inputs) === 1 && in_array($inputs, $this->reservedChar)) {
            return $this->dispatchMenuRequest($keyword);
        }

        $process = $this->parseAndProcessRequest($inputs);
        if ($process !== true) {
            $this->Companion::msg($process['err'], 'red');
            return $this->EuclidCore->modeMenu();
        }
        return $this->EuclidCore->handleCmd();
    }

    private function parseAndProcessRequest($inputs)
    {
        $parsed = $this->Parser->process($inputs);

        $setClassData = $this->EuclidCore->checkAndSetClassData($parsed['classKey']);
        if ($setClassData !== true) {
            return $setClassData;
        }

        $checkList = [];

        if (array_key_exists('__construct', $this->EuclidCore->classData['cmdList'])) {
            $this->EuclidCore->hasConstructor = true;
            $checkList['classArgm'] = '__construct';
        }

        if (empty($parsed['methodName']) && !$this->EuclidCore->hasConstructor) {
            return ['err' => 'Method has not been specified; and class does not have a constructor'];

        } elseif (!empty($parsed['methodName'])) {
            if (!array_key_exists($parsed['methodName'], $this->EuclidCore->classData["cmdList"])) {
                return ['err' => 'Unknown method: ' . $parsed['methodName'] . ' in class: ' . $parsed['classKey']];
            }
            $this->EuclidCore->methodName = $parsed['methodName'];
            $checkList['methodArgm'] = $parsed['methodName'];
        }

        if (!empty($checkList)) {
            foreach ($checkList as $key => $method) {
                if (array_key_exists($key, $parsed)) {
                    $check = $this->checkArgmCount($this->EuclidCore->classData["cmdList"][$method], $parsed[$key]);
                    if ($check !== true) {
                        return $check;
                    }
                    $this->EuclidCore->$key = $parsed[$key];

                }
            }
        }
        return true;
    }

    private function checkArgmCount($expctparams, $givenparams)
    {
        $diff = count($expctparams) - count($givenparams);
        if ($diff < 0) {
            return $this->paramError('too many', $expctparams);
        }
        if ($diff > 0) {
            $optcount = 0;
            foreach ($expctparams as $expctparam) {
                if (stripos($expctparam, '|opt') !== false) {
                    $optcount++;
                    if ($diff > $optcount) {
                        return $this->paramError('not enough', $expctparams);
                    }
                }
            }
        }
        return true;
    }

    private function paramError($situation, $expctparams)
    {
        return ['err' => $situation . ' arguments for requested method; ' . PHP_EOL . 'parameters list:' . PHP_EOL . implode(PHP_EOL, $expctparams), 'red'];
    }

    private function DirectModeReadMe()
    {
        $helptext = [
            '?   NanoReadMe   ?' . PHP_EOL,
            '--  Cmd syntax  --',
            'myClass ->myMethod',
            'myClass constrArg1 constrArg2 ->myMethod',
            'myClass constrArg1 constrArg2 ->myMethod methodArg1 methodArg2',
            '--  Tips  --',
            'Escape strings using double quotes or urlencode() if required',
            'Pass arrays with method \'parseArrayArgm\' available in EuclidCompanion, or as follow:',
            $this->EuclidCore->arrExample . PHP_EOL,
            '* edit map | # display cmd list | $ exit | % change mode'];
        return implode(PHP_EOL, $helptext);
    }

}
