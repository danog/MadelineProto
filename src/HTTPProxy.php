<?php

class HTTPProxy extends \BaseProxy
{
    private $useConnect = true;
    private $domain;
    
    public function __construct($domain, $type, $protocol)
    {
        parent::__construct($domain, $type, $protocol);
        $this->domain = $domain;

        if ($protocol === PHP_INT_MAX - 1) { /* http */
            $this->useConnect = false;
        }
    }

    protected function postConnect($address, $port) 
    {        
        if (isset($this->options['host']) && isset($this->options['port']) &&
                true === $this->useConnect) {
            if ($this->domain === AF_INET6 && strpos($address, ':') !== false) {
                $address = '['.$address.']';
            }
            $this->write('CONNECT '.$address.':'.$port." HTTP/1.1\r\n".
                    "Accept: */*\r\n".
                    'Host: '.$address.':'.$port."\r\n".
                    $this->getProxyAuthHeader().
                    "connection: keep-Alive\r\n".
                    "\r\n");

            $response = '';
            $status = false;
            
             while (true) {
                $line = '';
                while (($char = $this->conn->read(1)) !== "\n") {
                    $line .= $char;
                }
                $response .= $line . '\n';
                if (!rtrim($line)) {
                        break;
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

        return 'Proxy-Authorization: Basic '.base64_encode($this->options['user'].':'.$this->options['pass'])."\r\n";
    }

    public function getProxyHeaders()
    {
        return ($this->useConnect === true) ? '' : $this->getProxyAuthHeader();
    }
}
