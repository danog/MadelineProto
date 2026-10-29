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
        return $this->process($plaintext, true);
    }

    #[\Override]
    public function decrypt(string $ciphertext): string
    {
        return $this->process($ciphertext, false);
    }

    private function process(string $data, bool $encrypt): string
    {
        if (\strlen($data) % 16) {
            throw new ValueError('AES-IGE data length must be a multiple of 16');
        }
        $lock = $this->mutex->acquire();
        try {
            if ($data === '') {
                return '';
            }
            $executor = NativeExecutor::get();
            if ($executor) {
                try {
                    $result = $executor->run(fn (): array => \danog\MadelineProto\Native\Crypto::ige($data, $this->key, $this->iv, $encrypt));
                    $this->iv = $result[1];
                    return $result[0];
                } catch (\Exception $e) {
                    if (!NativeExecutor::failedWith($e, NativeExecutor::UNAVAILABLE)) {
                        throw $e;
                    }
                }
            }
            // The last committed IV is sufficient to continue without the executor.
            $fallback = IGE::getSyncInstance($this->key, $this->iv);
            $output = $encrypt ? $fallback->encrypt($data) : $fallback->decrypt($data);
            $this->iv = $fallback->getState();
            return $output;
        } finally {
            $lock->release();
        }
    }
}
