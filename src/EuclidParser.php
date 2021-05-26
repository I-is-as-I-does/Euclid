<?php
/* This file is part of Euclid | SSITU | (c) 2021 I-is-as-I-does | MIT License */
namespace SSITU\Euclid;

class EuclidParser implements EuclidParser_i
{
    private $output = [];
    private $input;
    private $arglist;

    private $knownResolvedInput = [];
    private $resolved = [];

    public function process($input)
    {
        $this->reset();
        $search = array_search($input, $this->knownResolvedInput);
        if ($search !== false) {
            return $this->resolved[$search];
        }

        if (is_string($input)) {

            $this->input = $input;
            $parsed = $this->detailedParsing();
        } else {

            $parsed = $this->simpleParse($input);
        }
        if ($parsed !== false) {
            $this->knownResolvedInput[] = $input;
            $this->resolved[] = $this->output;

            return $this->output;
        }
        return false;

    }

    public function reset()
    {
        $this->output = [];

        $this->input = null;
        $this->arglist = null;

    }

    private function simpleIsMethod($inp)
    {
        if (strlen($inp) > 2 && substr($inp, 0, 2) == '->') {
            return true;
        }
        return false;
    }

    public function simplePrcArgm($inp)
    {
        $tryArr = $this->prcArrArgm($inp);
        if ($tryArr !== false) {
            return $try;
        }
        return $this->prcStandardArgm($inp);
    }

    private function prcArrArgm($inp)
    {
        if (!empty($this->matchArgmArr($inp))) {

            return $this->parseArr($inp);
        }
        return false;
    }

    private function matchArgmArr($inp)
    {
        preg_match('/^a\[.*\]=/', $inp, $matches);
        return $matches;
    }

    private function parseArr($arrInput)
    {
        $rslt = [];
        parse_str($arrInput, $rslt);
        $out = [];

        foreach ($rslt['a'] as $arrKey => $arrEntry) {
            $out[$arrKey] = $this->simplePrcArgm($arrEntry);
        }

        return $out;
    }

    private function prcStandardArgm($inp)
    {
        if (is_string($inp) && substr($inp, 0, 1) === '"' && substr($inp, -1) === '"') {
            return substr($inp, 1, -1);
        }
        return $inp;
    }

    private function simpleParse($input)
    {
        if (is_array($input) && !empty($input)) {
            $this->output['classKey'] = $input[array_key_first($input)];
            if (count($input) > 1) {
                $input = array_slice($input, 1);
                foreach ($input as $inp) {
                    if (empty($this->output['methodName'])) {
                        if ($this->simpleIsMethod($inp)) {
                            $this->output['methodName'] = $inp;
                        } else {
                            $this->output['classArgm'][] = $this->simplePrcArgm($inp);
                        }
                    } else {
                        $this->output['methodArgm'][] = $this->simplePrcArgm($inp);
                    }
                }
            }

            return true;
        }
        return false;
    }

    private function parseArgList($arglist)
    {
        $this->arglist = trim($arglist);
        if (stripos($this->arglist, '"') === false) {
            return $this->prcNoQuotesList($this->arglist);
        }

        $out = [];

        while (strlen($this->arglist) > 0) {
            $tryArr = $this->parseArrItem();

            if ($tryArr !== false) {
                $out[] = $tryArr;
            } else {
                $out[] = $this->parseStrItem();
            }
        }

        return $out;
    }

    private function prcNoQuotesList($arglist)
    {
        $out = [];
        $arrArgm = explode(' ', trim($arglist));
        foreach ($arrArgm as $arg) {
            $try = $this->prcArrArgm($arg);
            if ($try !== false) {
                $out[] = $try;
            } else {
                $out[] = $arg;
            }
        }
        return $out;
    }

    private function parseArrItem()
    {
        $arglist = trim($this->arglist);
        $matchArr = $this->matchArgmArr($arglist);

        if (!empty($matchArr)) {

            $part1 = $matchArr[0];

            $this->arglist = trim(substr($arglist, strlen($part1)));

            $part2 = $this->parseStrItem();
            if ($part2 === false) {
                $part2 = '"' . $this->walkStrInQuotes() . '"'; //@doc: have to temporarely put back quotes for parser to succeed
            }

            return $this->parseArr($part1 . $part2);
        }
        return false;
    }

    private function parseStrItem()
    {

        $arglist = trim($this->arglist);

        if ($arglist[0] !== '"' || stripos($arglist, '"', 1) === false) { //@doc no quotes or odd case of solo opening quotes, with no ending quotes
            $splitArg = explode(' ', $arglist);
            $this->arglist = trim(substr($arglist, strlen($splitArg[0])));
            return $splitArg[0];
        }

        $str = substr($arglist, 1);
        $stack = '';
        $strArr = str_split($str);
        foreach ($strArr as $k => $v) {
            if ($v == '"' && $k !== 0 && $strArr[$k - 1] != '\\') {
                break;
            }
            $stack .= $v;
        }

        $this->arglist = trim(substr($str, strlen($stack) + 1));
        return $stack;
    }

    private function extractClassKey()
    {
        preg_match('/^\w+(?=\s|$)/', $this->input, $classKeyMatch);
        if (!empty($classKeyMatch)) {
            $this->output['classKey'] = $classKeyMatch[0];
            $this->input = trim(substr($this->input, strlen($this->output['classKey'])));
            return true;
        }
        return false;
    }

    private function extractMethodName()
    {
        preg_match('/(^|\s)->\w+(?=\s|$)/', $this->input, $methodNameMatch); //@no class argms
        if (!empty($methodNameMatch)) {
            $this->output['methodName'] = trim($methodNameMatch[0], " \-\>\t\n\r\0\x0B");
            return true;
        }
        return false;
    }

    private function detailedParsing()
    {

        if (!empty($this->input) && $this->extractClassKey() !== false) {

            if (strlen($this->input) > 0) {
                if ($this->extractMethodName() !== false) {
                    $splitArg = explode('->' . $this->output['methodName'], $this->input);

                    if (!empty($splitArg[0])) {
                        $this->output['classArgm'] = $this->parseArgList($splitArg[0]);
                    }
                    if (!empty($splitArg[1])) {

                        $this->output['methodArgm'] = $this->parseArgList($splitArg[1]);
                    }
                } else {
                    $this->output['classArgm'] = $this->parseArgList($splitArg[0]);
                }
            }

            return true;
        }

        return false;

    }
    public function getResult()
    {
        return $this->output;
    }

}
