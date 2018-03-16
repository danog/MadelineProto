<?php

class BaseProxy implements \danog\MadelineProto\Proxy
{

    protected $sock;
    protected $protocol;
    protected $timeout = ['sec' => 5, 'usec' => 5000000];
    protected $domain;
    protected $type;
    protected $options = [];

    public function __construct($domain, $type, $protocol)
    {
        $this->domain = $domain;
        $this->type = $type;
        $this->protocol = $protocol === PHP_INT_MAX ? 'tls' : 'tcp';
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

    protected function changeContextSSL()
    {
        /*
         * Change context to SSL
         */
        if ($this->protocol !== 'tls') {
            return true;
        }
        $modes = [
            STREAM_CRYPTO_METHOD_TLS_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv3_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv23_CLIENT,
            STREAM_CRYPTO_METHOD_SSLv2_CLIENT,
        ];

        $contextOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ];
        stream_context_set_option($this->sock, $contextOptions);

        $success = false;
        foreach ($modes as $mode)
        {
            $success = stream_socket_enable_crypto($this->sock, true, $mode);
            if ($success) {
                return true;
            }
        }

        return false;
    }

    /**
     * default: No proxy
     */
    public function connect($address, $port = 0)
    {
        $errno = 0;
        $errstr = '';

        $this->sock = @fsockopen($address, $port, $errno, $errstr, $this->timeout['sec'] + ($this->timeout['usec'] / 1000000));

        if ($this->sock === FALSE) {
            return false;
        }

        stream_set_timeout($this->sock, $this->timeout['sec'], $this->timeout['usec']);
        return $this->changeContextSSL();
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

    public function getProxyHeaders()
    {
        return '';
    }

    public function setExtra(array $extra = [])
    {
        $this->options = $extra;
    }

}
