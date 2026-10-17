<?php declare(strict_types=1);

namespace phpseclib3\Math;

if (!class_exists(BigInteger::class)) {
    /**
     * Loads the BigIntegers of sessions created with phpseclib 3.
     *
     * phpseclib up to 3.0.45 serialized BigInteger through __sleep, which stores its private $hex under
     * the mangled name "\0phpseclib3\Math\BigInteger\0hex": phpseclib 4's __unserialize only reads 'hex',
     * so a plain class_alias made it throw in the middle of the unserialization of the whole session.
     *
     * @internal
     */
    class BigInteger extends \phpseclib4\Math\BigInteger
    {
        public function __unserialize(array $data): void
        {
            $private = "\0".self::class."\0";
            $hex = $data['hex'] ?? $data[$private.'hex'] ?? null;
            if (!\is_string($hex)) {
                throw new \UnexpectedValueException('Invalid serialized phpseclib3 BigInteger');
            }
            $precision = $data['precision'] ?? $data[$private.'precision'] ?? null;
            parent::__unserialize(\is_int($precision) ? ['hex' => $hex, 'precision' => $precision] : ['hex' => $hex]);
        }
    }
}
