<?php

class OSError extends Exception{
}


class os {
    
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
    
    static public function ctermid() {
        return posix_ctermid();
    }

    static public function getegid() {
        return posix_getegid();
    }

    static public function geteuid() {
        return posix_geteuid();
    }
    
    static public function getgid() {
        return posix_getgid();
    }

    static public function getgroups() {
        return posix_getgroups();
    }
    
    static public function initgroups($username, $gid) {
        return posix_initgroups($username, $gid);
    }
    
    static public function getlogin() {
        return posix_getlogin();
    }
    
    static public function getpgid() {
        return posix_getpgid();
    }
    
    static public function getpgrp() {
        return posix_getpgrp();
    }
    
    static public function getpid() {
        return posix_getpid();
    }
    
    static public function getresuid() {
        self::_unimplemented();
    }
    
    static public function getresgid() {
        self::_unimplemented();
    }
    
    static public function getuid() {
        return posix_getuid();
    }
    
    static public function getenv($varname, $value=null) {
        return getenv($varname, $value);
    }
    
    static public function putenv($varname, $value) {
        putenv("$varname=$value");
    }

    static public function setegid($egid) {
        posix_setegid($egid);
    }

    static public function seteuid($euid) {
        posix_seteuid($euid);
    }
    
    static public function setgid($gid) {
        posix_setgid($gid);
    }

    static public function setgroups($groups) {
        self::_unimplemented();
    }
    
    static public function setpgrp() {
        self::_unimplemented();
    }
    
    static public function setpgid($pid, $pgrp) {
        posix_setpgid($pid, $pgrp);
    }
    
    static public function setregid($rgid, $egid) {
        self::_unimplemented();
    }

    static public function setresgid($rgid, $egid, $sgid) {
        self::_unimplemented();
    }
    
    static public function setresuid($ruid, $euid, $suid) {
        self::_unimplemented();
    }

    static public function setreuid($ruid, $euid) {
        self::_unimplemented();
    }
    
    static public function getsid($pid) {
        return posix_getsid();
    }
    
    static public function setsid() {
        posix_setsid();
    }
    
    static public function setuid($uid) {
        posix_setuid($uid);
    }
    
    static public function strerror($code) {
        self::_unimplemented();
    }
    
    static public function umask($mask) {
        umask($mask);
    }
    
    static public function uname() {
        return posix_uname();
    }
    
    static public function unsetenv($varname) {
        unset($_ENV[$varname]);
    }
    
    static public function fdopen($fd, $mode=null, $bufsize=null) {
        return new pyjslib_file($fd);
    }
    
    static public function popen($command, $mode=null, $bufsize=null) {
        self::_unimplemented();
    }
    
    static public function tmpfile() {
        return tmpfile();
    }
    
    static public function popen2($cmd, $mode=null, $bufsize=null) {
        self::_unimplemented();
    }
    
    static public function popen3($cmd, $mode=null, $bufsize=null) {
        self::_unimplemented();
    }

    static public function popen4($cmd, $mode=null, $bufsize=null) {
        self::_unimplemented();
    }
    
    static public function close($fd) {
        fclose( $fd );
    }

    static public function closerange($fd_low, $fd_high) {
        self::_unimplemented();
    }

    static public function dup($fd) {
        self::_unimplemented();
    }

    static public function dup2($fd) {
        self::_unimplemented();
    }

    static public function fchmod($fd, $mode) {
        self::_unimplemented();
    }

    static public function fchown($fd, $uid, $gid) {
        self::_unimplemented();
    }

    static public function fdatasync($fd) {
        self::_unimplemented();
    }

    static public function fpathconf($fd, $name) {
        self::_unimplemented();
    }

    static public function fstat($fd) {
        $info = fstat($fd);
        $obj = new stdClass;
        foreach($arr as $key => $v) {
            $attr = "st_" . $key;
            $obj->$attr = $v;
        }
        return $obj;
    }
    
    static public function fstatvfs($fd) {
        self::_unimplemented();
    }

    static public function fsync($fd) {
        fsync($fd);
    }
    
    static public function ftruncate($fd, $length) {
        ftruncate($fd, $length);
    }
    
    static public function isatty($fd) {
        return posix_isatty( $fd );
    }
    
    static public function lseek($fd, $pos, $how) {
        lseek($fd, $pos, $how);
    }
    
    static public function open($file, $flags, $mode=0777) {
        // todo: define and map flag constants.  See list at:
        // https://docs.python.org/2/library/os.html#open-flag-constants
        
        $fl = '';
        if( $flags & self::O_RDONLY ) {
            $fl .= 'r';
        }
        if( $flags & self::O_WRONLY ) {
            $fl .= 'w';
        }
        if( $flags & self::O_RDWR ) {
            $fl .= 'rw';
        }
        if( $flags & self::O_APPEND ) {
            $fl .= 'a';
        }
        if( $flags & self::O_CREAT ) {
            $fl .= 'c';
        }
        if( $flags & self::O_EXCL ) {
            $fl .= 'x';
        }
        if( $flags & self::O_TRUNC ) {
            $fl .= 'w';
        }
        
        return fopen($file, $fl, false );
    }
    
    static public function pipe() {
        self::_unimplemented();
    }
    
    static public function read($fd, $n) {
        return fread( $fd, $n );
    }
    
    static public function tcgetpgrp($fd) {
        self::_unimplemented();
    }
    
    static public function tcsetpgrp($fd, $pg) {
        self::_unimplemented();
    }

    static public function ttyname($fd) {
        return posix_ttyname($fd);
    }

    static public function write($fd, $str) {
        return fwrite( $fd, $str );
    }

    static function access($path, $mode) {
        return posix_access($path, $mode);
    }
    
    static function chdir($path) {
        chdir( $path );
    }

    static function fchdir($path) {
        fchdir( $path );
    }

    static function getcwd() {
        return getcwd();
    }
    
    static function getcwdu() {
        return getcwd();
    }
    
    static function chflags($path, $flags) {
        self::_unimplemented();
    }

    static function chroot($path) {
        chroot($path);
    }

    static function chmode($path, $mode) {
        self::_unimplemented();
    }
    
    static function chown($path, $uid, $gid) {
        chown($path, $uid, $gid);
    }

    static function lchflags($path, $flags) {
        self::_unimplemented();
    }
    
    static function lchmod($path, $mode) {
        self::_unimplemented();
    }

    static function lchown($path, $uid, $gid) {
        self::_unimplemented();
    }

    static function link($source, $link_name) {
        link($source, $link_name);
    }

    static function listdir($path) {
        self::_unimplemented();
    }
    
    static function lstat($path) {
        self::_unimplemented();
    }

    static function mkfifo($path, $mode=0666) {
        posix_mkfifo($path, $mode);
    }
    
    static function mknod($filename, $mode=0666, $device=0) {
        return posix_mknod( $filename, $mode );
    }

    static function major($path, $flags) {
        self::_unimplemented();
    }

    static function minor($path, $flags) {
        self::_unimplemented();
    }

    static function makedev($major, $minor) {
        self::_unimplemented();
    }

    static function mkdir($path, $mode=0777) {
        mkdir($path, $mode, $recursive=false);
    }
    
    static function makedirs($path, $mode=0777) {
        mkdir($path, $mode, $recursive=true);
    }
    
    static function pathconf($path, $name) {
        self::_unimplemented();
    }
    
    static function readlink($path) {
        return readlink($path);
    }
    
    static function remove($path) {
        if( !is_file( $path ) ) {
            throw new OSError("Path is not a file. $path");
        }
        try {
            unlink( $path );
        }
        catch( Exception $e ) {
            throw new OSError( $e->getMessage(), $e->getCode() );
        }
    }
    
    static function removedirs($path) {
        self::_unimplemented();
    }
    
    static function rename($src, $dst) {
        if( is_dir($dst)) {
            throw new OSError("Destination is a directory.  $dst");
        }
        rename($src, $dst);
    }
    
    static function renames($old, $new) {
        self::makedirs( dirname($new) );
        self::rename($old, $new);
    }
    
    static function rmdir($path) {
        rmdir($pat);
    }
    
    static function stat($path) {
        $arr = stat($path);
        if(!$arr) {
            throw new OSError("Path does not exist.  $path");
        }
        $obj = new stdClass;
        foreach($arr as $key => $v) {
            $attr = "st_" . $key;
            $obj->$attr = $v;
        }
        return $obj;
    }
    
    static function stat_float_times($newvalue=null) {
        self::_unimplemented();
    }
    
    static function statvfs() {
        self::_unimplemented();
    }
    
    static function symlink($source, $link_name) {
        symlink($source, $link_name);
    }
    
    static function tempnam($dir=null, $prefix='') {
        if( !$dir ) {
            $dir = sys_get_temp_dir() ;
        }
        $name = tempnam($dir, $prefix);
        unlink($name);
        return $name;
    }
    
    static function tmpnam() {
        return self::tempnam();
    }
    
    static function unlink($path) {
        unlink($path);
    }
    
    static function utime($path, $times) {
        self::_unimplemented();
    }
    
    static function walk($top, $topdown=true, $onerror=null, $followlinks=false) {
        self::_unimplemented();
    }
    
    /**
     * Begin Process Management
     */

    static function abort() {
        self::_unimplemented();
    }
    
    static function execl($path, $arg0, $arg1) {
        self::_unimplemented();
    }

    static function execle($path, $arg0, $arg1, $env) {
        self::_unimplemented();
    }

    static function execlp($file, $arg0, $arg1) {
        self::_unimplemented();
    }

    static function execlpe($file, $arg0, $arg1, $env) {
        self::_unimplemented();
    }

    static function execv($path, $args) {
        self::_unimplemented();
    }

    static function execve($path, $args, $env) {
        self::_unimplemented();
    }

    static function execvp($file, $args) {
        self::_unimplemented();
    }

    static function execvpe($file, $args, $env) {
        self::_unimplemented();
    }

    static function _exit($n) {
        exit($n);
    }
    
    static function fork() {
        return pcntl_fork();
    }
    
    static function forkpty() {
        self::_unimplemented();
    }
    
    static function kill($pid, $sig) {
        posix_kill($pid, $sig);
    }
    
    static function killpg($pgid, $sig) {
        self::_unimplemented();
    }
    
    static function nice($increment) {
        proc_nice($increment);
    }
    
    static function plock($op) {
        self::_unimplemented();
    }


    
    private static function _unimplemented() {
        throw new Exception( "Unimplemented.  Please consider submitting a patch to py2php project on github.");
    }
}

