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

use Amp\Deferred;
use Amp\Socket\ConnectContext;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Stream\ADNLTransport\ADNLStream;
use danog\MadelineProto\Stream\Common\BufferedRawStream;
use danog\MadelineProto\Stream\Common\CtrStream;
use danog\MadelineProto\Stream\Common\HashedBufferedStream;
use danog\MadelineProto\Stream\ConnectionContext;
use danog\MadelineProto\Stream\Transport\DefaultStream;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\Tools;
use Exception;
use tgseclib\Crypt\DH;
use tgseclib\Crypt\EC;
use tgseclib\Crypt\EC\Curves\Curve25519;
use tgseclib\Crypt\EC\PrivateKey;
use tgseclib\Crypt\EC\PublicKey;
use tgseclib\Math\BigInteger;

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
     * Request list.
     *
     * @var array
     */
    private $requests = [];
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
        //$random = strrev(hex2bin(strrev('9E7C27765D12CE634414F0875D55CE5C58E7A9D58CD45C57CAB516D1241B7864691E5B0AFC4ECB54BFF2CEFC2060F1D45F5B5DEB76A9EF6471D75816AAAEC83CD7DE39EE99B9E980B6C0D4565A916D00908613E63657D5539118F89A14FD73ABB8ECD3AC26C287EEBD0FA44F52B315F01DD60F486EFF4C5B4D71EA6F443358FF141E7294BBBB5D7C079F16BD46C28A12507E1948722E7121B94C3B5C7832ADE7')));
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
        $public = \strrev(Tools::getVar($public, 'QA')[1]->toBytes());

        $private = \strrev(Tools::getVar($private, 'dA')->toBytes());
        $private = PrivateKey::loadFormat('MontgomeryPrivate', $private);

        // Transpose their public
        $key = $endpoint['id']['key'];
        $key[31] = $key[31] & \chr(127);

        $curve = new Curve25519;
        $modulo = Tools::getVar($curve, "modulo");
        $y = new BigInteger(\strrev($key), 256);
        $y2 = clone $y;
        $y = $y->add(Magic::$one);
        $y2 = $y2->subtract(Magic::$one);
        $y2 = $modulo->subtract($y2)->powMod(Magic::$one, $modulo);
        $y2 = $y2->modInverse($modulo);

        $key = \strrev($y->multiply($y2)->powMod(Magic::$one, $modulo)->toBytes());
        $peerPublic = PublicKey::loadFormat('MontgomeryPublic', $key);

        // Generate secret
        $secret = DH::computeSecret($private, $peerPublic);

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

        $ip = \long2ip(\unpack('V', Tools::packSignedInt($endpoint['ip']))[1]);
        $port = $endpoint['port'];
        $ctx = (new ConnectionContext())
            ->setSocketContext(new ConnectContext)
            ->setUri("tcp://$ip:$port")
            ->addStream(DefaultStream::getName())
            ->addStream(BufferedRawStream::getName())
            ->addStream(CtrStream::getName(), $obf)
            ->addStream(HashedBufferedStream::getName(), 'sha256')
            ->addStream(ADNLStream::getName());

        $this->stream = yield $ctx->getStream($payload);

        Tools::callFork((function () {
            //yield Tools::sleep(1);
            while (true) {
                $buffer = yield $this->stream->getReadBuffer($length);
                if ($length) {
                    $data = yield $buffer->bufferRead($length);
                    $data = yield $this->TL->deserialize($data);
                    if ($data['_'] !== 'adnl.message.answer') {
                        throw new Exception('Wrong answer type: '.$data['_']);
                    }
                    $this->requests[$data['query_id']]->resolve(yield $this->TL->deserialize((string) $data['answer']));
                }
            }
        })());
    }

    /**
     * Send ADNL query.
     *
     * @param string $payload Payload to send
     *
     * @return \Generator
     */
    public function query(string $payload): \Generator
    {
        $data = yield $this->TL->serializeObject(
            ['type' => ''],
            [
                '_' => 'adnl.message.query',
                'query_id' => $id = Tools::random(32),
                'query' => $payload
            ],
            ''
        );
        (yield $this->stream->getWriteBuffer(\strlen($data)))->bufferWrite($data);
        $this->requests[$id] = new Deferred;

        return $this->requests[$id]->promise();
    }
}
