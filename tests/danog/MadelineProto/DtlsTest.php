<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use Webrtc\DTLS\RTCCertificate;
use Webrtc\SSL\DTLS\Engine;
use Webrtc\SSL\DTLS\Prf;
use Webrtc\SSL\DTLS\RecordLayer;
use PHPUnit\Framework\TestCase;

/**
 * Tests the pure-PHP DTLS 1.2 implementation used to secure calls.
 *
 * @internal
 */
final class DtlsTest extends TestCase
{
    /**
     * The TLS 1.2 PRF, checked against the well-known SHA-256 test vector.
     */
    public function testPrfVector(): void
    {
        $this->assertSame(
            'e3f229ba727be17b8d122620557cd453c2aab21d07c3d495329b52d4e61edb5a'
            .'6b301791e90d35c9c9a46b4e14baf9af0fa022f7077def17abfd3797c0564bab'
            .'4fbc91666e9def9b97fce34f796789baa48082d122ee42c5a72e5a5110fff701'
            .'87347b66',
            bin2hex(Prf::prf(
                (string) hex2bin('9bbe436ba940f017b17652849a71db35'),
                'test label',
                (string) hex2bin('a0ba9f936cda311827a6f796ffd5198c'),
                100
            ))
        );
    }

    public function testCertificateFingerprintMatchesOpenssl(): void
    {
        $certificate = new RTCCertificate;
        $fingerprint = $certificate->getFingerprints()[0];

        $this->assertSame('sha-256', $fingerprint->algorithm);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}:){31}[0-9A-F]{2}$/', $fingerprint->value);

        if (\function_exists('openssl_x509_fingerprint')) {
            $expected = strtoupper(implode(':', str_split(
                (string) openssl_x509_fingerprint($certificate->getCertificate(), 'sha256'),
                2
            )));
            $this->assertSame($expected, $fingerprint->value);
        }
    }

    public function testRecordLayerRoundTrip(): void
    {
        $tx = new RecordLayer;
        $rx = new RecordLayer;

        $plaintext = $tx->encode(RecordLayer::TYPE_HANDSHAKE, 'unprotected');
        $decoded = $rx->decode($plaintext);
        $this->assertCount(1, $decoded);
        $this->assertSame('unprotected', $decoded[0]['payload']);

        $keyA = random_bytes(16);
        $saltA = random_bytes(4);
        $keyB = random_bytes(16);
        $saltB = random_bytes(4);
        $tx->setKeys($keyA, $saltA, $keyB, $saltB);
        $rx->setKeys($keyB, $saltB, $keyA, $saltA);
        $tx->activateWrite();
        $rx->activateRead();

        $secret = str_repeat('secret', 100);
        $record = $tx->encode(RecordLayer::TYPE_APPLICATION_DATA, $secret);
        $this->assertStringNotContainsString($secret, $record, 'the payload must be encrypted');

        $decoded = $rx->decode($record);
        $this->assertCount(1, $decoded);
        $this->assertSame($secret, $decoded[0]['payload']);

        // Several records may share one datagram.
        $first = $tx->encode(RecordLayer::TYPE_APPLICATION_DATA, 'one');
        $second = $tx->encode(RecordLayer::TYPE_APPLICATION_DATA, 'two');
        $decoded = $rx->decode($first.$second);
        $this->assertSame(['one', 'two'], array_column($decoded, 'payload'));

        // Replays and forgeries are dropped silently, as the RFC requires.
        $this->assertSame([], $rx->decode($first));
        $forged = $tx->encode(RecordLayer::TYPE_APPLICATION_DATA, 'forge me');
        $forged[20] = \chr(\ord($forged[20]) ^ 0xFF);
        $this->assertSame([], $rx->decode($forged));
    }

    /**
     * The AEAD additional data must be built from the record sequence, never from the explicit
     * nonce: peers (OpenSSL among them) choose the nonce freely, and confusing the two produces
     * an implementation that only ever interoperates with itself.
     */
    public function testExplicitNonceDiffersFromRecordSequence(): void
    {
        $layer = new RecordLayer;
        $layer->setKeys(random_bytes(16), random_bytes(4), random_bytes(16), random_bytes(4));
        $layer->activateWrite();

        $record = $layer->encode(RecordLayer::TYPE_APPLICATION_DATA, 'payload');

        // Record layout: type(1) version(2) epoch(2) sequence(6) length(2) explicit_nonce(8) ...
        $sequence = substr($record, 3, 8);
        $explicitNonce = substr($record, 13, 8);

        $this->assertNotSame(
            $sequence,
            $explicitNonce,
            'the explicit nonce must not simply repeat the record sequence'
        );
    }

    /**
     * Run a full mutually authenticated handshake between two engines.
     *
     * @return array{Engine, Engine, RTCCertificate, RTCCertificate}
     */
    private function handshake(): array
    {
        $clientCertificate = new RTCCertificate;
        $serverCertificate = new RTCCertificate;

        $client = new Engine($clientCertificate);
        $client->setConnectState();
        $server = new Engine($serverCertificate);
        $server->setAcceptState();

        $client->startHandshake();
        $server->startHandshake();

        for ($round = 0; $round < 20; $round++) {
            if ($client->isHandshakeComplete() && $server->isHandshakeComplete()) {
                break;
            }
            $toServer = $client->takeOutgoing();
            foreach ($toServer as $datagram) {
                $server->handleDatagram($datagram);
            }
            $toClient = $server->takeOutgoing();
            foreach ($toClient as $datagram) {
                $client->handleDatagram($datagram);
            }
            if ($toServer === [] && $toClient === []) {
                break;
            }
        }

        return [$client, $server, $clientCertificate, $serverCertificate];
    }

    public function testHandshakeCompletes(): void
    {
        [$client, $server] = $this->handshake();

        $this->assertTrue($client->isHandshakeComplete(), 'the client completed the handshake');
        $this->assertTrue($server->isHandshakeComplete(), 'the server completed the handshake');
    }

    /**
     * Each side must end up holding the fingerprint the other advertises over SDP.
     */
    public function testPeerFingerprintsCrossMatch(): void
    {
        [$client, $server, $clientCertificate, $serverCertificate] = $this->handshake();

        $this->assertSame($serverCertificate->getFingerprints()[0]->value, $client->getPeerCertificateDigest());
        $this->assertSame($clientCertificate->getFingerprints()[0]->value, $server->getPeerCertificateDigest());
    }

    /**
     * The whole point of the handshake: both peers must derive identical SRTP keys.
     */
    public function testSrtpKeyingMaterialMatches(): void
    {
        [$client, $server] = $this->handshake();

        $this->assertNotSame('', $client->getSelectedSrtpProfile());
        $this->assertSame($client->getSelectedSrtpProfile(), $server->getSelectedSrtpProfile());

        $clientKeys = $client->exportKeyingMaterial('EXTRACTOR-dtls_srtp', 60);
        $serverKeys = $server->exportKeyingMaterial('EXTRACTOR-dtls_srtp', 60);

        $this->assertSame(60, \strlen($clientKeys));
        $this->assertSame($clientKeys, $serverKeys);
    }

    public function testApplicationDataFlowsBothWays(): void
    {
        [$client, $server] = $this->handshake();

        $client->write('hello from the client');
        foreach ($client->takeOutgoing() as $datagram) {
            $server->handleDatagram($datagram);
        }
        $this->assertSame('hello from the client', $server->read(4096));

        $server->write('hello from the server');
        foreach ($server->takeOutgoing() as $datagram) {
            $client->handleDatagram($datagram);
        }
        $this->assertSame('hello from the server', $client->read(4096));

        $large = str_repeat('A', 4000);
        $client->write($large);
        foreach ($client->takeOutgoing() as $datagram) {
            $server->handleDatagram($datagram);
        }
        $this->assertSame($large, $server->read(100000));
    }
}
