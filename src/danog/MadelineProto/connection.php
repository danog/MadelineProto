<?php

namespace danog\MadelineProto;

/**
 * Manages connection to telegram servers.
 */
class connection
{
    private $sock = null;
    private $protocol = null;

    public function __construct($ip, $port, $protocol = 'tcp')
    {
        switch ($protocol) {
            case 'tcp':
                $this->sock = fsockopen('tcp://'.$ip.':'.$port);
                $this->protocol = 'tcp';
                stream_set_timeout($this->sock, 5);
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function __destruct()
    {
        switch ($this->protocol) {
            case 'tcp':
                fclose($this->sock);
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function write($what, $length = null)
    {
        $what = substr($what, 0, $length);
        switch ($this->protocol) {
            case 'tcp':
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }

                return fwrite($this->sock, $what);
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }

    public function read($length)
    {
        switch ($this->protocol) {
            case 'tcp':
                if (!(get_resource_type($this->sock) == 'file' || get_resource_type($this->sock) == 'stream')) {
                    throw new Exception("Connection: couldn't connect to socket.");
                }

                return fread($this->sock, $length);
                break;
            default:
                throw new Exception('Connection: invalid protocol specified.');
                break;
        }
    }
}
