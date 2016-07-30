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
            $range = $r;
            while (!$range->equals($zero)) {
                $y = $y->powMod($two, $n)->add($c)->powMod($one, $n);
                $range = $range->subtract($one);
            }
            $k = $zero;
            while ($k->compare($r) == -1 && $g->equals($one)) {
                $ys = $y;
                $range = $big->min($m, $r->subtract($k));
                while (!$range->equals($zero)) {
                    $y = $y->powMod($two, $n)->add($c)->powMod($one, $n);
                    $q = $q->multiply($x->subtract($y)->abs())->powMod($one, $n);
                    $range = $range->subtract($one);
                }
                $g = $q->gcd($n);
                $k = $k->add($m);
            }
            $r = $r->multiply($two);
        }
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

/*
function pqPrimeLeemon ($what) {
  $minBits = 64;
  $minLen = ceil($minBits / $bpe) + 1;
  $it = 0
  $a = new Array(minLen)
  $b = new Array(minLen)
  $c = new Array(minLen)
  $g = new Array(minLen)
  $z = new Array(minLen)
  $x = new Array(minLen)
  $y = new Array(minLen)

  for ($i = 0; $i < 3; $i++) {
    $q = (nextRandomInt(128) & 15) + 17
    copyInt_(x, nextRandomInt(1000000000) + 1)
    copy_(y, x)
    lim = 1 << (i + 18)

    for (j = 1; j < lim; j++) {
      ++it
      copy_(a, x)
      copy_(b, x)
      copyInt_(c, q)

      while (!isZero(b)) {
        if (b[0] & 1) {
          add_(c, a)
          if (greater(c, what)) {
            sub_(c, what)
          }
        }
        add_(a, a)
        if (greater(a, what)) {
          sub_(a, what)
        }
        rightShift_(b, 1)
      }

      copy_(x, c)
      if (greater(x, y)) {
        copy_(z, x)
        sub_(z, y)
      } else {
        copy_(z, y)
        sub_(z, x)
      }
      eGCD_(z, what, g, a, b)
      if (!equalsInt(g, 1)) {
        break
      }
      if ((j & (j - 1)) == 0) {
        copy_(y, x)
      }
    }
    if (greater(g, one)) {
      break
    }
  }

  divide_(what, g, x, y)

  if (greater(g, x)) {
    P = x
    Q = g
  } else {
    P = g
    Q = x
  }

  // console.log(dT(), 'done', bigInt2str(what, 10), bigInt2str(P, 10), bigInt2str(Q, 10))

  return [bytesFromLeemonBigInt(P), bytesFromLeemonBigInt(Q), it]
}*/
}
