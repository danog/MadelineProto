<?php

set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).DIRECTORY_SEPARATOR.'libpy2php');
require_once 'libpy2php.php';
require_once 'os.php';
class file
{
    public function __construct($path)
    {
        $this->_path = $path;
    }

    /**
     * truncates the file and create new with :param bytes.
     * :return number of bytes written.
     */
    public function write_bytes($bytes)
    {
        // py2php.fixme "with" unsupported.
    }

    /**
     * read the file as bytes. :return b'' on file not exist.
     */
    public function read_bytes()
    {
        if (!(new exists($this->_path))) {
            return '';
        }
        // py2php.fixme "with" unsupported.
    }

    /**
     * tries to open with os default viewer.
     */
    public function open()
    {
        new call((os::name == 'nt') ? 'cmd /c start "" "'.$this->_path.'"' : [platform::startswith('darwin') ? 'open' : 'xdg-open', $this->_path]);
    }

    /**
     * try to remove the file.
     */
    public function remove()
    {
        try {
            os::remove($this->_path);
        } catch (FileNotFoundError $e) {
        }
    }
}
