<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';
class PrimeModule {
    function __construct() {
        $this->smallprimeset = array_unique($this->primesbelow(100000));
        $this->_smallprimeset = 100000;
        $this->smallprimes = $this->primesbelow(10000);
    }
    function primesbelow($N) {
        $res = [];
        for ($i = 2; $i <= $N; $i++)
        {
            if($i % 2 != 1 && $i != 2) continue;
            $d = 3;
            $x = sqrt($i);
            while ($i % $d != 0 && $d < $x) $d += 2;
            if((($i % $d == 0 && $i != $d) * 1) == 0) $res[] = $i;
        }
        return $res;
    }

    function isprime($n, $precision = 7)
    {
        if (($n == 1) || (($n % 2) == 0)) {
            return false;
        } elseif (($n < 1)) {
            throw new Exception('Out of bounds, first argument must be > 0');
        } elseif (($n < $this->_smallprimeset)) {
            return in_array($n, $this->smallprimeset);
        }
        $d = ($n - 1);
        $s = 0;
        while (($d % 2) == 0) {
            $d = floor($d / 2);
            $s += 1;
        }
        foreach (pyjslib_range($precision) as $repeat) {
            $a = rand(2, ($n - 2));
            $x = posmod(pow($a, $d), $n);
            if (($x == 1) || ($x == ($n - 1))) {
                continue;
            }
            foreach (pyjslib_range(($s - 1)) as $r) {
                $x = posmod(pow($x, 2), $n);
                if (($x == 1)) {
                    return false;
                }
                if (($x == ($n - 1))) {
                    break;
                }
            }
        }

        return true;
    }
    function pollard_brent($n)
    {
        if ((($n % 2) == 0)) {
            return 2;
        }
        if ((($n % 3) == 0)) {
            return 3;
        }
        list($y, $c, $m) = [rand(1, ($n - 1)), rand(1, ($n - 1)), rand(1, ($n - 1))];
        list($g, $r, $q) = [1, 1, 1];
        while (($g == 1)) {
            $x = $y;
            foreach (pyjslib_range($r) as $i) {
                $y = ((posmod(pow($y, 2), $n) + $c) % $n);
            }
            $k = 0;
            while (($k < $r) && ($g == 1)) {
                $ys = $y;
                foreach (pyjslib_range(min($m, ($r - $k))) as $i) {
                    $y = ((posmod(pow($y, 2), $n) + $c) % $n);
                    $q = (($q * abs(($x - $y))) % $n);
                }
                $g = $this->gcd($q, $n);
                $k += $m;
            }
            $r *= 2;
        }
        if (($g == $n)) {
            while (true) {
                $ys = ((posmod(pow($ys, 2), $n) + $c) % $n);
                $g = $this->gcd(abs(($x - $ys)), $n);
                if (($g > 1)) {
                    break;
                }
            }
        }

        return $g;
    }
    function primefactors($n, $sort = false)
    {
        $factors = [];
        $limit = ((int)(pow($n, 0.5)) + 1);
        foreach ($this->smallprimes as $checker) {
            if (($checker > $limit)) {
                break;
            }
            while (($n % $checker) == 0) {
                $factors[] = $checker;
                $n = floor($n / $checker);
                $limit = ((int)(pow($n, 0.5)) + 1);
                if (($checker > $limit)) {
                    break;
                }
            }
        }
        if (($n < 2)) {
            return $factors;
        }
        while (($n > 1)) {
            if ($this->isprime($n)) {
                $factors[] = $n;
                break;
            }
            $factor = $this->pollard_brent($n);
            $factors[] = $this->primefactors($factor);
            $n = floor($n / $factor);
        }
        if ($sort) {
            $factors = sort($factors);
        }

        return $factors;
    }
    function factorization($n)
    {
        $factors = [];
        foreach (primefactors($n) as $p1) {
            if(isset($factors[$p1])) {
                $factors[$p1] += 1;
            } else {
                $factors[$p1] = 1;
            }
        }

        return $factors;
    }
    function totient($n)
    {
        $totients = [];
        if (($n == 0)) {
            return 1;
        }
        if(isset($totients[$n])) {
            return $totients[$n];
        }
        $tot = 1;
        foreach (factorization($n) as $p => $exp) {
            $tot *= (($p - 1) * pow($p, ($exp - 1)));
        }
        $totients[$n] = $tot;

        return $tot;
    }
    function gcd($a, $b)
    {
        if (($a == $b)) {
            return $a;
        }
        while (($b > 0)) {
            list($a, $b) = [$b, ($a % $b)];
        }

        return $a;
    }
    function lcm($a, $b)
    {
        return floor(abs(($a * $b)) / $this->gcd($a, $b));
    }

}