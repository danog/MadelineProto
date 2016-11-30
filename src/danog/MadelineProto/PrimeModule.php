<?php
/*
Copyright 2016 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with the MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/

namespace danog\MadelineProto;

class PrimeModule extends Tools
{
    // Uses https://github.com/LonamiWebs/Telethon/blob/master/telethon/crypto/factorizator.py, thank you so freaking much!
    public function find_small_multiplier_lopatin($what)
    {
        $g = 0;
        foreach ($this->range(3) as $i) {
            $q = (rand(0, 127) & 15) + 17;
            $x = rand(0, 1000000000) + 1;
            $y = $x;
            $lim = 1 << ($i + 18);
            foreach ($this->range(1, $lim) as $j) {
                list($a, $b, $c) = [$x, $x, $q];
                while ($b != 0) {
                    if (($b & 1) != 0) {
                        $c += $a;
                        if ($c >= $what) {
                            $c -= $what;
                        }
                    }
                    $a += $a;
                    if ($a >= $what) {
                        $a -= $what;
                    }
                    $b >>= 1;
                }
                $x = $c;
                $z = ($x < $y) ? $y - $x : $x - $y;
                $g = $this->gcd($z, $what);
                if ($g != 1) {
                    break;
                }

                if (($j & ($j - 1)) == 0) {
                    $y = $x;
                }
            }
            if ($g > 1) {
                break;
            }
        }
        $p = $what; // g
        return min($p, $g);
    }

    public function gcd($a, $b)
    {
        while ($a != 0 && $b != 0) {
            while ($b & 1 == 0) {
                $b >>= 1;
            }
            while ($a & 1 == 0) {
                $a >>= 1;
            }
            if ($a > $b) {
                $a -= $b;
            } else {
                $b -= $a;
            }
        }

        return ($b == 0) ? $a : $b;
    }

    public function PrimeFactors($pq)
    {
        $pqstr = (string) $pq;
/*
        \danog\MadelineProto\Logger::log('Trying to use the python factorization module');
        if (function_exists('shell_exec')) {
            try {
                $res = json_decode(shell_exec('python '.__DIR__.'/getpq.py '.$pqstr));
                if (count($res) == 2) {
                    return $res;
                }
            } catch (Exception $e) {
            }
        }

        \danog\MadelineProto\Logger::log('Trying to use the wolfram alpha factorization module');
        $query = 'Do prime factorization of '.$pqstr;
        $params = [
            'async'         => true,
            'banners'       => 'raw',
            'debuggingdata' => false,
            'format'        => 'moutput',
            'formattimeout' => 8,
            'input'         => $query,
            'output'        => 'JSON',
            'proxycode'     => json_decode(file_get_contents('http://www.wolframalpha.com/api/v1/code'), true)['code'],
        ];
        $url = 'https://www.wolframalpha.com/input/json.jsp?'.http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Referer: https://www.wolframalpha.com/input/?i='.urlencode($query)]);
        curl_setopt($ch, CURLOPT_URL, $url);
        $res = json_decode(curl_exec($ch), true);
        curl_close($ch);
        foreach ($res['queryresult']['pods'] as $cur) {
            if ($cur['id'] == 'Divisors') {
                $res = explode(', ', preg_replace(["/{\d+, /", "/, \d+}$/"], '', $cur['subpods'][0]['moutput']));
                break;
            }
        }
        if (count($res) == 2) {
            return $res;
        }
*/
        \danog\MadelineProto\Logger::log('Trying to use the native factorization module');
        $res = $this->find_small_multiplier_lopatin((int) $pqstr);
        $res = [$res, $pqstr / $res];
        if ($res[1] != 1) {
            return $res;
        }


        throw new Exception("Couldn't calculate pq!");
    }
}
