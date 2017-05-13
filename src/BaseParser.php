<?php

namespace Maghead\SqliteParser;

use Exception;

class BaseParser
{
    /**
     *  @var int
     *
     *  The default buffer offset
     */
    protected $p = 0;

    /**
     * @var string
     *
     * The buffer string for parsing.
     */
    protected $str = '';

    protected function consume($token, $typeName)
    {
        if (($p2 = stripos($this->str, $token, $this->p)) !== false && $p2 == $this->p) {
            $this->p += strlen($token);

            return new Token($typeName, $token);
        }

        return false;
    }

    protected function tryParseKeyword(array $keywords, $as = 'keyword')
    {
        $this->ignoreSpaces();
        $this->sortKeywordsByLen($keywords);

        foreach ($keywords as $keyword) {
            $p2 = stripos($this->str, $keyword, $this->p);
            if ($p2 === $this->p) {
                $this->p += strlen($keyword);

                return new Token($as, $keyword);
            }
        }

        return;
    }

    protected function cur()
    {
        return $this->str[ $this->p ];
    }

    protected function expectKeyword(array $keywords, $as = 'keyword')
    {
        if ($t = $this->tryParseKeyword($keywords, $as)) {
            return $t;
        }
        throw new Exception("Expect keywords " . join(',',$keywords) . ':' . $this->currentWindow());
    }

    protected function expect($c)
    {
        if ($c === $this->str[$this->p]) {
            ++$this->p;
            return true;
        }
        throw new Exception("Expect '$c': ".$this->currentWindow());
    }

    protected function advance($c = null)
    {
        if (!$this->metEnd()) {
            if ($c) {
                if ($c === $this->str[$this->p]) {
                    ++$this->p;
                    return true;
                }
            } else {
                ++$this->p;
            }
        }
    }

    /**
     * return the current buffer window
     */
    protected function currentWindow($window = 32)
    {
        return var_export(substr($this->str, $this->p, $window) . '...', true)." FROM '{$this->str}'\n";
    }

    protected function metEnd()
    {
        return $this->p + 1 >= $this->strlen;
    }

    protected function ignoreSpaces()
    {
        while (!$this->metEnd() && in_array($this->str[$this->p], [' ', "\t", "\n"])) {
            ++$this->p;
        }
    }

    protected function metComma()
    {
        return !$this->metEnd() && $this->str[$this->p] == ',';
    }

    protected function skipComma()
    {
        if (!$this->metEnd() && $this->str[ $this->p ] == ',') {
            ++$this->p;
        }
    }

    protected function test(array $str)
    {
        foreach ($str as $s) {
            $p = stripos($this->str, $s, $this->p);
            if ($p === $this->p) {
                return strlen($s);
            }
        }
    }
}
