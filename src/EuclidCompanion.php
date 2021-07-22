<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;
use \SSITU\Jack;
class EuclidCompanion implements EuclidCompanion_i
{
    private $callableMap;

    private static $dfltNav = ['$' => 'exit', '.' => 'Euclid' . PHP_EOL];
    private static $dfltIntroMsg = 'Enter corresponding key > ';
    private static $_this;
    protected $EuclidCore;

    public function __construct()
    {
        self::$_this = $this;
    }

    public static function echoDfltNav()
    {
        self::output(self::$dfltNav, 'blue', '', ' ');
    }

    public static function inst()
    {
        if (empty(self::$_this)) {
            return new EuclidCompanion();
        }
        return self::$_this;
    }

    public function setEuclidCore($euclid)
    {
        if (is_object($euclid) && method_exists($euclid, 'goBack')) {
            $this->EuclidCore = $euclid;
            return true;
        }
        return false;
    }

    public function goToEuclid()
    {
        if (!empty($this->EuclidCore)) {
            return $this->EuclidCore->goBack();
        }

        if (class_exists('SSITU\Euclid\EuclidCore')) {
            $this->EuclidCore = new EuclidCore();
            return;
        }
        $this->Companion::msg('Apologies; Euclid not found.', 'yellow');
        exit;
    }

    public static function parseArrayArgm($array)
    {
        if (!is_array($array)) {
            return '"' . urlencode($array) . '"';
        }
        $stock = [];
        foreach ($array as $k => $v) {
            $stock[] = 'a[' . urlencode($k) . ']=' . urlencode($v);
        }
        return '"' . implode('&', $stock) . '"';
    }

    public static function msg($msg, $color = 'white', $echomsg = true)
    {
        if (is_array($msg)) {
            $msg = implode(PHP_EOL, $msg);
        }
        if (!is_string($msg)) {
            $msg = var_export($msg, true);
        }
        switch (strtolower($color)) {
            case 'red':
                $colorid = 31;
                break;
            case 'green':
                $colorid = 32;
                break;
            case 'yellow':
                $colorid = 33;
                break;
            case 'blue':
                $colorid = 34;
                break;
            case 'cyan':
                $colorid = 36;
                break;
            default:
                $colorid = 39;
        }
        $prepmsg = "\e[" . $colorid . "m$msg\e[0m";
        if ($echomsg === true) {
            echo $prepmsg . PHP_EOL;
        } else {
            return $prepmsg;
        }
    }

    public static function output($out, $color = 'auto', $b1 = '[', $b2 = ']')
    {
        if (!is_array($out)) {
            return self::msg($out, self::defineColor($out, $color), true);
        }

        foreach ($out as $k => $v) {
            $wrapK = $b1 . $k . $b2;
            echo self::msg($wrapK, self::defineColor($k, $color), false);
            $rslt = Jack\Array::flatten($v);
            if (is_array($v)) {
                echo PHP_EOL;
            }

            $lentarg1 = Jack\Array::longestKey($rslt) + 1;

            $mkey = [];
            $mval = [];
            foreach ($rslt as $itmk => $itmv) {
                $splitk = explode('.', $itmk);

                $first = array_shift($splitk);
                if (empty($first)) {
                    $first = array_shift($splitk);
                }
                $mkey[] = $first;
                $thenk = trim(implode('.', $splitk), '.');
                $spacing1 = \str_repeat(' ', $lentarg1 - strlen($first) - strlen($thenk));
                $mval[] = $thenk . $spacing1 . $itmv;
            }
            $lentarg2 = Jack\Array::longestItem($mkey) + 1;
            foreach ($mkey as $sk => $sv) {
                $spacing2 = \str_repeat(' ', $lentarg2 - strlen($sv));
                echo self::msg($sv . $spacing2, self::defineColor($sv, $color), false);
                self::msg($mval[$sk], self::defineColor($mval[$sk], $color), true);
            }
        }
    }

    public static function defineColor($item, $color)
    {
        if ($color === 'auto') {

            if (is_array($item)) {
                $color = 'blue';
            } elseif (is_bool($item)) {
                $color = 'green';
                if ($item === false) {
                    $color = 'red';
                }
            } elseif (is_string($item)) {

                if (in_array($item, ['return', 'class', 'method'])) {
                    $color = 'cyan';
                } elseif (in_array($item, ['todo', 'partial', 'partials', 'skipped', 'anomaly', 'anomalies'])) {
                    $color = 'yellow';
                } elseif (in_array($item, ['false', 'err', 'fail', 'error', 'critic'])) {
                    $color = 'red';
                } elseif (in_array($item, ['true', 'success', 'done', 'ok'])) {
                    $color = 'green';
                }
            }

        }
        return $color;
    }

    public function set_callableMap($callableMap)
    {
        if (is_array($callableMap)) {
            $this->callableMap = $callableMap;
            return true;
        }
        $this->callableMap = [];
        return false;
    }

    public function printRslt($rslt, $exit = false, $prompt = true, $newCallableMap = false)
    {
        echo PHP_EOL;
        self::output($rslt);
        echo PHP_EOL;
        if ($exit) {
            exit;
        }
        if ($prompt) {
            if ($newCallableMap !== false) {
                $setmap = $this->set_callableMap($newCallableMap);
            }
            if (!empty($this->callableMap)) {
                return $this->printCallableAndListen('Another request? > ');
            }
            return false;
        }
        return true;
    }

    public function printCallableAndListen($introMsg = null, $listcolor = 'blue')
    {
        if (!empty($this->callableMap)) {
            if (empty($introMsg)) {
                $introMsg = self::$dfltIntroMsg;
            }

            $this->output($this->callableMap, $listcolor, '', '');
            echo $introMsg;
            return $this->listenToRequest();
        }
        return false;
    }

    public function listenToRequest()
    {
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        $request = trim($line);
        if ($request === '$') {
            exit;
        }
        if ($request === '.') {
            return $this->goToEuclid();
        }
        if (empty($this->callableMap) || array_key_exists($request, $this->callableMap)) {
            return $request;
        }

        self::msg('Unknown request; please try again, or type $ to exit > ', 'yellow');
        return $this->listenToRequest();
    }

}
