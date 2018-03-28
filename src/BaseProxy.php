<?php

class BaseProxy implements \danog\MadelineProto\Proxy
{
    protected $options = [];
    protected $tls = false;
    /**
     *
     * @var \Socket
     */
    protected $conn;
    
    public function __construct($domain, $type, $protocol)
    {
        $this->conn = $protocol !== PHP_INT_MAX ? new \Socket($domain, $type, getprotobyname('tcp')) : new \FSocket($domain, $type, getprotobyname('tcp'));
        $this->tls = $protocol === PHP_INT_MAX ? true : false;
    }

    public function __destruct()
    {
        $this->conn = null;
    }

    public function accept()
    {
        $this->conn->accept();
    }

    public function bind($address, $port = 0)
    {
        $this->conn->bind($address, $port);
    }

    public function close()
    {
        $this->conn->close();
    }

    protected function changeContextSSL()
    {
        /*
         * Change context to SSL
         */
        if ($this->tls !== true) {
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
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ];
        $socket = $this->conn->getSocket();
        stream_context_set_option($socket, $contextOptions);

        $success = false;
        foreach ($modes as $mode) {
            $success = stream_socket_enable_crypto($socket, true, $mode);
            if ($success) {
                return true;
            }
        }

        return false;
    }

    protected function postConnect() 
    {
        return $this->changeContextSSL();
    }
    /**
     * default: No proxy.
     */
    public function connect($address, $port = 0)
    {
        
        $isConnected = false;
        if (isset($this->options['host']) && isset($this->options['port'])) {
            $isConnected = parent::connect($this->options['host'], $this->options['port']);
        } else {
            $isConnected = parent::connect($address, $port);
        }

        if ($isConnected === false) {
            return false;
        }

        return $this->postConnect();
    }

    public function getOption($level, $name)
    {
        return $this->conn->getOption($level, $name);
    }

    public function getPeerName($port = true)
    {
        return $this->conn->getPeerName($port);
    }

    public function getSockName($port = true)
    {
        return $this->conn->getSockName($port);
    }

    public function listen($backlog = 0)
    {
       return $this->conn->listen($backlog);
    }

    public function read($length, $flags = 0)
    {
        return $this->conn->read($length, $flags);
    }

    public function select(array &$read, array &$write, array &$except, $tv_sec, $tv_usec = 0)
    {
        return $this->conn->select($read, $write, $except, $tv_sec, $tv_usec);
    }

    public function send($data, $length, $flags)
    {
        return $this->conn->send($data, $length, $flags);
    }

    public function setBlocking($blocking)
    {
        return $this->conn->setBlocking($blocking);
    }

    public function setOption($level, $name, $value)
    {
        return $this->conn->setOption($level, $name, $value);
    }

    public function write($buffer, $length = -1)
    {
        return $this->conn->write($buffer, $length);
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
