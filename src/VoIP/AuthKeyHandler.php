<?php

declare(strict_types=1);

/**
 * AuthKeyHandler module.
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

namespace danog\MadelineProto\VoIP;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\DeferredFuture;
use AssertionError;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Ogg;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\Tools;
use danog\MadelineProto\VoIP;
use danog\MadelineProto\VoIPController;
use phpseclib3\Math\BigInteger;
use Throwable;

use const STR_PAD_LEFT;

/**
 * Manages the creation of the authorization key.
 *
 * https://core.telegram.org/mtproto/auth_key
 * https://core.telegram.org/mtproto/samples-auth_key
 *
 * @internal
 */
trait AuthKeyHandler
{
    /** @var array<int, VoIPController> */
    private array $calls = [];
    /** @var array<int, VoIPController> */
    private array $callsByPeer = [];
    private array $pendingCalls = [];
    /**
     * Request VoIP call.
     *
     * @param mixed $user User
     */
    public function requestCall(mixed $user): VoIP
    {
        $user = ($this->getInfo($user));
        if ($user['type'] !== 'user') {
            throw new AssertionError("Can only create a call with a user!");
        }
        $user = $user['bot_api_id'];
        if (isset($this->pendingCalls[$user])) {
            return $this->pendingCalls[$user]->await();
        }
        $deferred = new DeferredFuture;
        $this->pendingCalls[$user] = $deferred->getFuture();

        try {
            $this->logger->logger(sprintf('Calling %s...', $user), Logger::VERBOSE);
            $dh_config = ($this->getDhConfig());
            $this->logger->logger('Generating a...', Logger::VERBOSE);
            $a = BigInteger::randomRange(Magic::$two, $dh_config['p']->subtract(Magic::$two));
            $this->logger->logger('Generating g_a...', Logger::VERBOSE);
            $g_a = $dh_config['g']->powMod($a, $dh_config['p']);
            Crypt::checkG($g_a, $dh_config['p']);
            $res = $this->methodCallAsyncRead('phone.requestCall', [
                'user_id' => $user,
                'g_a_hash' => hash('sha256', $g_a->toBytes(), true),
                'protocol' => VoIPController::CALL_PROTOCOL,
            ])['phone_call'];
            $res['a'] = $a;
            $res['g_a'] = str_pad($g_a->toBytes(), 256, \chr(0), STR_PAD_LEFT);
            $this->calls[$res['id']] = $controller = new VoIPController($this, $res);
            $this->callsByPeer[$controller->public->otherID] = $controller;
            unset($this->pendingCalls[$user]);
            $deferred->complete($controller->public);
        } catch (Throwable $e) {
            unset($this->pendingCalls[$user]);
            $deferred->error($e);
        }
        return $deferred->getFuture()->await();
    }

    /** @internal */
    public function cleanupCall(int $id): void
    {
        if (isset($this->calls[$id])) {
            $call = $this->calls[$id];
            unset($this->callsByPeer[$call->public->otherID], $this->calls[$id]);
        }
    }

    /**
     * Get call emojis (will return null if the call is not inited yet).
     *
     * @internal
     *
     * @return ?list{string, string, string, string}
     */
    public function getCallVisualization(int $id): ?array
    {
        return ($this->calls[$id] ?? null)?->getVisualization();
    }

    /**
     * Accept call.
     */
    public function acceptCall(int $id, ?Cancellation $cancellation = null): void
    {
        ($this->calls[$id] ?? null)?->accept($cancellation);
    }

    /**
     * Discard call.
     *
     * @param int<1, 5> $rating  Call rating in stars
     * @param string    $comment Additional comment on call quality.
     */
    public function discardCall(int $id, DiscardReason $reason = DiscardReason::HANGUP, ?int $rating = null, ?string $comment = null): void
    {
        ($this->calls[$id] ?? null)?->discard($reason, $rating, $comment);
    }

    /**
     * Get the phone call with the specified user ID.
     */
    public function getCallByPeer(int $userId): ?VoIP
    {
        return ($this->callsByPeer[$userId] ?? null)?->public;
    }

    /**
     * Get all pending and running calls, indexed by user ID.
     *
     * @return array<int, VoIP>
     */
    public function getAllCalls(): array
    {
        return array_map(static fn (VoIPController $v): VoIP => $v->public, $this->callsByPeer);
    }

    /**
     * Get phone call information.
     */
    public function getCall(int $id): ?VoIP
    {
        return ($this->calls[$id] ?? null)?->public;
    }

    /**
     * Play file in call.
     */
    public function callPlay(int $id, LocalFile|RemoteUrl|ReadableStream $file): void
    {
        if (!Tools::canConvertOgg()) {
            if ($file instanceof LocalFile || $file instanceof RemoteUrl) {
                Ogg::validateOgg($file);
            } else {
                throw new AssertionError("The passed file was not generated by MadelineProto or @libtgvoipbot, please pre-convert it using @libtgvoip bot or install FFI and ffmpeg to perform realtime conversion!");
            }
        }
        ($this->calls[$id] ?? null)?->play($file);
    }

    /**
     * Set output file or stream for incoming OPUS audio packets in a call.
     *
     * Will write an OGG OPUS stream to the specified file or stream.
     */
    public function callSetOutput(int $id, LocalFile|WritableStream $file): void
    {
        ($this->calls[$id] ?? null)?->setOutput($file);
    }

    /**
     * Play file in call, blocking until the file has finished playing if a stream is provided.
     *
     * @internal
     */
    public function callPlayBlocking(int $id, LocalFile|RemoteUrl|ReadableStream $file): void
    {
        if (!isset($this->calls[$id])) {
            return;
        }
        $this->callPlay($id, $file);
        if ($file instanceof ReadableStream) {
            $deferred = new DeferredFuture;
            $file->onClose($deferred->complete(...));
            $deferred->getFuture()->await();
        }
    }

    /**
     * When called, skips to the next file in the playlist.
     */
    public function skipPlay(int $id): void
    {
        ($this->calls[$id] ?? null)?->skip();
    }

    /**
     * Stops playing all files in the call, clears the main and the hold playlist.
     */
    public function stopPlay(int $id): void
    {
        ($this->calls[$id] ?? null)?->stop();
    }

    /**
     * Pauses playback of the current audio file in the call.
     */
    public function pausePlay(int $id): void
    {
        ($this->calls[$id] ?? null)?->pause();
    }

    /**
     * Resumes playback of the current audio file in the call.
     */
    public function resumePlay(int $id): void
    {
        ($this->calls[$id] ?? null)?->resume();
    }

    /**
     * Whether the currently playing audio file is paused.
     */
    public function isPlayPaused(int $id): bool
    {
        return ($this->calls[$id] ?? null)?->isPaused() ?? false;
    }

    /**
     * Play files on hold in call.
     */
    public function callPlayOnHold(int $id, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        if (!Tools::canConvertOgg()) {
            foreach ($files as $file) {
                if ($file instanceof LocalFile || $file instanceof RemoteUrl) {
                    Ogg::validateOgg($file);
                } else {
                    throw new AssertionError("The passed file was not generated by MadelineProto or @libtgvoipbot, please pre-convert it using @libtgvoip bot or install FFI and ffmpeg to perform realtime conversion!");
                }
            }
        }
        ($this->calls[$id] ?? null)?->playOnHold(...$files);
    }

    /**
     * Play files on hold in call.
     *
     * @internal
     */
    public function callPlayOnHoldBlocking(int $id, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        if (!isset($this->calls[$id])) {
            return;
        }
        $this->callPlayOnHold($id, ...$files);
        foreach ($files as $file) {
            if ($file instanceof ReadableStream) {
                $deferred = new DeferredFuture;
                $file->onClose($deferred->complete(...));
                $deferred->getFuture()->await();
            }
        }
    }

    /**
     * Get the file that is currently being played.
     *
     * Will return a string with the object ID of the stream if we're currently playing a stream, otherwise returns the related LocalFile or RemoteUrl.
     */
    public function callGetCurrent(int $id): RemoteUrl|LocalFile|string|null
    {
        return ($this->calls[$id] ?? null)?->getCurrent();
    }

    /**
     * Get call state.
     */
    public function getCallState(int $id): ?CallState
    {
        return ($this->calls[$id] ?? null)?->getCallState();
    }
}
