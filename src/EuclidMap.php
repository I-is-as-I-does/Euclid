<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;

use SSITU\Jack\Jack;

class EuclidMap implements EuclidMap_i
{
    protected $configPath;
    protected $cmdMap = [];
    protected $logErr = [];

    public function __construct($path = null)
    {
        if (!empty($path)) {
            $this->initMap($path);
        }
    }

    public function getLogErr()
    {
        return $this->logErr;
    }

    protected function handleUnvalidClass($key)
    {
        $this->logErr[$key] = 'Class ' . $key . ' does not exists';
        unset($this->cmdMap[$key]);
        return ['err' => 'Unvalid class name'];
    }

    public function addSelf()
    {

        return $this->addToMap('euclidMap', get_class($this), '*Map', true);

    }

    public function initMap($path)
    {
        $content = $this->getConfigFileContent($path);
        if (is_array($content) && !empty($content['maps'])) {
            $this->configPath = $path;
            $this->cmdMap = $content['maps'];

            foreach ($this->cmdMap as $key => $data) {
                if (empty($data["className"]) || !class_exists($data["className"], true)) {
                    $this->handleUnvalidClass($key);
                } else {
                    $this->buildCmdList($key, $data);
                }
            }

            return $this->getMap();
        }
        return ['err' => 'Unvalid config file'];
    }

    protected function processPath($path)
    {
        if (!empty($path) && file_exists($path)) {
            return $path;
        }

        if (empty($path) && !empty($this->configPath)) {
            return $this->configPath;
        }

        return false;
    }

    public function saveMap($anotherPath = false)
    {
        $path = $this->processPath($anotherPath);
        if ($path !== false) {
            $save = Jack::File()->saveJson(["maps" => $this->cmdMap], $path);
            if ($save !== false) {
                return $this->getMap();
            }
        }
        return ['err' => 'Unvalid path or chmod permissions'];
    }

    public function addToMap($key, $className, $methodHook = null, $prepend = false)
    {
        if (class_exists($className, true)) {
            $classdata = [
                'className' => $className,
            ];
            if (!empty($methodHook)) {
                $classdata['methodHook'] = $methodHook;
            }
            if ($prepend) {
                $this->cmdMap = [$key => $classdata] + $this->cmdMap;
            } else {
                $this->cmdMap[$key] = $classdata;
            }

            return $this->buildCmdList($key, $classdata);
        }
        return ['err' => $className . ' is not a valid class'];
    }

    public function rmvFromMap($key)
    {
        unset($this->cmdMap[$key]);

        return $this->getMap();
    }

    public function updtMap($key, $value, $prop)
    {
        if (isset($this->cmdMap[$key]) && in_array($prop, ['className', 'methodHook'])) {
            $this->cmdMap[$key][$prop] = $value;
            return $this->buildCmdList($key, $this->cmdMap[$key]);
        }
        return ['err' => 'Unvalid arguments'];
    }

    public function getMap()
    {
        return $this->cmdMap;
    }

    protected function buildCmdList($key, $classdata = null)
    {
        $cmdMap = $this->getMap();

        if (empty($cmdMap) || ($classdata === null && !isset($cmdMap[$key]))) {
            return ['err' => $key . ' is not in cmd map'];
        }
        if ($classdata === null) {
            $classdata = $cmdMap[$key];
        }
        if (empty($classdata['className']) || !class_exists($classdata['className'], true)) {
            return $this->handleUnvalidClass($key);

        }
        $classn = $classdata['className'];
        $hook = false;
        if (!empty($classdata['methodHook'])) {
            $hook = $classdata['methodHook'];
        }

        $methods = get_class_methods($classn);
        $list = [];
        foreach ($methods as $methodn) {
            if (!empty($methodn)) {
                if (empty($hook) || $hook === $methodn || stripos($methodn, str_replace("*", "", $hook)) !== false) {
                    $list[$methodn] = $this->getParamList($classn, $methodn);
                }
            }
        }

        if (!empty($list)) {
            $cmdMap[$key]['cmdList'] = $list;
            $this->cmdMap = $cmdMap;
            return $cmdMap;
        }

        return ['err' => 'Class ' . $key . ' has no eligible method'];

    }

    protected function getConfigFileContent($path = null)
    {
        $path = $this->processPath($path);
        return Jack::File()->readJson($path);
    }

    protected function getParamList($classn, $method)
    {
        $reflc = new \ReflectionMethod($classn, $method);
        $params = $reflc->getParameters();
        $stack = [];
        if (!empty($params)) {
            foreach ($params as $param) {
                $paramname = $param->getName();
                if ($param->isOptional()) {
                    $paramname .= '|opt';
                }
                $stack[] = $paramname;
            }
        }
        return $stack;
    }
}
