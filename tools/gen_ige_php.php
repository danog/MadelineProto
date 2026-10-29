<?php

declare(strict_types=1);

/**
 * Generates src/MTProtoTools/Crypt/IGEPhp.php, the pure PHP AES-256-IGE backend.
 *
 * The AES rounds are unrolled on 32-bit words with T-tables. The source does not depend on the key
 * (round keys are loaded into locals from an array), so it is a normal file that opcache caches and
 * the JIT compiles: no eval, no per-key code generation.
 *
 * IGEPhpTest checks that the committed file matches this generator's output.
 */

/**
 * Unrolled AES-256 rounds of one block. The input block is $p0-$p3 (encryption) or $c0-$c3
 * (decryption), XORed with the chaining value $q0-$q3; the output is $o0-$o3.
 */
function rounds(bool $decrypt, string $indent): string
{
    $code = '';
    $key = 3;
    $input = $decrypt ? 'c' : 'p';
    for ($i = 0; $i < 4; $i++) {
        $code .= "$indent\$s$i = \$$input$i ^ \$q$i ^ \$k".(++$key).";\n";
    }
    [$from, $to] = ['s', 'e'];
    for ($round = 1; $round < 14; $round++) {
        for ($i = 0; $i < 4; $i++) {
            [$i1, $i2, $i3] = columns($decrypt, $i);
            $code .= "$indent\$$to$i = \$t0[\$$from$i >> 24] ^ \$t1[(\$$from$i1 >> 16) & 0xff] ^ \$t2[(\$$from$i2 >> 8) & 0xff] ^ \$t3[\$$from$i3 & 0xff] ^ \$k".(++$key).";\n";
        }
        [$from, $to] = [$to, $from];
    }
    for ($i = 0; $i < 4; $i++) {
        [$i1, $i2, $i3] = columns($decrypt, $i);
        $code .= "$indent\$o$i = (\$sbox[\$$from$i >> 24] << 24) ^ (\$sbox[(\$$from$i1 >> 16) & 0xff] << 16) ^ (\$sbox[(\$$from$i2 >> 8) & 0xff] << 8) ^ \$sbox[\$$from$i3 & 0xff] ^ \$k$i;\n";
    }
    return $code;
}

/** @return array{int, int, int} The columns ShiftRows (or InvShiftRows) moves into rows 1-3 of column $i. */
function columns(bool $decrypt, int $i): array
{
    return $decrypt
        ? [($i + 3) % 4, ($i + 2) % 4, ($i + 1) % 4]
        : [($i + 1) % 4, ($i + 2) % 4, ($i + 3) % 4];
}

function method(string $name, bool $decrypt): string
{
    $input = $decrypt ? 'c' : 'p';
    // The rounds use the last round key as $k0-$k3 and the others from $k4: encryption keys are in
    // natural order (the last one at 56), decryption keys already in this order.
    $loadKeys = '';
    for ($i = 0; $i < 60; $i++) {
        $index = $decrypt ? $i : ($i < 4 ? 56 + $i : $i - 4);
        $loadKeys .= "        \$k$i = \$keys[$index];\n";
    }
    // Encryption: q = c_{i-1}, r = p_{i-1}; decryption: q = p_{i-1}, r = c_{i-1}. Either way the
    // output block is the AES output XORed with r, and the next q.
    [$first, $second] = $decrypt ? ['r', 'q'] : ['q', 'r'];
    $rounds = rounds($decrypt, '                ');
    return <<<PHP
            /**
             * @param list<int> \$keys
             * @param array{array<int, int>, array<int, int>, array<int, int>, array<int, int>, array<int, int>} \$tables
             */
            private static function $name(string \$text, array \$keys, string \$iv1, string \$iv2, array \$tables): string
            {
                [\$t0, \$t1, \$t2, \$t3, \$sbox] = \$tables;
        $loadKeys        [1 => \${$first}0, 2 => \${$first}1, 3 => \${$first}2, 4 => \${$first}3] = unpack('N4', \$iv1);
                [1 => \${$second}0, 2 => \${$second}1, 3 => \${$second}2, 4 => \${$second}3] = unpack('N4', \$iv2);
                \$result = '';
                // Unpack 64 KiB at a time, to bound the memory taken by the arrays of words.
                for (\$offset = 0, \$length = \\strlen(\$text); \$offset < \$length; \$offset += 65536) {
                    \$in = unpack('N*', substr(\$text, \$offset, 65536));
                    \$out = [];
                    for (\$j = 1, \$count = \\count(\$in); \$j <= \$count; \$j += 4) {
                        \${$input}0 = \$in[\$j];
                        \${$input}1 = \$in[\$j + 1];
                        \${$input}2 = \$in[\$j + 2];
                        \${$input}3 = \$in[\$j + 3];
        $rounds                \$q0 = \$o0 ^ \$r0;
                        \$q1 = \$o1 ^ \$r1;
                        \$q2 = \$o2 ^ \$r2;
                        \$q3 = \$o3 ^ \$r3;
                        \$r0 = \${$input}0;
                        \$r1 = \${$input}1;
                        \$r2 = \${$input}2;
                        \$r3 = \${$input}3;
                        \$out[] = \$q0;
                        \$out[] = \$q1;
                        \$out[] = \$q2;
                        \$out[] = \$q3;
                    }
                    \$result .= pack('N*', ...\$out);
                }
                return \$result;
            }

        PHP;
}

$encrypt = method('encryptBlocks', false);
$decrypt = method('decryptBlocks', true);

$file = <<<PHP
    <?php

    declare(strict_types=1);

    /**
     * Crypt module.
     *
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

    namespace danog\MadelineProto\MTProtoTools\Crypt;

    use phpseclib4\Crypt\Rijndael;
    use ValueError;

    /**
     * Pure PHP IGE implementation, used without OpenSSL.
     *
     * AES-256 with T-tables (phpseclib's), unrolled on 32-bit words and expanded once per key.
     * Generated by tools/gen_ige_php.php: do not edit, run `php tools/gen_ige_php.php` instead.
     *
     * @internal
     */
    final class IGEPhp extends IGE
    {
        /** @var ?array{array{array<int, int>, array<int, int>, array<int, int>, array<int, int>, array<int, int>}, array{array<int, int>, array<int, int>, array<int, int>, array<int, int>, array<int, int>}, array{array<int, int>, array<int, int>, array<int, int>, array<int, int>}} */
        private static ?array \$tables = null;
        /**
         * The expanded key, in natural order.
         *
         * @var ?list<int>
         */
        private ?array \$encryptKeys = null;
        /**
         * Round keys of the equivalent inverse cipher, in the order the rounds use them: the last one first, then the others.
         *
         * @var ?list<int>
         */
        private ?array \$decryptKeys = null;

        protected function __construct(#[\SensitiveParameter] private readonly string \$key, string \$iv)
        {
            if (\\strlen(\$key) !== 32 || \\strlen(\$iv) !== 32) {
                throw new ValueError('AES-256-IGE requires a 32-byte key and IV');
            }
            \$this->iv_part_1 = substr(\$iv, 0, 16);
            \$this->iv_part_2 = substr(\$iv, 16);
        }

        #[\Override]
        public function encrypt(string \$plaintext): string
        {
            if (\\strlen(\$plaintext) % 16) {
                throw new ValueError('AES-IGE data length must be a multiple of 16');
            }
            if (\$plaintext === '') {
                return '';
            }
            \$tables = self::tables();
            \$this->encryptKeys ??= self::encryptKeys(\$this->key, \$tables[0][4]);
            \$ciphertext = self::encryptBlocks(\$plaintext, \$this->encryptKeys, \$this->iv_part_1, \$this->iv_part_2, \$tables[0]);
            \$this->iv_part_1 = substr(\$ciphertext, -16);
            \$this->iv_part_2 = substr(\$plaintext, -16);
            return \$ciphertext;
        }

        #[\Override]
        public function decrypt(string \$ciphertext): string
        {
            if (\\strlen(\$ciphertext) % 16) {
                throw new ValueError('AES-IGE data length must be a multiple of 16');
            }
            if (\$ciphertext === '') {
                return '';
            }
            \$tables = self::tables();
            \$this->decryptKeys ??= self::decryptKeys(\$this->encryptKeys ?? self::encryptKeys(\$this->key, \$tables[0][4]), \$tables[2]);
            \$plaintext = self::decryptBlocks(\$ciphertext, \$this->decryptKeys, \$this->iv_part_1, \$this->iv_part_2, \$tables[1]);
            \$this->iv_part_1 = substr(\$ciphertext, -16);
            \$this->iv_part_2 = substr(\$plaintext, -16);
            return \$plaintext;
        }

        /**
         * phpseclib's encryption and decryption T-tables, each followed by its S-box, and the
         * InvMixColumns tables of the decryption key schedule.
         *
         * @return array{array{array<int, int>, array<int, int>, array<int, int>, array<int, int>, array<int, int>}, array{array<int, int>, array<int, int>, array<int, int>, array<int, int>, array<int, int>}, array{array<int, int>, array<int, int>, array<int, int>, array<int, int>}}
         */
        private static function tables(): array
        {
            if (self::\$tables === null) {
                /** @psalm-suppress InaccessibleMethod, PossiblyInvalidFunctionCall */
                [\$encrypt, \$decrypt] = \Closure::bind(
                    fn (): array => [\$this->getTables(), \$this->getInvTables()],
                    new Rijndael('ecb'),
                    Rijndael::class,
                )();
                // The inverse T-tables are InvMixColumns ∘ InvSubBytes: after SubBytes, they give InvMixColumns.
                \$invMixColumns = [];
                foreach ([0, 1, 2, 3] as \$i) {
                    foreach (\$encrypt[4] as \$byte => \$substituted) {
                        \$invMixColumns[\$i][\$byte] = \$decrypt[\$i][\$substituted];
                    }
                }
                self::\$tables = [\$encrypt, \$decrypt, \$invMixColumns];
            }
            return self::\$tables;
        }

        /**
         * AES-256 key expansion (FIPS-197 5.2), in natural order: eight words per step.
         *
         * @param array<int, int> \$sbox
         * @return list<int>
         */
        private static function encryptKeys(#[\SensitiveParameter] string \$key, array \$sbox): array
        {
            [1 => \$w0, 2 => \$w1, 3 => \$w2, 4 => \$w3, 5 => \$w4, 6 => \$w5, 7 => \$w6, 8 => \$w7] = unpack('N8', \$key);
            \$words = [\$w0, \$w1, \$w2, \$w3, \$w4, \$w5, \$w6, \$w7];
            for (\$rcon = 0x01000000; ; \$rcon <<= 1) {
                // SubWord(RotWord(w7)) ^ rcon
                \$words[] = \$w0 ^= (\$sbox[(\$w7 >> 16) & 0xff] << 24) ^ (\$sbox[(\$w7 >> 8) & 0xff] << 16) ^ (\$sbox[\$w7 & 0xff] << 8) ^ \$sbox[\$w7 >> 24] ^ \$rcon;
                \$words[] = \$w1 ^= \$w0;
                \$words[] = \$w2 ^= \$w1;
                \$words[] = \$w3 ^= \$w2;
                if (\$rcon === 0x40000000) {
                    return \$words;
                }
                // SubWord(w3)
                \$words[] = \$w4 ^= (\$sbox[\$w3 >> 24] << 24) ^ (\$sbox[(\$w3 >> 16) & 0xff] << 16) ^ (\$sbox[(\$w3 >> 8) & 0xff] << 8) ^ \$sbox[\$w3 & 0xff];
                \$words[] = \$w5 ^= \$w4;
                \$words[] = \$w6 ^= \$w5;
                \$words[] = \$w7 ^= \$w6;
            }
        }

        /**
         * Round keys of the equivalent inverse cipher (FIPS-197 5.3.5): the first round key, the last
         * one, then the others from the 13th, with InvMixColumns applied.
         *
         * @param list<int> \$keys The expanded key, in natural order.
         * @param array{array<int, int>, array<int, int>, array<int, int>, array<int, int>} \$invMixColumns
         * @return list<int>
         */
        private static function decryptKeys(array \$keys, array \$invMixColumns): array
        {
            [\$m0, \$m1, \$m2, \$m3] = \$invMixColumns;
            \$result = [\$keys[0], \$keys[1], \$keys[2], \$keys[3], \$keys[56], \$keys[57], \$keys[58], \$keys[59]];
            for (\$i = 52; \$i > 0; \$i -= 4) {
                \$w = \$keys[\$i];
                \$result[] = \$m0[\$w >> 24] ^ \$m1[(\$w >> 16) & 0xff] ^ \$m2[(\$w >> 8) & 0xff] ^ \$m3[\$w & 0xff];
                \$w = \$keys[\$i + 1];
                \$result[] = \$m0[\$w >> 24] ^ \$m1[(\$w >> 16) & 0xff] ^ \$m2[(\$w >> 8) & 0xff] ^ \$m3[\$w & 0xff];
                \$w = \$keys[\$i + 2];
                \$result[] = \$m0[\$w >> 24] ^ \$m1[(\$w >> 16) & 0xff] ^ \$m2[(\$w >> 8) & 0xff] ^ \$m3[\$w & 0xff];
                \$w = \$keys[\$i + 3];
                \$result[] = \$m0[\$w >> 24] ^ \$m1[(\$w >> 16) & 0xff] ^ \$m2[(\$w >> 8) & 0xff] ^ \$m3[\$w & 0xff];
            }
            return \$result;
        }

    $encrypt$decrypt}

    PHP;

$path = $argv[1] ?? __DIR__.'/../src/MTProtoTools/Crypt/IGEPhp.php';
file_put_contents($path, rtrim($file)."\n");
