<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;

class EuclidCore implements EuclidCore_i
{

    public $cmdMap;
    private $euclidMap;
    private $Parser;

    public $goBackTo = 'modeMenu';

    private $Companion;
    public $navigation = ['$' => 'exit', '.' => 'back', '*' => 'edit'];
    public $arrExample = 'a[]=bob&a[]=0.5&a[b]="some other bob"';
    private $modesMenu = [1 => 'direct: enter full cmd in one line', 2 => 'guided: easily navigate classes, methods and parameters'];
    private $anotherRequestMenu = ['$' => 'exit', '*' => 'edit map', 1 => 'reset | direct mode', 2 => 'reset | guided mode', 3 => 're-run same cmd'];

    public $classKey;
    public $classData;
    public $hasConstructor;
    public $classArgm;
    public $currentClass;

    public $methodName;
    public $methodArgm;

    public $guidedMode;
    public $directMode;

    public function __construct($inputs = [], $mapClassOrPath = false)
    {
        $this->Companion = EuclidCompanion::inst();
        $this->Companion->setEuclidCore($this);

        $this->Parser = new EuclidParser($this);

        $this->guidedMode = new EuclidGuided($this, $this->Companion, $this->Parser);
        $this->directMode = new EuclidDirect($this, $this->Companion, $this->Parser);

        $this->EuclidHeader();

        $this->euclidMap = $this->getEuclidMap($mapClassOrPath);

        $cmdMap = $this->euclidMap->getMap();
        $mapErr = $this->euclidMap->getLogErr();
        if (!empty($mapErr)) {
            $this->Companion::msg('Errors occured while mapping classes: ' . PHP_EOL . implode(' ', $mapErr) . PHP_EOL, 'red');
        }
        if (empty($cmdMap)) {
            $this->Companion::msg('Cannot proceed: cmd map is empty', 'red');
            return $this->resolveConfigPath(false);
        }

        $this->cmdMap = $cmdMap;
        if (count($inputs) > 1) {
            $inputs = array_slice($inputs, 1);

            return $this->directMode->dispatchRequest($inputs);
        }
        return $this->modeMenu();
    }

    private function EuclidHeader()
    {
        $this->Companion::msg(PHP_EOL . implode(str_repeat(' ', 2), str_split('|EUCLID|')) . PHP_EOL, 'blue');
    }

    public function goBack()
    {
        $method = $this->goBackTo;
        if (method_exists($this->guidedMode, $method)) {
            return $this->guidedMode->$method();
        }
        return $this->modeMenu();
    }

    public function getNavigation($isModeMenu = false)
    {
        $nav = $this->navigation;
        if ($isModeMenu) {
            $nav = array_slice($nav, 0, 1);
        } elseif ($this->classKey == 'euclidMap') {
            $nav = array_slice($nav, 0, 2);
        }
        $nav[array_key_last($nav)] .= PHP_EOL;
        return $nav;

    }

    public function editMap()
    {
        $this->resetAll();
        $this->cmdMap = $this->euclidMap->addSelf();
        $this->checkAndSetClassData('euclidMap');
        $this->currentClass = $this->euclidMap;
        return $this->guidedMode->methodMenu();
    }

    private function getEuclidMap($mapClassOrPath)
    {
        if (is_object($mapClassOrPath) && \method_exists($mapClassOrPath, 'getMap')) {
            return $mapClassOrPath;
        }
        return $this->resolveConfigPath($mapClassOrPath);
    }

    private function resolveConfigPath($mapPath)
    {
        if (!empty($mapPath) && is_file($mapPath)) {
            return new EuclidMap($mapPath);
        }
        $this->Companion->set_callableMap([]);
        $this->Companion::msg(implode(PHP_EOL, ['Please enter path to config file', '$ to exit']), 'blue');
        echo $this->Companion::msg('> ', 'white', false);
        $mapPath = $this->Companion->listenToRequest();
        return $this->resolveConfigPath($mapPath);
    }

    public function modeMenu()
    {
        unset($this->cmdMap['euclidMap']);
        $this->Companion->set_callableMap($this->getNavigation(true) + $this->modesMenu);
        $requestk = $this->Companion->printCallableAndListen('Pick a mode > ');
        if ($requestk == 1) {
            return $this->directMode->outputHelpList();
        }
        return $this->guidedMode->classMenu();
    }

    public function resetAll()
    {
        $this->classKey = '';
        $this->classData = [];
        $this->classArgm = [];
        $this->hasConstructor = false;
        $this->currentClass = null;
        $this->resetMethodParam();
    }
    public function resetMethodParam()
    {
        $this->methodName = '';
        $this->methodArgm = [];
    }

    public function checkAndSetClassData($classKey)
    {
        $err = [];
        if (!array_key_exists($classKey, $this->cmdMap)) {
            $err[] = 'Class ' . $classKey . ' is not in cmd list';
        } else {
            if (empty($this->cmdMap[$classKey]['cmdList'])) {
                $err[] = 'Class ' . $classKey . ' does not have public methods';
            }
            if (empty($this->cmdMap[$classKey]['className'])) {
                $err[] = 'Full class name has not been specified for "' . $classKey . '"';
            }
        }
        if (!empty($err)) {
            unset($this->cmdMap[$classKey]);
            return ['err' => implode(PHP_EOL, $err)];
        }
        $this->classKey = $classKey;
        $this->classData = $this->cmdMap[$classKey];
        return true;

    }

    private function initClass()
    {
        if (!empty($this->classData)) {

            $classname = $this->classData['className'];
            if (class_exists($classname, true)) {

                if (!empty($this->hasConstructor) && !empty($this->classArgm)) {
                    return new $classname(...$this->classArgm);
                }
                return new $classname();
            }
        }
        return ['err' => 'Class does not exists'];

    }

    private function callMethod()
    {
        if (empty($this->currentClass) || empty($this->methodName) || !method_exists($this->currentClass, $this->methodName)) {
            return ['err' => 'Method does not exist'];
        }
        $runMethodName = $this->methodName;
        if (!empty($this->methodArgm)) {
            return $this->currentClass->$runMethodName(...$this->methodArgm);
        }
        return $this->currentClass->$runMethodName();
    }

    public function handleCmd()
    {
        if (empty($this->classKey)) {
            return $this->modeMenu();
        }

        if (empty($this->currentClass)) {
            $init = $this->initClass();
            if (is_array($init) && isset($init['err'])) {
                $this->Companion::msg('An error occured: ' . $init['err'], 'red');
                return $this->modeMenu();
            }
            $this->currentClass = $init;
        }
        $rslt = [];
        $rslt['class'] = $this->classKey;
        if (!empty($this->methodName)) {
            $rslt['method'] = $this->methodName;
            $return = $this->callMethod();
            if ($this->classKey == 'euclidMap' && empty($return['err'])) {
                $this->cmdMap = $return;
                if ($this->methodName !== 'getMap') {
                    $return = 'success';
                }
            }
            $rslt['return'] = var_export($return, true);
        } else {
            $rslt['method'] = 'constructor';
            $rslt['return'] = 'done';
        }

        return $this->dispatchAnotherRequest($rslt);
    }

    private function dispatchAnotherRequest($rslt)
    {
        if (empty($this->classData)) {
            return $this->modeMenu();
        }
        $newCallableMap = $this->anotherRequestMenu;

        if (count($this->classData['cmdList']) > 1) {
            $newCallableMap[4] = 'call another method of class "' . $this->classKey . '"';
        }

        if (!empty($this->methodArgm)) {
            $newCallableMap[5] = 'call method "' . $this->methodName . '" with new arguments';
        }
        $newCallableMap[array_key_last($newCallableMap)] .= PHP_EOL;

        $requestk = $this->Companion->printRslt($rslt, false, true, $newCallableMap);

        switch ($requestk) {
            case 1:
                $this->directMode->outputHelpList();
                break;
            case 2:
                $this->guidedMode->classMenu();
                break;
            case 3:
                $this->handleCmd();
                break;
            case 4:
                $this->guidedMode->methodMenu();
                break;
            case 5:
                $this->guidedMode->argmMenu();
                break;
            default:
                $this->modeMenu();
        }
        return;
    }

}
