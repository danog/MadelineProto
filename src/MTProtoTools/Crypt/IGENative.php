<?php

declare(strict_types=1);

/**
 * @author Alexander Pankratov <s.pankratoff@gmail.com>
 * @copyright 2026 Alexander Pankratov <s.pankratoff@gmail.com>
 * @license https://opensource.org/licenses/AGPL-3.0 AGPLv3
 */

namespace danog\MadelineProto\MTProtoTools\Crypt;

use Amp\ForbidSerialization;
use Amp\Sync\LocalMutex;
use ValueError;

/**
 * Continuous IGE using the process-wide native executor.
 * @internal
 * @psalm-suppress PropertyNotSetInConstructor getState() replaces the legacy parent's split IV storage.
 */
final class IGENative extends IGE
{
    use ForbidSerialization;

    private readonly LocalMutex $mutex;

    protected function __construct(private readonly string $key, private string $iv)
    {
        if (\strlen($key) !== 32 || \strlen($iv) !== 32) {
            throw new ValueError('AES-256-IGE requires a 32-byte key and IV');
        }
        $this->mutex = new LocalMutex;
    }

    #[\Override]
    public function getState(): string
    {
        return $this->iv;
    }

    #[\Override]
    public function encrypt(string $plaintext): string
    {
        return $this->process($plaintext, NativeExecutor::ENCRYPT);
    }

    #[\Override]
    public function decrypt(string $ciphertext): string
    {
        return $this->process($ciphertext, NativeExecutor::DECRYPT);
    }

    private function process(string $data, int $operation): string
    {
        if (\strlen($data) % 16) {
            throw new ValueError('AES-IGE data length must be a multiple of 16');
        }
        $lock = $this->mutex->acquire();
        try {
            if ($data === '') {
                return '';
            }
            $executor = NativeExecutor::get($operation);
            if (!$executor) {
                // The last committed IV is sufficient to continue after executor shutdown.
                $fallback = IGE::getInstance($this->key, $this->iv);
                $output = $operation === NativeExecutor::ENCRYPT ? $fallback->encrypt($data) : $fallback->decrypt($data);
                $this->iv = $fallback->getState();
                return $output;
            }
            $result = $executor->submit(fn (\FFI $ffi, \FFI\CData $pool): int => (int) $ffi->madeline_crypto_submit($pool, $operation, $data, \strlen($data), $this->key, $this->iv))->await();
            $this->iv = $result['state'];
            return $result['data'];
        } finally {
            $lock->release();
        }
    }
}
