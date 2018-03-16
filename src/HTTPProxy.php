<?php

class HTTPProxy extends \BaseProxy
{

    private $use_connect = true;

    public function __construct($domain, $type, $protocol)
    {
        parent::__construct($domain, $type, $protocol);

        if ($protocol === PHP_INT_MAX - 1) { /* http */
            $this->use_connect = false;
        }
    }

    public function connect($address, $port = 0)
    {
        $errno = 0;
        $errstr = '';

        if (isset($this->options['host']) && isset($this->options['port'])) {
            $this->sock = @fsockopen($this->options['host'], $this->options['port'], $errno, $errstr, $this->timeout['sec'] + ($this->timeout['usec'] / 1000000));
        } else {
            $this->sock = @fsockopen($address, $port, $errno, $errstr, $this->timeout['sec'] + ($this->timeout['usec'] / 1000000));
        }

        if ($this->sock === FALSE) {
            return false;
        }

        stream_set_timeout($this->sock, $this->timeout['sec'], $this->timeout['usec']);

        if (isset($this->options['host']) && isset($this->options['port']) &&
                true === $this->use_connect) {
            if ($this->domain === AF_INET6 && strpos($address, ':') !== false) {
                $address = '[' . $address . ']';
            }
            fwrite($this->sock, 'CONNECT ' . $address . ':' . $port . " HTTP/1.1\r\n" .
                    "Accept: */*\r\n" .
                    'Host: ' . $address . ':' . $port . "\r\n" .
                    $this->getProxyAuthHeader() .
                    "connection: keep-Alive\r\n" .
                    "\r\n");

            $response = '';
            $status = false;
            while ($line = @fgets($this->sock)) {
                $status = $status || (strpos($line, 'HTTP') !== false);
                if ($status) {
                    $response .= $line;
                    if (!rtrim($line)) {
                        break;
                    }
                }
            }
            if (substr($response, 0, 13) !== 'HTTP/1.1 200 ') {
                return false;
            }
        }
        return $this->changeContextSSL();
    }

    private function getProxyAuthHeader()
    {
        if (!isset($this->options['user']) || !isset($this->options['pass'])) {
            return '';
        }

        return 'Proxy-Authorization: Basic ' . base64_encode($this->options['user'] . ':' . $this->options['pass']) . "\r\n";
    }

    public function getProxyHeaders()
    {
        return ($this->use_connect === true) ? '' : $this->getProxyAuthHeader();
    }

}
