<?php

namespace danog\MadelineProto;

/**
 * Some tools.
 */
class tools
{
    /**
     * posmod(numeric,numeric) : numeric
     * Works just like the % (modulus) operator, only returns always a postive number.
     */
    public function posmod($a, $b)
    {
        $resto = $a % $b;
        if ($resto < 0) {
            $resto += abs($b);
        }

        return $resto;
    }

    public function fread_all($handle)
    {
        $pos = ftell($handle);
        fseek($handle, 0);
        $content = fread($handle, fstat($handle)['size']);
        fseek($handle, $pos);

        return $content;
    }

    public function fopen_and_write($filename, $mode, $data)
    {
        $handle = fopen($filename, $mode);
        fwrite($handle, $data);
        rewind($handle);

        return $handle;
    }

    public function string2bin($string)
    {
        $res = null;
        foreach (explode('\\', $string) as $s) {
            if ($s != null && strlen($s) == 3) {
                $res .= hex2bin(substr($s, 1));
            }
        }

        return $res;
    }
}
