<?php

declare(strict_types=1);

/**
 * Internal loop trait.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Loop\VoIP;

use Amp\ByteStream\Pipe;
use Amp\ByteStream\ReadableStream;
use Amp\Cancellation;
use Amp\CancelledException;
use Amp\DeferredCancellation;
use Amp\DeferredFuture;
use AssertionError;
use danog\Loop\Loop;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Loop\VoIPLoop;
use danog\MadelineProto\Ogg;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\Tools;
use danog\MadelineProto\VoIP;
use danog\MadelineProto\VoIP\CallState;
use danog\MadelineProto\VoIPController;
use Revolt\EventLoop;
use SplQueue;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * VoIP loop.
 *
 * @internal
 */
final class DjLoop extends VoIPLoop
{
    /** @var array<LocalFile|RemoteUrl|ReadableStream> */
    private array $holdFiles = [];
    /** @var list<LocalFile|RemoteUrl|ReadableStream> */
    private array $inputFiles = [];
    private bool $playingPrimary = true;
    private bool $readingPrimary = true;
    private SplQueue $packetQueuePrimary;
    private SplQueue $packetQueueSecondary;

    private LocalFile|RemoteUrl|string|null $descriptionPrimary = null;
    private LocalFile|RemoteUrl|string|null $descriptionSecondary = null;

    private ?DeferredFuture $packetDeferred = null;

    private bool $playingHold = false;

    private Cancellation $cancellationPrimary;
    private DeferredCancellation $deferredPrimary;
    private Cancellation $cancellationSecondary;
    private DeferredCancellation $deferredSecondary;
    private int $holdIndex = 0;

    private bool $pause = false;

    public function __construct(VoIPController $instance)
    {
        parent::__construct($instance);
        $this->packetQueuePrimary = new SplQueue();
        $this->packetQueueSecondary = new SplQueue();
        $this->deferredPrimary = new DeferredCancellation;
        $this->cancellationPrimary = $this->deferredPrimary->getCancellation();
        $this->deferredSecondary = new DeferredCancellation;
        $this->cancellationSecondary = $this->deferredSecondary->getCancellation();
    }

    public function __serialize(): array
    {
        return [
            'pause' => $this->pause,
            'instance' => $this->instance,
            'holdFiles' => array_filter(
                $this->holdFiles,
                static fn ($v) => !$v instanceof ReadableStream
            ),
            'inputFiles' => array_filter(
                $this->inputFiles,
                static fn ($v) => !$v instanceof ReadableStream
            ),
            'packetQueuePrimary' => $this->packetQueuePrimary,
            'packetQueueSecondary' => $this->packetQueueSecondary,
            'readingPrimary' => $this->readingPrimary,
            'playingPrimary' => $this->playingPrimary,
            'playingHold' => $this->playingHold,
            'holdIndex' => $this->holdIndex,
        ];
    }
    /**
     * Wakeup function.
     */
    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        $this->deferredPrimary = new DeferredCancellation;
        $this->cancellationPrimary = $this->deferredPrimary->getCancellation();
        $this->deferredSecondary = new DeferredCancellation;
        $this->cancellationSecondary = $this->deferredSecondary->getCancellation();
    }

    public function discard(): void
    {
        $this->deferredPrimary->cancel();
        $this->deferredSecondary->cancel();
        $this->packetQueuePrimary = new SplQueue;
        $this->packetQueueSecondary = new SplQueue;
        $this->packetDeferred?->complete(false);
    }

    protected function loop(): ?float
    {
        if ($this->instance->getCallState() === CallState::ENDED) {
            $this->instance->log("Exiting DJ loop in $this because the call ended!");
            return self::STOP;
        }
        if (!($this->readingPrimary ? $this->packetQueuePrimary : $this->packetQueueSecondary)->isEmpty()) {
            $this->instance->log("Pausing DJ loop in $this because both queues are full!");
            return self::PAUSE;
        }
        if (!$this->inputFiles) {
            $this->instance->log("Pausing DJ loop in $this because we have nothing to play!");
            return self::PAUSE;
        }
        if ($this->inputFiles[0] instanceof ReadableStream && !($this->readingPrimary ? $this->packetQueueSecondary : $this->packetQueuePrimary)->isEmpty()) {
            $this->instance->log("Pausing DJ loop in $this because the next audio is a stream, and we're still playing the old file!");
            return self::PAUSE;
        }
        $this->instance->log("Resuming DJ loop in $this!");
        $file = array_shift($this->inputFiles);
        try {
            $fileStr = match (true) {
                $file instanceof LocalFile => $file->file,
                $file instanceof RemoteUrl => $file->url,
                $file instanceof ReadableStream => 'stream '.spl_object_id($file)
            };
            $p = $this->readingPrimary ? 'primary queue' : 'secondary queue';
            $this->instance->log("DJ loop: playing $fileStr to $p in $this!");
            $desc = match (true) {
                $file instanceof LocalFile => $file,
                $file instanceof RemoteUrl => $file,
                $file instanceof ReadableStream => 'stream '.spl_object_id($file)
            };
            if ($this->readingPrimary) {
                $this->descriptionPrimary = $desc;
            } else {
                $this->descriptionSecondary = $desc;
            }
            $this->startPlaying(
                $file,
                $this->readingPrimary ? $this->packetQueuePrimary : $this->packetQueueSecondary,
                $this->readingPrimary ? $this->cancellationPrimary : $this->cancellationSecondary,
            );
        } catch (CancelledException) {
            if ($this->packetDeferred) {
                $deferred = $this->packetDeferred;
                $this->packetDeferred = null;
                $deferred?->complete(false);
            }
        } catch (Throwable $e) {
            $this->instance->log("Got $e in $this!");
        } finally {
            $this->readingPrimary = !$this->readingPrimary;
        }

        return self::CONTINUE;
    }

    private function startPlaying(LocalFile|RemoteUrl|ReadableStream $f, SplQueue $queue, Cancellation $cancellation): void
    {
        $it = null;
        if ($f instanceof LocalFile || $f instanceof RemoteUrl) {
            try {
                $it = new Ogg($f, $cancellation);
                if (!\in_array('MADELINE_ENCODER_V=1', $it->comments, true)) {
                    $it = null;
                }
            } catch (CancelledException $e) {
                throw $e;
            } catch (Throwable) {
                $it = null;
            }
        }
        if (!$it) {
            if (!Tools::canConvertOgg()) {
                throw new AssertionError("The passed file was not generated by MadelineProto or @libtgvoipbot, please pre-convert it using @libtgvoip bot or install FFI and ffmpeg to perform realtime conversion!");
            }
            $this->instance->log("Starting conversion fiber...");
            $pipe = new Pipe(4096);
            EventLoop::queue(static function () use ($f, $pipe, $cancellation): void {
                try {
                    Ogg::convert($f, $pipe->getSink(), $cancellation);
                } catch (CancelledException) {
                } finally {
                    EventLoop::queue($pipe->getSink()->close(...));
                }
            });
            $it = new Ogg($pipe->getSource());
        }
        foreach ($it->opusPackets as $packet) {
            $queue->enqueue($packet);
            if ($this->packetDeferred) {
                $deferred = $this->packetDeferred;
                $this->packetDeferred = null;
                $deferred->complete(true);
            }
        }
    }
    public function pullPacket(): ?string
    {
        if ($this->pause) {
            return null;
        }
        $queue = $this->playingPrimary ? $this->packetQueuePrimary : $this->packetQueueSecondary;
        if ($queue->isEmpty()) {
            if ($this->instance->getCallState() === CallState::ENDED || !$this->isRunning()) {
                return null;
            }
            if ($this->readingPrimary !== $this->playingPrimary) {
                if ($this->playingPrimary) {
                    $this->descriptionPrimary = null;
                } else {
                    $this->descriptionSecondary = null;
                }
                $this->playingPrimary = !$this->playingPrimary;
                return $this->pullPacket();
            }
            if ($this->isPaused()) {
                if ($this->inputFiles) {
                    Assert::true($this->resume());
                    return null;
                }
                if (!$this->holdFiles) {
                    return null;
                }
                $this->playingHold = true;
                $this->inputFiles []= $this->holdFiles[($this->holdIndex++) % \count($this->holdFiles)];
                Assert::true($this->resume());
                return null;
            }
            $this->packetDeferred ??= new DeferredFuture;
            if (!$this->packetDeferred->getFuture()->await()) {
                return null;
            }
        }
        return $queue->dequeue();
    }

    /**
     * Play file.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file): void
    {
        $this->inputFiles[] = $file;
        if ($this->playingHold) {
            $this->playingHold = false;
            $this->skip();
        }
        $this->resume();
    }

    /**
     * When called, skips to the next file in the playlist.
     */
    public function skip(): void
    {
        if ($this->playingPrimary) {
            $this->playingPrimary = false;
            $this->packetQueuePrimary = new SplQueue;
            $deferred = $this->deferredPrimary;
            $this->deferredPrimary = new DeferredCancellation;
            $this->cancellationPrimary = $this->deferredPrimary->getCancellation();
        } else {
            $this->playingPrimary = true;
            $this->packetQueueSecondary = new SplQueue;
            $deferred = $this->deferredSecondary;
            $this->deferredSecondary = new DeferredCancellation;
            $this->cancellationSecondary = $this->deferredSecondary->getCancellation();
        }
        $deferred->cancel();
        $this->resume();
    }
    /**
     * Stops playing all files, clears the main and the hold playlist.
     */
    public function stopPlaying(): void
    {
        $this->inputFiles = [];
        $this->holdFiles = [];
        $this->skip();
        $this->skip();
    }
    public function pausePlaying(): void
    {
        $this->pause = true;
    }
    public function resumePlaying(): void
    {
        $this->pause = false;
    }
    public function isAudioPaused(): bool
    {
        return $this->pause;
    }
    /**
     * Get info about the audio currently being played.
     *
     * Will return a string with the object ID of the stream if we're currently playing a stream, otherwise returns the related LocalFile or RemoteUrl.
     */
    public function getCurrent(): LocalFile|RemoteUrl|string|null
    {
        return $this->playingPrimary ? $this->descriptionPrimary : $this->descriptionSecondary;
    }
    /**
     * Files to play on hold.
     */
    public function playOnHold(LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $this->holdFiles = $files;
    }

    public function __toString(): string
    {
        return "DJ loop {$this->instance}";
    }
}
