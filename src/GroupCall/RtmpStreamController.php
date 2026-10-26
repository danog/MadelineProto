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

namespace danog\MadelineProto\GroupCall;

use Amp\ByteStream\ReadableStream;
use Amp\CancelledException;
use Amp\DeferredFuture;
use Amp\TimeoutCancellation;
use danog\MadelineProto\EventHandler\Calls\RtmpStream;
use danog\MadelineProto\Exception;
use danog\MadelineProto\GroupCall\Rtmp\RtmpClient;
use danog\MadelineProto\GroupCall\Rtmp\RtmpPublisher;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RestoresDeclaredProperties;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use Webmozart\Assert\Assert;

/**
 * Publishes what its disk jockey plays to an RTMP server, with a fixed URL and stream key, see {@see RtmpStream}.
 *
 * @internal
 */
final class RtmpStreamController implements CallControllerInterface
{
    use RestoresDeclaredProperties;

    public readonly RtmpStream $public;
    private DjLoop $diskJockey;
    /** Not saved: started again on wakeup, see {@see self::resumeAfterRestart()}. */
    private ?RtmpPublisher $publisher = null;
    private bool $closed = false;
    /** Whether the stream was restored from a session, and is yet to be resumed by {@see self::resumeAfterRestart()}. */
    private bool $resumePending = false;

    public function __construct(
        private readonly MTProto $API,
        int $id,
        private readonly string $url,
        #[\SensitiveParameter]
        private readonly string $key,
        /** Whether to connect only to a server on the internet, see {@see RtmpClient::resolvePublic()}. */
        private readonly bool $publicOnly = false,
    ) {
        $this->public = new RtmpStream($API, $id, $url);
        $this->diskJockey = new DjLoop($this, rtmp: true);
        Assert::true($this->diskJockey->start());
        $this->log("Publishing what is played to {$this}", Logger::NOTICE);
        $this->publisher = $this->createPublisher();
    }

    /**
     * Whether this stream publishes to the given URL with the given key.
     */
    public function publishesTo(string $url, #[\SensitiveParameter] string $key): bool
    {
        return $this->url === $url && $this->key === $key;
    }

    private function createPublisher(): RtmpPublisher
    {
        // The key is fixed: a refused stream is retried with a backoff (the server may still hold our previous
        // session after a network drop) until the stream is closed.
        return new RtmpPublisher(
            $this,
            $this->diskJockey,
            fn (bool $revoke): array => ['url' => $this->url, 'key' => $this->key],
            // A server that isn't on the internet, with $publicOnly.
            function (): void {
                $this->close();
            },
            publicOnly: $this->publicOnly,
        );
    }

    public function __serialize(): array
    {
        $result = get_object_vars($this);
        unset($result['publisher'], $result['resumePending']);
        return $result;
    }

    public function __unserialize(array $data): void
    {
        $this->publisher = null;
        $this->restoreDeclaredProperties($data);
        // Resumed by MTProto::wakeup(), see resumeAfterRestart().
        $this->resumePending = !$this->closed;
        if (!$this->closed) {
            Assert::true($this->diskJockey->start());
        }
    }

    /**
     * Publish again after the process restarted.
     *
     * @internal
     */
    public function resumeAfterRestart(): void
    {
        if (!$this->resumePending) {
            return;
        }
        $this->resumePending = false;
        $this->log("Resumed publishing to {$this} after a restart of the process.");
        $this->publisher = $this->createPublisher();
        // What is played before is dropped, see beforePlay().
        $this->awaitConnection();
        if (!$this->closed) {
            $this->diskJockey->resumeReader();
        }
    }

    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest): void
    {
        $this->beforePlay($dest);
        $this->diskJockey->play($file);
    }

    public function playBlocking(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest): void
    {
        $this->play($file, $dest);
        if ($file instanceof ReadableStream) {
            $closed = new DeferredFuture;
            $file->onClose($closed->complete(...));
            $closed->getFuture()->await();
        }
    }

    public function playOnHold(MediaDestination $dest, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $this->beforePlay($dest);
        $this->diskJockey->playOnHold(...$files);
    }

    public function skip(MediaDestination $dest): void
    {
        if ($dest === MediaDestination::Camera) {
            $this->diskJockey->skip();
        }
    }

    public function stop(MediaDestination $dest): void
    {
        if ($dest === MediaDestination::Camera) {
            $this->diskJockey->stopPlaying();
        }
    }

    public function pause(MediaDestination $dest): void
    {
        if ($dest === MediaDestination::Camera) {
            $this->diskJockey->pausePlaying();
        }
    }

    public function resume(MediaDestination $dest): void
    {
        if ($dest === MediaDestination::Camera) {
            $this->diskJockey->resumePlaying();
        }
    }

    public function isPaused(MediaDestination $dest): bool
    {
        return $dest === MediaDestination::Camera && $this->diskJockey->isAudioPaused();
    }

    public function getCurrent(MediaDestination $dest): LocalFile|RemoteUrl|string|null
    {
        return $dest === MediaDestination::Camera ? $this->diskJockey->getCurrent() : null;
    }

    /**
     * Whether it connects only to a server on the internet.
     */
    public function isPublicOnly(): bool
    {
        return $this->publicOnly;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function isConnected(): bool
    {
        return $this->publisher?->isConnected() ?? false;
    }

    /**
     * Stop publishing for good.
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }
        $this->closed = true;
        $this->resumePending = false;
        $this->log("Stopped publishing to {$this}", Logger::NOTICE);
        $this->publisher?->stop();
        $this->publisher = null;
        $this->diskJockey->discard();
    }

    private function beforePlay(MediaDestination $dest): void
    {
        if ($this->closed) {
            throw new Exception("Cannot play to {$this}: it was closed");
        }
        if ($dest !== MediaDestination::Camera) {
            throw new Exception("An RTMP stream has no {$dest->name} stream: only the camera one");
        }
        // Reconnect right away, if the server dropped the connection while nothing was played.
        $this->publisher?->wake();
        $this->awaitConnection();
    }

    /**
     * Wait for the connection to the server: what is played before is dropped, with it the first keyframe, and the
     * video until the next one.
     */
    private function awaitConnection(): void
    {
        try {
            $this->publisher?->awaitConnection(new TimeoutCancellation(self::CONNECTION_TIMEOUT));
        } catch (CancelledException) {
            $this->log("Not connected to {$this} after ".self::CONNECTION_TIMEOUT." seconds: playing anyway, published once connected", Logger::WARNING);
        }
    }

    /** How long play() waits for the connection to the server, in seconds. */
    private const CONNECTION_TIMEOUT = 10;

    #[\Override]
    public function log(string $message, int $level = Logger::NOTICE): void
    {
        // Not $this->API->logger: unset while the session is unserialized, when the disk jockey starts.
        $this->API->logger($message, $level, 'RtmpStream');
    }

    #[\Override]
    public function getVoIPSettings(): \danog\MadelineProto\Settings\VoIP
    {
        return $this->API->getSettings()->getVoip();
    }

    #[\Override]
    public function isCallEnded(): bool
    {
        return $this->closed;
    }

    #[\Override]
    public function __toString(): string
    {
        return (string) $this->public;
    }
}
