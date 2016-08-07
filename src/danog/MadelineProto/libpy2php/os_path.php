<?php

require_once dirname(__FILE__).'/os.php';

/**
 * A class to emulate python's os.path.
 */
class os_path
{
    const supports_unicode_filenames = true;

    public static function abspath($path)
    {
        return self::normpath(self::join(getcwd(), $path));
    }

    public static function basename($path)
    {
        // is this right?
        return basename($path);
    }

    public static function commonprefix($list)
    {
        $pl = 0; // common prefix length
        $n = count($list);
        $l = strlen($list[0]);
        while ($pl < $l) {
            $c = $list[0][$pl];
            for ($i = 1; $i < $n; $i++) {
                if ($list[$i][$pl] !== $c) {
                    break 2;
                }
            }
            $pl++;
        }

        return substr($list[0], 0, $pl);
    }

    public static function dirname($path)
    {
        return dirname($path);
    }

    public static function exists($path)
    {
        return file_exists($path);
    }

    public static function lexists($path)
    {
        $rc = file_exists($path);
        if (!$rc && is_link($path)) {
            return true;
        }

        return $rc;
    }

    public static function expanduser($path)
    {
        if (strpos($path, '~') !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace('~', $info['dir'], $path);
        }

        return $path;
    }

    public static function expandvars($path)
    {
        $env = count($_ENV) ?: $_SERVER;
        $map = [];
        foreach ($env as $k => $v) {
            if (!is_scalar($v)) {
                continue;
            }
            $map['$'.$k] = $v;
            $map['${'.$k.'}'] = $v;
        }

        return strtr($path, $map);
    }

    public static function getatime($path)
    {
        try {
            $rc = fileatime($path);

            return $rc;
        } catch (Exception $e) {
            throw new OSError($e->getMessage, $e->getCode());
        }
    }

    public static function getmtime($path)
    {
        try {
            $rc = filemtime($path);

            return $rc;
        } catch (Exception $e) {
            throw new OSError($e->getMessage, $e->getCode());
        }
    }

    public static function getctime($path)
    {
        try {
            $rc = filectime($path);

            return $rc;
        } catch (Exception $e) {
            throw new OSError($e->getMessage, $e->getCode());
        }
    }

    public static function getsize($path)
    {
        try {
            $rc = filesize($path);

            return $rc;
        } catch (Exception $e) {
            throw new OSError($e->getMessage, $e->getCode());
        }
    }

    public static function isabs($path)
    {
        // fixme: implement check for windows.
        return $path[0] == '/';
    }

    public static function isfile($path)
    {
        return is_file($path);
    }

    public static function isdir($path)
    {
        return is_dir($path);
    }

    public static function islink($path)
    {
        return is_link($path);
    }

    public static function ismount($path)
    {
        self::_unimplemented();
    }

    public static function split($path)
    {
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $first = implode(DIRECTORY_SEPARATOR, array_slice($parts, 0, count($parts) - 1));
        $last = $parts[count($parts) - 1];

        return [$first, $last];
    }

    public static function join($path, ...$paths)
    {
        $buf = rtrim($path, '/');
        foreach ($paths as $p) {
            $i = 0;
            $p = trim($p, '/');
            $buf .= DIRECTORY_SEPARATOR.$p;
        }

        return $buf;
    }

    public static function normcase($path)
    {
        // fixme: different behavior on windows.
        return $path;
    }

    public static function normpath($path)
    {
        return realpath($path);
    }

    public static function realpath($path)
    {
        return realpath($path);
    }

    public static function relpath($path, $start)
    {
        self::_unimplemented();
    }

    public static function samefile($path1, $path2)
    {
        return fileinode($path1) == fileinode($path2);
    }

    public static function sameopenfile($fd1, $fd2)
    {
        $s1 = fstat($fd1);
        $s2 = fstat($fd2);

        return $s1['ino'] == $s2['ino'];
    }

    public static function samestat($stat1, $stat2)
    {
        return $stat1 == $stat2;
    }

    public static function splitdrive($path)
    {
        //fixme:  implement windows case.
        return ['', $path];
    }

    public static function splitext($path)
    {
        $first = $path;
        $second = '';

        $pos = strrpos($path, '.');
        if ($pos !== false) {
            $first = substr($path, 0, $pos);
            $second = substr($path, $pos);
        }

        return [$first, $second];
    }

    public static function splitunc($path)
    {
        self::_unimplemented();
    }

    public static function walk($path, $visit, $arg)
    {
        // Note: deprecated in python 3 in favor of os.walk()
        self::_unimplemented();
    }

    private static function _unimplemented()
    {
        throw new Exception('Unimplemented.  Please consider submitting a patch to py2php project on github.');
    }
}
