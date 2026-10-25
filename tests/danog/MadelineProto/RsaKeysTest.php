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
 * The RSA keys come from the settings, not from the session: a session saved before rsa_keys was renamed to rsaKeys
 * (8.6.2) woke up without keys, and kept failing the handshakes ("Couldn't find any of our keys in the
 * server_public_key_fingerprints vector") until a version or settings change loaded them again.
 *
 * @internal
 */
final class RsaKeysTest extends TestCase
{
    /** Fingerprint of Telegram's production key, as sent in resPQ. */
    private const PRODUCTION_FINGERPRINT = -3414540481677951611;

    public function testTheKeysAreLoadedFromTheSettingsNotFromTheSession(): void
    {
        $API = (new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty($API, 'settings'))->setValue($API, $settings = new Settings);
        $TL = new TL($API);
        $TL->init($settings->getSchema());
        (new ReflectionProperty($API, 'TL'))->setValue($API, $TL);

        $this->assertNotContains('rsaKeys', $API->__sleep());
        $this->assertNotContains('testRsaKeys', $API->__sleep());

        // As woken up from such a session.
        $fps = [pack('q', self::PRODUCTION_FINGERPRINT)];
        $this->assertNull($API->findRsaKey($fps, false, false));

        (new ReflectionMethod($API, 'loadRsaKeys'))->invoke($API);
        $this->assertNotNull($API->findRsaKey($fps, false, false));
        $this->assertNull($API->findRsaKey($fps, true, false));
    }
}
