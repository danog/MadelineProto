<?php

class CustomHTTPProxy implements \danog\MadelineProto\Proxy
{
    private $sock;
    private $protocol;
    private $timeout = ['sec' => 0, 'usec' => 0];
    private $domain;
    private $type;
    private $options = [];
    private $use_connect = false;
    private $use_ssl = false;

    public function __construct($domain, $type, $protocol)
    {
        $this->domain = $domain;
        $this->type = $type;
        $this->protocol = $protocol === PHP_INT_MAX ? 'tls' : 'tcp';

        if ($protocol === PHP_INT_MAX) { /* https */
            $this->use_connect = $this->use_ssl = true;
        } elseif ($protocol !== PHP_INT_MAX - 1) {  /* http */
            $this->use_connect = true;
        }
    }

    public function __destruct()
    {
        if ($this->sock !== null) {
            fclose($this->sock);
            $this->sock = null;
        }
    }

    public function accept()
    {
        throw new \danog\MadelineProto\Exception('Not supported');
    }

    public function bind($address, $port = 0)
    {
        throw new \danog\MadelineProto\Exception('Not supported');
    }

    public function close()
    {
        fclose($this->sock);
        $this->sock = null;
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
        stream_set_timeout($this->sock, $this->timeout['sec'], $this->timeout['usec']);

        if (isset($this->options['host']) && isset($this->options['port']) &&
                true === $this->use_connect) {
            if ($this->domain === AF_INET6 && strpos($address, ':') !== false) {
                $address = '['.$address.']';
            }
            fwrite($this->sock, 'CONNECT '.$address.':'.$port." HTTP/1.1\r\n".
                    "Accept: */*\r\n".
                    'Host: '.$address.':'.$port."\r\n".
                    $this->getProxyAuthHeader().
                    "connection: keep-Alive\r\n".
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

        if (true === $this->use_ssl) {
            $modes = [
                STREAM_CRYPTO_METHOD_TLS_CLIENT,
                STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
                STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
                STREAM_CRYPTO_METHOD_SSLv2_CLIENT,
            ];

            $contextOptions = [
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ];
            stream_context_set_option($this->sock, $contextOptions);

            $success = false;
            foreach ($modes as $mode) {
                $success = stream_socket_enable_crypto($this->sock, true, $mode);
                if ($success) {
                    return true;
                }
            }

            return false;
        }

        return true;
    }

    public function getOption($level, $name)
    {
        throw new \danog\MadelineProto\Exception('Not supported');
    }

    public function getPeerName($port = true)
    {
        throw new \danog\MadelineProto\Exception('Not supported');
    }

    public function getSockName($port = true)
    {
        throw new \danog\MadelineProto\Exception('Not supported');
    }

    public function listen($backlog = 0)
    {
        throw new \danog\MadelineProto\Exception('Not supported');
    }

    public function read($length, $flags = 0)
    {
        return stream_get_contents($this->sock, $length);
    }

    public function select(array &$read, array &$write, array &$except, $tv_sec, $tv_usec = 0)
    {
        return stream_select($read, $write, $except, $tv_sec, $tv_usec);
    }

    public function send($data, $length, $flags)
    {
        throw new \danog\MadelineProto\Exception('Not supported');
    }

    public function setBlocking($blocking)
    {
        return stream_set_blocking($this->sock, $blocking);
    }

    public function setOption($level, $name, $value)
    {
        if (in_array($name, [\SO_RCVTIMEO, \SO_SNDTIMEO])) {
            $this->timeout = ['sec' => (int) $value, 'usec' => (int) (($value - (int) $value) * 1000000)];

            return true;
        }

        throw new \danog\MadelineProto\Exception('Not supported');
    }

    public function write($buffer, $length = -1)
    {
        return $length === -1 ? fwrite($this->sock, $buffer) : fwrite($this->sock, $buffer, $length);
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
        return ($this->use_connect === true) ? '' : $this->getProxyAuthHeader();
    }

    public function setExtra(array $extra = [])
    {
        $this->options = $extra;
    }
}
