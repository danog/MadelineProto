<?php

declare(strict_types=1);

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

use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings;
use danog\MadelineProto\TL\TL;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * A session saved before rsa_keys was renamed to rsaKeys (8.6.2) woke up without keys, and kept failing the handshakes
 * ("Couldn't find any of our keys in the server_public_key_fingerprints vector") until a version or settings change
 * parsed them again: they're checked against the settings on each wakeup, and parsed again only if they differ.
 *
 * @internal
 */
final class RsaKeysTest extends TestCase
{
    /** Fingerprint of Telegram's production key, as sent in resPQ. */
    private const PRODUCTION_FINGERPRINT = -3414540481677951611;

    private function api(Settings $settings): MTProto
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty($API, 'settings'))->setValue($API, $settings);
        $TL = new TL($API);
        $TL->init($settings->getSchema());
        (new ReflectionProperty($API, 'TL'))->setValue($API, $TL);
        return $API;
    }

    private static function load(MTProto $API): void
    {
        (new ReflectionMethod($API, 'loadRsaKeys'))->invoke($API);
    }

    public function testASessionWithoutKeysGetsThem(): void
    {
        // As woken up from such a session.
        $API = $this->api(new Settings);
        $fps = [pack('q', self::PRODUCTION_FINGERPRINT)];
        $this->assertNull($API->findRsaKey($fps, false, false));

        self::load($API);
        $this->assertNotNull($API->findRsaKey($fps, false, false));
        $this->assertNull($API->findRsaKey($fps, true, false));

        // Saved with what they were parsed from.
        foreach (['rsaKeys', 'testRsaKeys', 'rsaKeysSource'] as $property) {
            $this->assertContains($property, $API->__sleep());
        }
    }

    public function testTheKeysAreOnlyParsedAgainWhenTheSettingsChange(): void
    {
        $API = $this->api($settings = new Settings);
        $fps = [pack('q', self::PRODUCTION_FINGERPRINT)];
        self::load($API);
        $key = $API->findRsaKey($fps, false, false);

        self::load($API);
        $this->assertSame($key, $API->findRsaKey($fps, false, false));

        $settings->getConnection()->setRsaKeys([]);
        self::load($API);
        $this->assertNull($API->findRsaKey($fps, false, false));
    }
}
