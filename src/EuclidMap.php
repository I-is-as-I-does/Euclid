<?php
/* This file is part of Euclid | ExoProject | (c) 2021 I-is-as-I-does | MIT License */
namespace ExoProject\Euclid;

class EuclidMap implements EuclidMap_i
{
    protected $dflt_config_filename = 'euclid_config';
    protected $configPath;
    protected $cmdMap = [];

    public function setCustomConfigPath($path, $permanent = false)
    {
        if (file_exists($path)) {
            if ($permanent !== true) {
                $this->configPath = $path;
                return true;
            }
            $dfltconfigpath = $this->getDlftConfigPath();
            $content = $this->getConfigFileContent($dfltconfigpath);
            if (!is_array($content)) {
                $content= [];
            }
                $content['REDIRECT'] = $path;
                $save = $this->saveEdits($dfltconfigpath, $content);
                if ($save !== false) {
                    $this->configPath = $path;
                    return true;
                }
        }
        return false;
    }

    public function unsetPermanentCustomConfigPath()
    {
        $path = $this->getDlftConfigPath();
        $content = $this->getConfigFileContent($path);
        if ($content===false) {
            return false;
        }
        if (!isset($this->configPath) || (isset($content['REDIRECT']) && $this->configPath == $content['REDIRECT'])) {
            $this->configPath = $path;
        }
        if (!isset($content['REDIRECT'])) {
            return true;
        }
        unset($content['REDIRECT']);
        return $this->saveEdits($path, $content);
    }


    public function setMapFromConfig($path = null)
    {
        $content = $this->getConfigFileContent($path);
        if (!empty($content)) {
            if (!empty($content['REDIRECT']) && $path !== $content['REDIRECT']) {
                $nwpath = $content['REDIRECT'];
                $nwcontent = $this->getConfigFileContent($nwpath);
                if (!empty($nwcontent)) {
                    $content = $nwcontent;
                    $path = $nwpath;
                }
            }
            $this->cmdMap = $content['maps'];
            $savefile = false;
            foreach ($this->cmdMap as $key => $data) {
                if (empty($data["className"])) {
                    unset($this->cmdMap[$key]);
                    $savefile = true;
                }
                if (empty($data["cmdList"])) {
                    $this->buildCmdList($key, $data);
                    $savefile = true;
                }
            }
            if ($savefile === true) {
                $save = $this->saveEdits($path, $content);
                if ($save === false) {
                    return false;
                }
            }
            
            return true;
        }
        return false;
    }

    public function saveEdits($path = null, $content = null)
    {

        if (empty($path)) {
            $path = $this->getFallbackPath($path);
        }
        if (file_exists($path)) {

            if (empty($content)) {
                if (!empty($this->cmdMap)) {
                    $content = ["maps"=>$this->cmdMap];
                } else {
                    $content = ["maps"=>[]];
                }
            }
            return file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT), LOCK_EX);
        }
        return false;
    }

    public function addToMap($key, $className, $methodHook = null)
    {
        $classdata = [
            'className'=>$className
        ];
        if(!empty($methodHook)){
            $classdata['methodHook'] = $methodHook;
        }
        $this->cmdMap[$key] = $classdata;
        return $this->buildCmdList($key, $classdata);
    }

    public function remvFromMap($key)
    {
        if (isset($this->cmdMap[$key])) {
            unset($this->cmdMap[$key]);
        }
        return true;
    }

    public function updateMap($key, $value, $jsonkey)
    {
        if (isset($this->cmdMap[$key]) && in_array($jsonkey, ['className','methodHook'])) {
            $this->cmdMap[$key][$jsonkey] = $value;
            $this->buildCmdList($key, $this->cmdMap[$key]);
        }
        return false;
    }

    public function getMap()
    {
        if (!isset($this->cmdMap)) {
            if ($this->setMapFromConfig() === false) {
                return false;
            }
        }
        return $this->cmdMap;
    }
   
    public function buildCmdList($key, $classdata = null)
    {
        $cmdMap = $this->getMap();
        if ($cmdMap === false) {
            return false;
        }
        if ($classdata === null) {
            if (!isset($cmdMap[$key])) {
                return false;
            }
            $classdata = $cmdMap[$key];
        }
        if (!empty($classdata['className'])) {
            $classn = $classdata['className'];
            $class = new $classn();
            $hook = false;
            if (!empty($classdata['methodHook'])) {
                $hook = $classdata['methodHook'];
            }
            $methods = get_class_methods($class);
            $list = [];
            foreach ($methods as $methodn) {
                if (!empty($methodn)) {
                    if (empty($hook) || $hook === $methodn) {
                        $list[$methodn] = $this->getParamList($classn, $methodn);
                    } elseif (stripos($methodn, str_replace("*", "", $hook)) !== false) {
                        $hooklen = strlen($hook)-1;
                        if ($hook[0] === '*') {
                            $methodkey = substr($methodn, 0, -$hooklen);
                        } else {
                            $methodkey = substr($methodn, $hooklen);
                        }
                        $list[$methodkey] = $this->getParamList($classn, $methodn);
                    }
                }
            }

            if (!empty($list)) {
                $cmdMap[$key]['cmdList'] = $list;
                $this->cmdMap = $cmdMap;
                return true;
            }
        }
        return false;
    }
    
    protected function getDlftConfigPath()
    {
        return dirname(__DIR__).'\\config\\'.$this->dflt_config_filename.'.json';
    }

    protected function getFallbackPath($path)
    {
        if(!empty($path) && file_exists($path)){
            return $path;
        }
        if (!empty($this->configPath)) {
            return $this->configPath;
        }
        return $this->getDlftConfigPath();
    }

    protected function getConfigFileContent($path = null)
    {
        if (empty($path)) {
            $path = $this->getFallbackPath($path);
        }
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
        return false;
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
