<?php
/* This file is part of Euclid | ExoProject | (c) 2021 I-is-as-I-does | MIT License */

namespace ExoProject\EuclidTest;
use ExoProject\Euclid\EuclidTools;

class DemoDoer
{
    public $bobName; 

    public function setBobName(){ //@doc: will be ignored because method hook is 'build*'
        $this->bobName = "Bob";
        EuclidTools::msg('setBobName done: '.$this->bobName,'green');
    }

    protected function buildSandCastle() //@doc: will be ignored because is not a public function
    {
        $sandCstleBldr = new SandCastleBuilder();
        EuclidTools::msg('buildSandCastle done: '.$sandCstleBldr->getProof(),'green');
    }

    public function buildDirTree($parentdir, $subdirs = false)
    {
        try {
            $parentpath = dirname(__DIR__).'\\'.$parentdir;
            if (!is_dir($parentpath)) {
                mkdir($parentpath);
                EuclidTools::msg('buildDirTree done: '.$parentpath, 'green');
            }
            if (!empty($subdirs)) {
               if(!is_array($subdirs)){
                   $subdirs = [$subdirs];
               }
                foreach ($subdirs as $subdir) {
                    $subdirpath = $parentpath.'\\'.$subdir;
                    if (!is_dir($subdirpath)) {
                        mkdir($subdirpath);
                        EuclidTools::msg('buildDirTree done: '.$subdirpath, 'green');
                    } else {
                        EuclidTools::msg('buildDirTree: dir '.$subdirpath.' already exists', 'yellow');
                    }
                }
            }
        } catch (\Exception $e){
            EuclidTools::msg('buildDirTree an error occured: '.$e, 'red');       
    }
    }

    public function buildFile(){
        $path = __DIR__.'\\'.'test.txt';
        $now = date("Y-m-d H:i:s");
        $write = file_put_contents($path,$now.' I\'m still alive'.PHP_EOL,FILE_APPEND | LOCK_EX);
        if($write === false){
            EuclidTools::msg('buildFile write error: '.$path,'red');
        } else {
            EuclidTools::msg('buildFile done: '.$path,'green');
        }
    }
}