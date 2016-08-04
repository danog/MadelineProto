<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';



class PrimeModule
{
    public function __construct()
    {
        $this->smallprimeset = array_unique($this->primesbelow(100000));
        $this->_smallprimeset = 100000;
        $this->smallprimes = $this->primesbelow(10000);
    }

    public function primesbelow($N)
    {
        $res = [];
        for ($i = 2; $i <= $N; $i++) {
            if ($i % 2 != 1 && $i != 2) {
                continue;
            }
            $d = 3;
            $x = sqrt($i);
            while ($i % $d != 0 && $d < $x) {
                $d += 2;
            }
            if ((($i % $d == 0 && $i != $d) * 1) == 0) {
                $res[] = $i;
            }
        }

        return $res;
    }

    public function isprime($n, $precision = 7)
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
            $s++;
        }
        $break = false;
        foreach (pyjslib_range($precision) as $repeat) {
            $a = rand(2, ($n - 2));
            $x = posmod(pow($a, $d), $n);
            if (($x == 1) || ($x == ($n - 1))) {
                continue;
            }
            foreach (pyjslib_range($s - 1) as $r) {
                $x = posmod(pow($x, 2), $n);
                if (($x == 1)) {
                    return false;
                }
                if (($x == ($n - 1))) {
                    $break = true;
                }
            }
            if (!$break) {
                return false;
            }
        }

        return true;
    }
    // taken from https://github.com/enricostara/telegram-mt-node/blob/master/lib/security/pq-finder.js
    public function getpq($pq) {
        $zero = new \phpseclib\Math\BigInteger(0);
        $one = new \phpseclib\Math\BigInteger(1);
        $two = new \phpseclib\Math\BigInteger(2);
        $three = new \phpseclib\Math\BigInteger(3);

        $p = new \phpseclib\Math\BigInteger();
        $q = new \phpseclib\Math\BigInteger();
	while (!$pq->equals($p->multiply($q))) {
            for ($i = 0; $i < 3; $i++) {
                $q = new \phpseclib\Math\BigInteger((random_int(0, 128) & 15) + 17);
                $x = new \phpseclib\Math\BigInteger(random_int(0, 1000000000) + 1);
                $y = $x;
                $lim = 1 << ($i + 18);
                for ($j = 1; $j < $lim; $j++) {
                    $a = $x;
                    $b = $x;
                    $c = $q;
                    while (!$b->equals($zero)) {
                        if ($b->powMod($one, $two)->equals($zero)) {
                           $c = $c->add($a);
                           if ($c->compare($pq) > 0) {
                                $c = $c->subtract($pq);
                           }
                        }
                        $a = $a->add($a);
                        if ($a->compare($pq) > 0) {
                            $a = $a->subtract($pq);
                        }
                        $b = $b->rightShift(1);
                    }
                    $x = $c;
                    $z = ($y->compare($x) > 0) ? $y->subtract($x) : $x->subtract($y);
                    $p = $z->gcd($pq);
                    if (!$p->equals($one)) {
                       break;
                    }
                    if (($j & ($j - 1)) === 0) {
                        $y = $x;
                    }
                }
                if (prime.gt(BigInteger.One())) {
                    break;
                }
            }
            $q = $pq->divide(prime)[0];
        }
        $_pq = ($q->compare($p) > 0) ? [$p, $q] : [$q, $p];
        return $_pq;
    }
    public function pollard_brent($n)
    {
        $zero = new \phpseclib\Math\BigInteger(0);
        $one = new \phpseclib\Math\BigInteger(1);
        $two = new \phpseclib\Math\BigInteger(2);
        $three = new \phpseclib\Math\BigInteger(3);
        if ($n->powMod($one, $two)->toString() == '0') {
            return 2;
        }
        if ($n->powMod($one, $three)->toString() == '0') {
            return 3;
        }
        $big = new \phpseclib\Math\BigInteger();
        $max = $n->subtract($one);
        list($y, $c, $m) = [new \phpseclib\Math\BigInteger(87552211475113995), new \phpseclib\Math\BigInteger(330422027228888537), new \phpseclib\Math\BigInteger(226866727920975483)];
        //[$big->random($one, $max), $big->random($one, $max), $big->random($one, $max)];
        list($g, $r, $q) = [$one, $one, $one];
        while ($g->equals($one)) {
            $x = $y;
            $params = ['y' => $y, 'two' => $two, 'c' => $c, 'one' => $one, 'n' => $n];
            $r->loopforeach(function ($i, $params) {
                $params['y'] = $params['y']->powMod($params['two'], $params['n'])->add($params['c'])->powMod($params['one'], $params['n']);
            }, $params);
            each($params);
            $k = $zero;
            while ($k->compare($r) == -1 && $g->equals($one)) {
                $ys = $y;
                $params = ['x' => $x, 'y' => $y, 'two' => $two, 'c' => $c, 'one' => $one, 'n' => $n, 'q' => $q];
                $m->min($r->subtract($k))->loopforeach(function ($i, $params) {
                    $params['y'] = $params['y']->powMod($params['two'], $params['n'])->add($params['c'])->powMod($params['one'], $params['n']);
                    $params['q'] = $params['q']->multiply($params['x']->subtract($params['y'])->abs())->powMod($params['one'], $params['n']);
                }, $params);
                each($params);
                $g = $q->gcd($n);
                $k = $k->add($m);
            }
            $r = $r->multiply($two);
        }
        die;
        if ($g->equals($n)) {
            while (true) {
                $ys = $ys->powMod($two, $n)->add($c)->powMod($one, $n);
                $g = $x->subtract($ys)->abs()->gcd($n);
                if ($g->compare($one) == 1) {
                    break;
                }
            }
        }

        return $g;
    }

    public function primefactors($n, $sort = false)
    {
        $factors = [];
        $n = new \phpseclib\Math\BigInteger(1724114033281923457);
var_dump($this->getpq($n));
        $one = new \phpseclib\Math\BigInteger(1);
        $two = new \phpseclib\Math\BigInteger(2);
        $limit = $n->root()->add($one);
        foreach ($this->smallprimes as $checker) {
            $checker = new \phpseclib\Math\BigInteger($checker);
            if ($limit->compare($checker) == -1) {
                break;
            }
            while ($n->modPow($one, $checker)->toString() == '0') {
                $factors[] = $checker;
                $n = $n->divide($checker)[0];
                $limit = $n->root()->add($one);
                if ($limit->compare($checker) == -1) {
                    break;
                }
            }
        }
        if ($n->compare($two) == -1) {
            return $factors;
        }
        while ($n->compare($two) == 1) {
            if ($n->isprime()) {
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

    public function factorization($n)
    {
        $factors = [];
        foreach (primefactors($n) as $p1) {
            if (isset($factors[$p1])) {
                $factors[$p1] += 1;
            } else {
                $factors[$p1] = 1;
            }
        }

        return $factors;
    }

    public function totient($n)
    {
        $totients = [];
        if (($n == 0)) {
            return 1;
        }
        if (isset($totients[$n])) {
            return $totients[$n];
        }
        $tot = 1;
        foreach (factorization($n) as $p => $exp) {
            $tot *= (($p - 1) * pow($p, ($exp - 1)));
        }
        $totients[$n] = $tot;

        return $tot;
    }

    public function gcd($a, $b)
    {
        if (($a == $b)) {
            return $a;
        }
        while (($b > 0)) {
            list($a, $b) = [$b, posmod($a, $b)];
        }

        return $a;
    }

    public function lcm($a, $b)
    {
        return floor(abs(($a * $b)) / $this->gcd($a, $b));
    }

}
