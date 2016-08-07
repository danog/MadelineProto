<?php

class OSError extends Exception
{
}


class os
{
    const F_OK = 0x0001;
    const R_OK = 0x0002;
    const W_OK = 0x0004;
    const X_OK = 0x0008;

    const SEEK_SET = SEEK_SET;
    const SEEK_CUR = SEEK_CUR;
    const SEEK_END = SEEK_END;

    const EX_OK = 0;
    const EX_USAGE = 1;
    const EX_DATAERR = 2;
    const EX_NOINPUT = 3;
    const EX_NOUSER = 4;
    const EX_NOHOST = 5;
    const EX_UNAVAILABLE = 6;
    const EX_SOFTWARE = 7;
    const EX_OSERR = 8;
    const EX_OSFILE = 9;
    const EX_CANTCREAT = 10;
    const EX_IOERR = 11;
    const EX_TEMPFAIL = 12;
    const EX_PROTOCOL = 13;
    const EX_NOPERM = 14;
    const EX_CONFIG = 15;
    const EX_NOTFOUND = 16;
    const EX_ = 17;

    const O_RDONLY = 0x0001;
    const O_WRONLY = 0x0002;
    const O_RDWR = 0x0004;
    const O_APPEND = 0x0008;
    const O_CREAT = 0x0010;
    const O_EXCL = 0x0020;
    const O_TRUNC = 0x0040;

    const TMP_MAX = PHP_INT_MAX;

    public $environ;
    public $pathconf_names = [];

    public static function ctermid()
    {
        return posix_ctermid();
    }

    public static function getegid()
    {
        return posix_getegid();
    }

    public static function geteuid()
    {
        return posix_geteuid();
    }

    public static function getgid()
    {
        return posix_getgid();
    }

    public static function getgroups()
    {
        return posix_getgroups();
    }

    public static function initgroups($username, $gid)
    {
        return posix_initgroups($username, $gid);
    }

    public static function getlogin()
    {
        return posix_getlogin();
    }

    public static function getpgid()
    {
        return posix_getpgid();
    }

    public static function getpgrp()
    {
        return posix_getpgrp();
    }

    public static function getpid()
    {
        return posix_getpid();
    }

    public static function getresuid()
    {
        self::_unimplemented();
    }

    public static function getresgid()
    {
        self::_unimplemented();
    }

    public static function getuid()
    {
        return posix_getuid();
    }

    public static function getenv($varname, $value = null)
    {
        return getenv($varname, $value);
    }

    public static function putenv($varname, $value)
    {
        putenv("$varname=$value");
    }

    public static function setegid($egid)
    {
        posix_setegid($egid);
    }

    public static function seteuid($euid)
    {
        posix_seteuid($euid);
    }

    public static function setgid($gid)
    {
        posix_setgid($gid);
    }

    public static function setgroups($groups)
    {
        self::_unimplemented();
    }

    public static function setpgrp()
    {
        self::_unimplemented();
    }

    public static function setpgid($pid, $pgrp)
    {
        posix_setpgid($pid, $pgrp);
    }

    public static function setregid($rgid, $egid)
    {
        self::_unimplemented();
    }

    public static function setresgid($rgid, $egid, $sgid)
    {
        self::_unimplemented();
    }

    public static function setresuid($ruid, $euid, $suid)
    {
        self::_unimplemented();
    }

    public static function setreuid($ruid, $euid)
    {
        self::_unimplemented();
    }

    public static function getsid($pid)
    {
        return posix_getsid();
    }

    public static function setsid()
    {
        posix_setsid();
    }

    public static function setuid($uid)
    {
        posix_setuid($uid);
    }

    public static function strerror($code)
    {
        self::_unimplemented();
    }

    public static function umask($mask)
    {
        umask($mask);
    }

    public static function uname()
    {
        return posix_uname();
    }

    public static function unsetenv($varname)
    {
        unset($_ENV[$varname]);
    }

    public static function fdopen($fd, $mode = null, $bufsize = null)
    {
        return new pyjslib_file($fd);
    }

    public static function popen($command, $mode = null, $bufsize = null)
    {
        self::_unimplemented();
    }

    public static function tmpfile()
    {
        return tmpfile();
    }

    public static function popen2($cmd, $mode = null, $bufsize = null)
    {
        self::_unimplemented();
    }

    public static function popen3($cmd, $mode = null, $bufsize = null)
    {
        self::_unimplemented();
    }

    public static function popen4($cmd, $mode = null, $bufsize = null)
    {
        self::_unimplemented();
    }

    public static function close($fd)
    {
        fclose($fd);
    }

    public static function closerange($fd_low, $fd_high)
    {
        self::_unimplemented();
    }

    public static function dup($fd)
    {
        self::_unimplemented();
    }

    public static function dup2($fd)
    {
        self::_unimplemented();
    }

    public static function fchmod($fd, $mode)
    {
        self::_unimplemented();
    }

    public static function fchown($fd, $uid, $gid)
    {
        self::_unimplemented();
    }

    public static function fdatasync($fd)
    {
        self::_unimplemented();
    }

    public static function fpathconf($fd, $name)
    {
        self::_unimplemented();
    }

    public static function fstat($fd)
    {
        $info = fstat($fd);
        $obj = new stdClass();
        foreach ($arr as $key => $v) {
            $attr = 'st_'.$key;
            $obj->$attr = $v;
        }

        return $obj;
    }

    public static function fstatvfs($fd)
    {
        self::_unimplemented();
    }

    public static function fsync($fd)
    {
        fsync($fd);
    }

    public static function ftruncate($fd, $length)
    {
        ftruncate($fd, $length);
    }

    public static function isatty($fd)
    {
        return posix_isatty($fd);
    }

    public static function lseek($fd, $pos, $how)
    {
        lseek($fd, $pos, $how);
    }

    public static function open($file, $flags, $mode = 0777)
    {
        // todo: define and map flag constants.  See list at:
        // https://docs.python.org/2/library/os.html#open-flag-constants

        $fl = '';
        if ($flags & self::O_RDONLY) {
            $fl .= 'r';
        }
        if ($flags & self::O_WRONLY) {
            $fl .= 'w';
        }
        if ($flags & self::O_RDWR) {
            $fl .= 'rw';
        }
        if ($flags & self::O_APPEND) {
            $fl .= 'a';
        }
        if ($flags & self::O_CREAT) {
            $fl .= 'c';
        }
        if ($flags & self::O_EXCL) {
            $fl .= 'x';
        }
        if ($flags & self::O_TRUNC) {
            $fl .= 'w';
        }

        return fopen($file, $fl, false);
    }

    public static function pipe()
    {
        self::_unimplemented();
    }

    public static function read($fd, $n)
    {
        return fread($fd, $n);
    }

    public static function tcgetpgrp($fd)
    {
        self::_unimplemented();
    }

    public static function tcsetpgrp($fd, $pg)
    {
        self::_unimplemented();
    }

    public static function ttyname($fd)
    {
        return posix_ttyname($fd);
    }

    public static function write($fd, $str)
    {
        return fwrite($fd, $str);
    }

    public static function access($path, $mode)
    {
        return posix_access($path, $mode);
    }

    public static function chdir($path)
    {
        chdir($path);
    }

    public static function fchdir($path)
    {
        fchdir($path);
    }

    public static function getcwd()
    {
        return getcwd();
    }

    public static function getcwdu()
    {
        return getcwd();
    }

    public static function chflags($path, $flags)
    {
        self::_unimplemented();
    }

    public static function chroot($path)
    {
        chroot($path);
    }

    public static function chmode($path, $mode)
    {
        self::_unimplemented();
    }

    public static function chown($path, $uid, $gid)
    {
        chown($path, $uid, $gid);
    }

    public static function lchflags($path, $flags)
    {
        self::_unimplemented();
    }

    public static function lchmod($path, $mode)
    {
        self::_unimplemented();
    }

    public static function lchown($path, $uid, $gid)
    {
        self::_unimplemented();
    }

    public static function link($source, $link_name)
    {
        link($source, $link_name);
    }

    public static function listdir($path)
    {
        self::_unimplemented();
    }

    public static function lstat($path)
    {
        self::_unimplemented();
    }

    public static function mkfifo($path, $mode = 0666)
    {
        posix_mkfifo($path, $mode);
    }

    public static function mknod($filename, $mode = 0666, $device = 0)
    {
        return posix_mknod($filename, $mode);
    }

    public static function major($path, $flags)
    {
        self::_unimplemented();
    }

    public static function minor($path, $flags)
    {
        self::_unimplemented();
    }

    public static function makedev($major, $minor)
    {
        self::_unimplemented();
    }

    public static function mkdir($path, $mode = 0777)
    {
        mkdir($path, $mode, $recursive = false);
    }

    public static function makedirs($path, $mode = 0777)
    {
        mkdir($path, $mode, $recursive = true);
    }

    public static function pathconf($path, $name)
    {
        self::_unimplemented();
    }

    public static function readlink($path)
    {
        return readlink($path);
    }

    public static function remove($path)
    {
        if (!is_file($path)) {
            throw new OSError("Path is not a file. $path");
        }
        try {
            unlink($path);
        } catch (Exception $e) {
            throw new OSError($e->getMessage(), $e->getCode());
        }
    }

    public static function removedirs($path)
    {
        self::_unimplemented();
    }

    public static function rename($src, $dst)
    {
        if (is_dir($dst)) {
            throw new OSError("Destination is a directory.  $dst");
        }
        rename($src, $dst);
    }

    public static function renames($old, $new)
    {
        self::makedirs(dirname($new));
        self::rename($old, $new);
    }

    public static function rmdir($path)
    {
        rmdir($pat);
    }

    public static function stat($path)
    {
        $arr = stat($path);
        if (!$arr) {
            throw new OSError("Path does not exist.  $path");
        }
        $obj = new stdClass();
        foreach ($arr as $key => $v) {
            $attr = 'st_'.$key;
            $obj->$attr = $v;
        }

        return $obj;
    }

    public static function stat_float_times($newvalue = null)
    {
        self::_unimplemented();
    }

    public static function statvfs()
    {
        self::_unimplemented();
    }

    public static function symlink($source, $link_name)
    {
        symlink($source, $link_name);
    }

    public static function tempnam($dir = null, $prefix = '')
    {
        if (!$dir) {
            $dir = sys_get_temp_dir();
        }
        $name = tempnam($dir, $prefix);
        unlink($name);

        return $name;
    }

    public static function tmpnam()
    {
        return self::tempnam();
    }

    public static function unlink($path)
    {
        unlink($path);
    }

    public static function utime($path, $times)
    {
        self::_unimplemented();
    }

    public static function walk($top, $topdown = true, $onerror = null, $followlinks = false)
    {
        self::_unimplemented();
    }

    /**
     * Begin Process Management.
     */
    public static function abort()
    {
        self::_unimplemented();
    }

    public static function execl($path, $arg0, $arg1)
    {
        self::_unimplemented();
    }

    public static function execle($path, $arg0, $arg1, $env)
    {
        self::_unimplemented();
    }

    public static function execlp($file, $arg0, $arg1)
    {
        self::_unimplemented();
    }

    public static function execlpe($file, $arg0, $arg1, $env)
    {
        self::_unimplemented();
    }

    public static function execv($path, $args)
    {
        self::_unimplemented();
    }

    public static function execve($path, $args, $env)
    {
        self::_unimplemented();
    }

    public static function execvp($file, $args)
    {
        self::_unimplemented();
    }

    public static function execvpe($file, $args, $env)
    {
        self::_unimplemented();
    }

    public static function _exit($n)
    {
        exit($n);
    }

    public static function fork()
    {
        return pcntl_fork();
    }

    public static function forkpty()
    {
        self::_unimplemented();
    }

    public static function kill($pid, $sig)
    {
        posix_kill($pid, $sig);
    }

    public static function killpg($pgid, $sig)
    {
        self::_unimplemented();
    }

    public static function nice($increment)
    {
        proc_nice($increment);
    }

    public static function plock($op)
    {
        self::_unimplemented();
    }

    private static function _unimplemented()
    {
        throw new Exception('Unimplemented.  Please consider submitting a patch to py2php project on github.');
    }
}
