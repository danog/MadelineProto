<?php

/**
 * TON API module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TON;

use Amp\Socket\ConnectContext;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Stream\ADNLTransport\ADNLStream;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\Common\HashedBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\MTProtoTransport\ObfuscatedStream;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\Tools;
use phpseclib3\Crypt\DH;
use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\Curves\Curve25519;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\EC\PublicKey;
use phpseclib3\Math\BigInteger;

class ADNLConnection
{
    /**
     * TL serializer instance.
     *
     * @var TL
     */
    private $TL;
    /**
     * ADNL stream instance.
     *
     * @var StreamInterface
     */
    private $stream;
    /**
     * Construct class.
     *
     * @param TL $TL TL instance
     */
    public function __construct(TL $TL)
    {
        $this->TL = $TL;
    }
    /**
     * Connect to specified ADNL endpoint.
     *
     * @param array $endpoint Endpoint
     *
     * @return \Generator
     */
    public function connect(array $endpoint): \Generator
    {
        if ($endpoint['_'] !== 'liteserver.desc') {
            throw new \InvalidArgumentException('Only liteservers are supported at the moment!');
        }
        if ($endpoint['id']['_'] !== 'pub.ed25519') {
            throw new \InvalidArgumentException('Only ECDH is supported at the moment!');
        }

        $random = Tools::random(256 - 32 - 64);
        $s1 = \substr($random, 0, 32);
        $s2 = \substr($random, 32, 32);
        $v1 = \substr($random, 64, 16);
        $v2 = \substr($random, 80, 16);

        $obf = [
            'decrypt' => [
                'key' => $s1,
                'iv' => $v1
            ],
            'encrypt' => [
                'key' => $s2,
                'iv' => $v2
            ],
        ];
        // Generating new private/public params
        $private = EC::createKey('Ed25519');

        $public = $private->getPublicKey();
        $public = strrev(Tools::getVar($public, 'QA')[1]->toBytes());

        $private = strrev(Tools::getVar($private, 'dA')->toBytes());
        $private = PrivateKey::loadFormat('MontgomeryPrivate', $private);

        // Transpose their public
        $key = $endpoint['id']['key'];
        $key[31] = $key[31] & chr(127);

        $curve = new Curve25519;
        $modulo = Tools::getVar($curve, "modulo");
        $y = new BigInteger(strrev($key), 256);
        $y2 = clone $y;
        $y = $y->add(Magic::$one);
        $y2 = $y2->subtract(Magic::$one);
        $y2 = $modulo->subtract($y2)->powMod(Magic::$one, $modulo);
        $y2 = $y2->modInverse($modulo);

        $key = strrev($y->multiply($y2)->powMod(Magic::$one, $modulo)->toBytes());
        $peerPublic = PublicKey::loadFormat('MontgomeryPublic', $key);

        // Generate secret
        $secret = DH::computeSecret($private, $peerPublic);

        var_dumP($private, $peerPublic, bin2hex($secret));

        // Encrypting random with obf keys
        $digest = \hash('sha256', $random, true);

        $key = \substr($secret, 0, 16).\substr($digest, 16, 16);
        $iv = \substr($digest, 0, 4).\substr($secret, 20, 12);

        $encryptedRandom = Crypt::ctrEncrypt($random, $key, $iv);

        // Generating plaintext init payload
        $payload = \hash('sha256', yield $this->TL->serializeObject(['type' => ''], $endpoint['id'], 'key'), true);
        $payload .= $public;
        $payload .= $digest;
        $payload .= $encryptedRandom;

        \var_dump(bin2hex($payload));

        $ip = \long2ip(\unpack('V', Tools::packSignedInt($endpoint['ip']))[1]);
        $port = $endpoint['port'];
        $ctx = (new ConnectionContext())
            ->setSocketContext(new ConnectContext)
            ->setUri("tcp://$ip:$port")
            ->addStream(DefaultStream::getName())
            ->addStream(BufferedRawStream::getName())
            ->addStream(ObfuscatedStream::getName(), $obf)
            ->addStream(HashedBufferedStream::getName(), 'sha256')
            ->addStream(ADNLStream::getName());

        $this->stream = yield $ctx->getStream($payload);
        \var_dump("Connected");

        Tools::callFork((function () {
            yield Tools::sleep(2);
            while (true) {
                $buffer = yield $this->stream->getReadBuffer($length);
                \var_dump($length, "GOT PACKET WITH LENGTH $length");
                \var_dump($length, yield $buffer->bufferRead($length));
            }
        })());
    }

    /**
     * Send TL payload.
     *
     * @param array $payload Payload to send
     *
     * @return \Generator
     */
    public function send(array $payload): \Generator
    {
        $data = yield $this->TL->serializeMethod($payload['_'], $payload);
        (yield $this->stream->getWriteBuffer(\strlen($data)))->bufferWrite($data);
    }
}
