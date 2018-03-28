<?php

/**
 * Mostly based on clue/php-socks.
 */
class SOCKSProxy extends \BaseProxy
{
    private function readBinary($what)
    {
        $length = 0;
        $unpack = '';
        foreach ($what as $code => $format) {
            if ($length !== 0) {
                $unpack .= '/';
            }
            $unpack .= $format.$code;
            if ($format === 'C') {
                $length++;
            } elseif ($format === 'n') {
                $length += 2;
            } elseif ($format === 'N') {
                $length += 4;
            } else {
                throw new \danog\MadelineProto\Exception('Invalid SOCKS format');
            }
        }

        return unpack($unpack, $this->read($length));
    }

    private function connectSOCKS5($host, $port)
    {
        $data = pack('C', 0x05);
        $auth = isset($this->options['user']) && isset($this->options['pass']) ?
                $auth = pack('C2', 0x01, strlen($this->options['user'])).$this->options['user'].pack('C', strlen($this->options['pass'])).$this->options['pass'] : null;
        if (null === $auth) {
            // one method, no authentication
            $data .= pack('C2', 0x01, 0x00);
        } else {
            // two methods, username/password and no authentication
            $data .= pack('C3', 0x02, 0x02, 0x00);
        }
        $this->write($data);
        $data = $this->readBinary([
            'version' => 'C',
            'method'  => 'C',
        ]);
        if ($data['version'] !== 0x05) {
            return false;
            // throw new \danog\MadelineProto\Exception('Version/Protocol mismatch');
        }

        if ($data['method'] === 0x02 && $auth !== null) {
            // username/password authentication requested and provided
            $this->write($auth);
            $data = $this->readBinary([
                'version' => 'C',
                'status'  => 'C',
            ]);

            if ($data['version'] !== 0x01 || $data['status'] !== 0x00) {
                return false;
                // throw new \danog\MadelineProto\Exception('Username/Password authentication failed');
            }
        } elseif ($data['method'] !== 0x00) {
            // any other method than "no authentication"
            return false;
            // throw new \danog\MadelineProto\Exception('Unacceptable authentication method requested');
        }
        // Auth OK
        $ip = @inet_pton($host);
        $data = pack('C3', 0x05, 0x01, 0x00);
        if ($ip === false) {
            // not an IP, send as hostname
            $data .= pack('C2', 0x03, strlen($host)).$host;
        } else {
            // send as IPv4 / IPv6
            $data .= pack('C', (strpos($host, ':') === false) ? 0x01 : 0x04).$ip;
        }
        $data .= pack('n', $port);
        $this->write($data);
        $data = $this->readBinary([
            'version' => 'C',
            'status'  => 'C',
            'null'    => 'C',
            'type'    => 'C',
        ]);
        if ($data['version'] !== 0x05 || $data['status'] !== 0x00 || $data['null'] !== 0x00) {
            return false;
            // throw new \danog\MadelineProto\Exception('Invalid SOCKS response');
        }
        if ($data['type'] === 0x01) {
            // IPv4 address => skip IP and port
            $data = $this->read(6);
        } elseif ($data['type'] === 0x03) {
            // domain name => read domain name length
            $data = $this->readBinary([
                'length' => 'C',
            ]);
            // skip domain name and port
            $data = $this->read($data['length'] + 2);
        } elseif ($data['type'] === 0x04) {
            // IPv6 address => skip IP and port
            $data = $this->read(18);
        } else {
            return false;
            // throw new Exception('Invalid SOCKS reponse: Invalid address type');
        }

        return true;
    }

    private function connectSOCKS4($host, $port)
    {
        $ip = ip2long($host);
        $data = pack('C2nNC', 0x04, 0x01, $port, $ip === false ? 1 : $ip, 0x00);
        if ($ip === false) {
            $data .= $host.pack('C', 0x00);
        }
        $this->write($data);

        $data = $this->readBinary([
            'null'   => 'C',
            'status' => 'C',
            'port'   => 'n',
            'ip'     => 'N',
        ]);

        if ($data['null'] !== 0x00 || $data['status'] !== 0x5a) {
            return false;
            // throw new \danog\MadelineProto\Exception('Invalid SOCKS response');
        }

        return true;
    }

//    public function connect($address, $port = 0)
    protected function postConnect()
    {        
        /* Use protocol 5 only if needed */
        if (isset($this->options['host']) && isset($this->options['port'])) {
            $connected = ((!isset($this->options['version']) || $this->options['version'] !== 5) &&
                    (!isset($this->options['user']) || !isset($this->options['pass']))) ? $this->connectSOCKS4($address, $port) : $this->connectSOCKS5($address, $port);
            if (false === $connected) {
                return false;
            }
        }

        return $this->changeContextSSL();
    }
}
