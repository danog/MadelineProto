<?php
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libpy2php');
require_once ('libpy2php.php');
require_once ('os.php');
class File {
    function __construct($path) {
        $this->_path = $path;
    }
    /**
     * truncates the file and create new with :param bytes.
     * :return number of bytes written
     */
    function write_bytes($bytes) {
        // py2php.fixme "with" unsupported.
        
    }
    /**
     * read the file as bytes. :return b'' on file not exist
     */
    function read_bytes() {
        if (!(new exists($this->_path))) {
            return '';
        }
        // py2php.fixme "with" unsupported.
        
    }
    /**
     * tries to open with os default viewer
     */
    function open() {
        new call((os::name == 'nt') ? 'cmd /c start "" "' . $this->_path . '"' : [platform::startswith('darwin') ? 'open' : 'xdg-open', $this->_path]);
    }
    /**
     * try to remove the file
     */
    function remove() {
        try {
            os::remove($this->_path);
        }
        catch(FileNotFoundError $e) {
        }
    }
}
