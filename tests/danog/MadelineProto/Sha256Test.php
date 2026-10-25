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
 * @author    Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Test;

use danog\MadelineProto\MTProtoTools\Crypt;
use PHPUnit\Framework\TestCase;

final class Sha256Test extends TestCase
{
    public function testKnownDigests(): void
    {
        self::assertSame(hex2bin('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855'), Crypt::sha256(''));
        self::assertSame(hex2bin('ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad'), Crypt::sha256('abc'));
    }

    public function testBinaryPayloadsMatchSha256(): void
    {
        $prefix = random_bytes(32);
        foreach ([1, 15, 16, 17, 4096, 1048576] as $size) {
            $message = $prefix.random_bytes($size);
            $expected = hash('sha256', $message, true);
            $actual = Crypt::sha256($message);
            self::assertSame($expected, $actual);
        }
    }
}
