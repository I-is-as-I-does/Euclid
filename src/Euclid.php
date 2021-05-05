<?php
/* This file is part of Euclid | ExoProject | (c) 2021 I-is-as-I-does | MIT License */
namespace ExoProject\Euclid;

class Euclid implements Euclid_i
{
    protected $cmdMap;

    public function __construct($argm, $cmdMap = null)
    {
        if (!empty($argm)) {
            if (empty($cmdMap)) {
                $cmdMapClass = new EuclidMap();
                $cmdMap = $cmdMapClass->getMap();
                if (empty($cmdMap)) {
                    EuclidTools::msg('Cannot proceed: cmd map is empty', 'red');
                    exit;
                }
            }

            $this->cmdMap = $cmdMap;
            if ($argm[1] == 'readme') {
                EuclidTools::msg($this->NanoReadMe(), 'blue');
                exit;
            }
            if ($argm[1] == 'help') {
                $helplist = ['#   Cmd List   #'];
                foreach ($this->cmdMap as $key => $classdata) {
                    if (!empty($classdata['cmdList'])) {
                        $main = $key;
                        if (!empty($classdata['cmdList']['__construct'])) {
                            $main .= ' '.implode(' ', $classdata['cmdList']['__construct']);
                            unset($classdata['cmdList']['__construct']);
                        }
                        foreach ($classdata['cmdList'] as $methodname => $methodparam) {
                            if (!empty($methodname)) {
                                $cmd = $main.' ->'.$methodname;
                                if (!empty($methodparam)) {
                                    $cmd .= ' '.implode(' ', $methodparam);
                                }
                                $helplist[] = $cmd;
                            }
                        }
                    }
                }
                EuclidTools::msg(implode(PHP_EOL, $helplist), 'blue');
                exit;
            }
            $this->handleRequest($argm);
        }
    }
    
    public function handleRequest($argm)
    {
        $key = $argm[1];
        if (!isset($this->cmdMap[$key])) {
            EuclidTools::msg($key.': unknown class', 'red');
            exit;
        }
        $classdata = $this->cmdMap[$key];
        if (empty($classdata['className'])) {
            EuclidTools::msg('Full class name not specified', 'red');
            exit;
        }
        $classname = $classdata['className'];

        $classargm = [];
        $method = false;
        $methodargm = [];
        
        for ($a=2; $a < count($argm);$a++) {
            $arg = $argm[$a];
            if (substr($arg, 0, 2) === '->' && $method === false) {
                $method = substr($arg, 2);
            } else {
                if (preg_match('/a\[.*\]=/', $arg)) {
                    $rslt = [];
                    parse_str($arg, $rslt);
                    $arg = $rslt['a'];
                }
                if ($method !== false) {
                    $methodargm[] = $arg;
                } else {
                    $classargm[] = $arg;
                }
            }
        }
        if ($method === false) {
            EuclidTools::msg('Method not specified; please prepend method name with ->', 'red');
            exit;
        }
        
        if (!empty($classargm) && !empty($classdata["cmdList"]['__construct'])) {
            $this->checkArgmCount($classdata["cmdList"]['__construct'], $classargm);
            $class = new $classname(...$classargm);
        } else {
            if (!empty($classargm)) {
                EuclidTools::msg('Class '.$key.' does not have parameters; specified arguments will be ignored.', 'yellow');
            }
            $class = new $classname();
        }
        
        $methodname = $method;
        if (!empty($classdata['methodHook'])) {
            $hook = $classdata['methodHook'];
            if ($hook[0] === '*') {
                $methodname .= substr($hook, 1);
            } elseif ($hook[-1] === '*') {
                $methodname = substr($hook, 0, -1).$method;
            }
        }
        
        if (!isset($classdata["cmdList"][$method]) || !method_exists($class, $methodname)) {
            $methodslist = array_keys($classdata["cmdList"]);
            EuclidTools::msg($method.': unknown method in class '.$key.PHP_EOL.'Suggestions: '.PHP_EOL.implode(PHP_EOL, $methodslist), 'red');
            exit;
        }

        if (!empty($methodargm)) {
            $this->checkArgmCount($classdata["cmdList"][$method], $methodargm);
            var_dump($methodargm);
            $class->$methodname(...$methodargm);
        } else {
            if (!empty($methodargm)) {
                EuclidTools::msg('Method '.$methodname.' does not have parameters; specified arguments will be ignored.', 'yellow');
            }
            $class->$methodname();
        }
    }

    protected function checkArgmCount($expctparams, $givenparams)
    {
        $diff = count($expctparams) - count($givenparams);
        if ($diff < 0) {
            $this->paramError('too many');
            exit;
        }
        if ($diff > 0) {
            $optcount = 0;
            foreach ($expctparams as $expctparam) {
                if (stripos($expctparam, '|opt') !== false) {
                    $optcount++;
                    if ($diff > $optcount) {
                        $this->paramError('not enough');
                        exit;
                    }
                }
            }
        }
    }

    protected function paramError($situation)
    {
        EuclidTools::msg(implode(' ', $argm).' '.$situation.' arguments for requested method; '.PHP_EOL.'parameters list:'.PHP_EOL.$expctparams, 'red');
    }
 
    private function NanoReadMe()
    {
        $helptext = [
            '#   NanoReadMe   #',
            '--  Cmd syntax  --',
            'myClass ->myMethod',
            'myClass classArg1 classArg2 ->myMethod',
            'myClass classArg1 classArg2 ->myMethod methodArg1 methodArg2',
            '--  Tips  --',
            'Escape strings if required with comma or urlencode()',
            'Pass arrays with method in EuclidTools or as follow:',
            'a[]=bob&a[]=0.1&a[b]="some other bob"'];
        return implode(PHP_EOL, $helptext);
    }
}
