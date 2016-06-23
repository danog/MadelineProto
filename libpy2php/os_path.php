<?php

require_once(dirname(__FILE__) . '/os.php');

/**
 * A class to emulate python's os.path
 */
class os_path {
    
    const supports_unicode_filenames = true;
    
    static function abspath($path) {
        return self::normpath(self::join(getcwd(), $path));
    }

    static function basename($path) {
        // is this right?
        return basename($path);
    }
    
    static function commonprefix($list) {
        $pl = 0; // common prefix length
        $n = count($list);
        $l = strlen($list[0]);
        while ($pl < $l) {
            $c = $list[0][$pl];
            for ($i=1; $i<$n; $i++) {
                if ($list[$i][$pl] !== $c) {
                    break 2;
                }
            }
            $pl++;
        }
        return substr($list[0], 0, $pl);
    }

    static function dirname($path) {
        return dirname($path);
    }
    
    static function exists($path) {
        return file_exists($path);
    }
    
    static function lexists($path) {
        $rc = file_exists($path);
        if( !$rc && is_link($path) ) {
            return true;
        }
        return $rc;
    }

    static function expanduser($path) {
        if( strpos($path, '~') !== false) {
            $info = posix_getpwuid(posix_getuid());
            $path = str_replace('~', $info['dir'], $path);
        }
        return $path;
    }
    
    static function expandvars($path) {
        $env = count($_ENV) ?: $_SERVER;
        $map = array();
        foreach( $env as $k => $v ) {
            if( !is_scalar( $v )) {
                continue;
            }
            $map['$' . $k] = $v;
            $map['${' . $k . '}'] = $v;
        }
        return strtr($path, $map);
    }
    
    static function getatime($path) {
        try {
            $rc = fileatime($path);
            return $rc;
        }
        catch( Exception $e ) {
            throw new OSError($e->getMessage, $e->getCode());
        }
    }

    static function getmtime($path) {
        try {
            $rc = filemtime($path);
            return $rc;
        }
        catch( Exception $e ) {
            throw new OSError($e->getMessage, $e->getCode());
        }
    }

    static function getctime($path) {
        try {
            $rc = filectime($path);
            return $rc;
        }
        catch( Exception $e ) {
            throw new OSError($e->getMessage, $e->getCode());
        }
    }

    static function getsize($path) {
        try {
            $rc = filesize($path);
            return $rc;
        }
        catch( Exception $e ) {
            throw new OSError($e->getMessage, $e->getCode());
        }
    }

    static function isabs($path) {
        // fixme: implement check for windows.
        return $path[0] == '/';
    }
    
    static function isfile($path) {
        return is_file($path);
    }
    
    static function isdir($path) {
        return is_dir($path);
    }
    
    static function islink($path) {
        return is_link($path);
    }
    
    static function ismount($path) {
        self::_unimplemented();
    }
    
    static function split($path) {
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $first =  implode(DIRECTORY_SEPARATOR, array_slice($parts, 0, count($parts)-1 ));
        $last = $parts[count($parts)-1];
        return array($first, $last);
    }
    
    static function join($path, ...$paths) {
        $buf = rtrim($path, '/');
        foreach( $paths as $p ) {
            $i = 0;
            $p = trim( $p, '/');
            $buf .= DIRECTORY_SEPARATOR . $p;
        }
        return $buf;
    }
    
    static function normcase($path) {
        // fixme: different behavior on windows.
        return $path;
    }
    
    static function normpath($path) {
        return realpath($path);
    }
    
    static function realpath($path) {
        return realpath($path);
    }
    
    static function relpath($path, $start) {
        self::_unimplemented();
    }
    
    static function samefile($path1, $path2) {
        return fileinode($path1) == fileinode($path2);
    }
    
    static function sameopenfile($fd1, $fd2) {
        $s1 = fstat( $fd1 );
        $s2 = fstat( $fd2 );
        return $s1['ino'] == $s2['ino'];
    }
    
    static function samestat($stat1, $stat2) {
        return $stat1 == $stat2;
    }
    
    static function splitdrive($path) {
        //fixme:  implement windows case.
        return array('', $path);
    }
    
    static function splitext($path) {
        $first = $path;
        $second = '';
        
        $pos = strrpos( $path, '.');
        if( $pos !== false ) {
            $first = substr($path, 0, $pos);
            $second = substr($path, $pos);
        }
        
        return array($first, $second);
    }
    
    static function splitunc($path) {
        self::_unimplemented();
    }
    
    static function walk($path, $visit, $arg) {
        // Note: deprecated in python 3 in favor of os.walk()
        self::_unimplemented();
    }
    
    private static function _unimplemented() {
        throw new Exception( "Unimplemented.  Please consider submitting a patch to py2php project on github.");
    }
    
}