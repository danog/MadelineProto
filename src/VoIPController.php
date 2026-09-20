<?php declare(strict_types=1);

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

// Please keep the above notice the next time you copy my code, or I will sue you :)

namespace danog\MadelineProto;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\Sync\LocalMutex;
use danog\MadelineProto\EventHandler\Calls\PrivateCall;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\RPCError\CallAlreadyAcceptedError;
use danog\MadelineProto\RPCError\CallAlreadyDeclinedError;
use danog\MadelineProto\Tgcalls\CallInterface;
use danog\MadelineProto\Tgcalls\Controller;
use danog\MadelineProto\Tgcalls\LegacyController;
use danog\MadelineProto\VoIP\CallState;
use danog\MadelineProto\VoIP\DiscardReason;
use danog\MadelineProto\VoIP\MediaState;
use danog\MadelineProto\VoIP\SignalingProtocolVersion;
use phpseclib4\Math\BigInteger;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

/** @internal */
final class VoIPController implements CallInterface
{
    /**
     * The [phoneCallProtocol](https://core.telegram.org/constructor/phoneCallProtocol) we advertise.
     *
     * `udp_p2p`, `udp_reflector`, `min_layer` and `max_layer` are deprecated leftovers of libtgvoip
     * and must be sent with these exact hardcoded values, see
     * https://core.telegram.org/api/calls#populating-phonecallprotocol.
     */
    public const CALL_PROTOCOL = [
        '_' => 'phoneCallProtocol',
        'udp_p2p' => true,
        'udp_reflector' => true,
        'min_layer' => 65,
        'max_layer' => 92,
        'library_versions' => SignalingProtocolVersion::SUPPORTED,
    ];

    private CallState $callState;

    private array $call;
    private ?Controller $tgcallsController = null;
    private ?LegacyController $legacyController = null;

    private DjLoop $diskJockey;
    /** The separate video-only playlist for the presentation (screencast) stream, created on first use. */
    private ?DjLoop $presentationDj = null;

    /** Auth key */
    private ?string $authKey = null;

    public readonly PrivateCall $public;
    /** @var ?list{string, string, string, string} */
    private ?array $visualization = null;

    private LocalMutex $authMutex;

    private ?LocalFile $outputFile = null;

    private ?RecordingFormat $outputFormat = null;

    private bool $muted = false;

    /**
     * Constructor.
     *
     * @internal
     */
    public function __construct(
        public readonly MTProto $API,
        array $call
    ) {
        $this->public = new PrivateCall($API, $call);
        $call['_'] = 'inputPhoneCall';
        $this->diskJockey = new DjLoop($this);
        Assert::true($this->diskJockey->start());
        $this->call = $call;
        if ($this->public->outgoing) {
            $this->callState = CallState::REQUESTED;
        } else {
            $this->callState = CallState::INCOMING;
        }
        $this->authMutex = new LocalMutex;
    }

    /**
     * @psalm-mutation-free
     */
    public function __serialize(): array
    {
        // A call that never finished constructing (e.g. the process was killed mid-setup) must not be
        // resurrected: persist only an "ended" marker so its half-built subsystems (disk jockey, WebRTC
        // engine, recorders) are dropped from the graph and cannot start crashing loops on resume.
        if (!isset($this->public, $this->callState, $this->call)) {
            return ['callState' => CallState::ENDED];
        }
        $result = get_object_vars($this);
        // The engines (WebRTC and libtgvoip alike) now serialize themselves and resume their
        // connections on wakeup, so they are kept. Only the mutex, which wraps a live suspension
        // queue, is dropped and recreated.
        unset($result['authMutex']);

        return $result;
    }
    /**
     * Wakeup function.
     */
    public function __unserialize(array $data): void
    {
        $this->authMutex = new LocalMutex;
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        // Defensively drop a call that came back without its full essential state (a half-constructed
        // call that slipped into the session): force it ENDED and start nothing. Because no subsystem
        // starts async work during deserialization any more (that is all deferred to resume() below),
        // this is the single point that decides whether a restored call comes back to life at all.
        if (!isset($this->callState) || !isset($this->public) || !isset($this->call)
            || $this->callState === CallState::ENDED
        ) {
            $this->callState = CallState::ENDED;
            return;
        }
        if (!isset($this->API->logger)) {
            $this->API->setupLogger();
        }
        $this->diskJockey ??= new DjLoop($this);
        Assert::true($this->diskJockey->start());
        // Resume everything on the next tick — NEVER here: deserialization must stay synchronous, and
        // resuming reopens files and starts loops (async). By the time this runs the whole call graph
        // is restored and callState is known-good, so nothing dereferences half-restored state.
        EventLoop::queue(function (): void {
            if ($this->callState !== CallState::RUNNING) {
                return;
            }
            // The WebRTC/libtgvoip engine restored itself and its transport resumes on its own. Restart
            // the demuxer/reader, the playback producers and the (file-backed) recorders, which were all
            // left dormant during deserialization.
            $this->diskJockey->resumeReader();
            $this->tgcallsController?->resume();
            // The legacy (libtgvoip) engine still re-arms its recorder from the remembered output.
            if ($this->outputFile !== null) {
                $this->legacyController?->setOutput($this->outputFile);
            }
            $this->log("Resumed $this after a restart of the process.");
        });
    }

    /**
     * Confirm requested call.
     * @internal
     */
    public function confirm(array $params): bool
    {
        $lock = $this->authMutex->acquire();
        try {
            if ($this->callState !== CallState::REQUESTED) {
                $this->log(sprintf(Lang::$current_lang['call_error_2'], $this->public->callID));
                return false;
            }
            $this->log(sprintf(Lang::$current_lang['call_confirming'], $this->public->otherID), Logger::VERBOSE);
            $dh_config = $this->API->getDhConfig();
            $params['g_b'] = new BigInteger((string) $params['g_b'], 256);
            Crypt::checkG($params['g_b'], $dh_config['p']);
            $key = str_pad($params['g_b']->powMod($this->call['a'], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT);
            try {
                $res = ($this->API->methodCallAsyncRead('phone.confirmCall', [
                    'key_fingerprint' => substr(sha1($key, true), -8),
                    'peer' => ['id' => $params['id'], 'access_hash' => $params['access_hash'], '_' => 'inputPhoneCall'],
                    'g_a' => $this->call['g_a'],
                    'protocol' => self::CALL_PROTOCOL,
                ]))['phone_call'];
            } catch (CallAlreadyAcceptedError) {
                $this->log(sprintf(Lang::$current_lang['call_already_accepted'], $params['id']));
                return true;
            } catch (CallAlreadyDeclinedError) {
                $this->log(Lang::$current_lang['call_already_declined']);
                $this->discard(DiscardReason::HANGUP);
                return false;
            }
            \assert(isset($this->call['g_a']) && \is_string($this->call['g_a']));
            $this->visualization = self::computeVisualization($key, $this->call['g_a']);
            $this->authKey = $key;
            $this->callState = CallState::RUNNING;
            $this->initialize($res);
            return true;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }
    /**
     * Accept incoming call.
     */
    public function accept(?Cancellation $cancellation = null): self
    {
        $lock = $this->authMutex->acquire();
        try {
            if ($this->callState === CallState::RUNNING || $this->callState === CallState::ENDED) {
                return $this;
            }
            Assert::eq($this->callState->name, CallState::INCOMING->name);

            $this->log(sprintf(Lang::$current_lang['accepting_call'], $this->public->otherID), Logger::VERBOSE);
            $dh_config = $this->API->getDhConfig($cancellation);
            $this->log('Generating b...', Logger::VERBOSE);
            $b = BigInteger::randomRange(Magic::$two, $dh_config['p']->subtract(Magic::$two));
            $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
            Crypt::checkG($g_b, $dh_config['p']);

            $this->callState = CallState::ACCEPTED;
            try {
                $this->API->methodCallAsyncRead('phone.acceptCall', [
                    'peer' => [
                        'id' => $this->call['id'],
                        'access_hash' => $this->call['access_hash'],
                        '_' => 'inputPhoneCall',
                    ],
                    'g_b' => $g_b->toBytes(),
                    'protocol' => self::CALL_PROTOCOL,
                    'cancellation' => $cancellation,
                ]);
            } catch (CallAlreadyAcceptedError) {
                $this->log(sprintf(Lang::$current_lang['call_already_accepted'], $this->public->callID));
                return $this;
            } catch (CallAlreadyDeclinedError) {
                $this->log(Lang::$current_lang['call_already_declined']);
                $this->discard(DiscardReason::HANGUP);
                return $this;
            }
            $this->call['b'] = $b;

            return $this;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }

    /**
     * Complete call handshake.
     *
     * @internal
     */
    public function complete(array $params): bool
    {
        $lock = $this->authMutex->acquire();
        try {
            if ($this->callState !== CallState::ACCEPTED) {
                return false;
            }

            $this->log(sprintf(Lang::$current_lang['call_completing'], $this->public->otherID), Logger::VERBOSE);
            $dh_config = $this->API->getDhConfig();
            if (hash('sha256', (string) $params['g_a_or_b'], true) !== (string) $this->call['g_a_hash']) {
                throw new SecurityException('Invalid g_a!');
            }
            $params['g_a_or_b'] = new BigInteger((string) $params['g_a_or_b'], 256);
            Crypt::checkG($params['g_a_or_b'], $dh_config['p']);
            $key = str_pad($params['g_a_or_b']->powMod($this->call['b'], $dh_config['p'])->toBytes(), 256, \chr(0), STR_PAD_LEFT);
            if (substr(sha1($key, true), -8) != $params['key_fingerprint']) {
                throw new SecurityException(Lang::$current_lang['fingerprint_invalid']);
            }
            $this->visualization = self::computeVisualization(
                $key,
                str_pad($params['g_a_or_b']->toBytes(), 256, \chr(0), STR_PAD_LEFT)
            );
            $this->authKey = $key;
            $this->callState = CallState::RUNNING;
            $this->initialize($params);
            return true;
        } finally {
            EventLoop::queue($lock->release(...));
        }
    }

    /**
     * Compute the four emojis used to verify the call key.
     *
     * @return list{string, string, string, string}
     */
    private static function computeVisualization(string $key, string $g_a): array
    {
        $visualization = [];
        $length = new BigInteger(\count(Magic::$emojis));
        foreach (str_split(hash('sha256', $key.$g_a, true), 8) as $number) {
            $number[0] = \chr(\ord($number[0]) & 0x7f);
            $visualization[] = Magic::$emojis[(int) (new BigInteger($number, 256))->divide($length)[1]->toString()];
        }
        /** @var list{string, string, string, string} */
        return $visualization;
    }

    /**
     * Hand off the call to the WebRTC engine.
     */
    private function initialize(array $call): void
    {
        \assert($this->authKey !== null);
        $version = SignalingProtocolVersion::fromProtocol($call['protocol'] ?? []);
        if ($version === null) {
            $advertised = implode(', ', array_map(
                static fn (mixed $v): string => (string) $v,
                (array) ($call['protocol']['library_versions'] ?? [])
            ));
            $this->log(
                "Cannot set up $this: the other party only supports the tgcalls protocol versions "
                ."[$advertised], while MadelineProto implements ["
                .implode(', ', SignalingProtocolVersion::supported()).'].',
                Logger::ERROR
            );
            $this->discard(DiscardReason::DISCONNECTED);
            return;
        }
        if ($version->isLegacy()) {
            $this->log("Starting the libtgvoip engine of $this using protocol {$version->value}", Logger::NOTICE);
            $this->legacyController = new LegacyController(
                $this,
                $this->authKey,
                $this->public->outgoing,
                $this->diskJockey,
                $call['connections'] ?? [],
            );
            if ($this->outputFile !== null) {
                $this->legacyController->setOutput($this->outputFile);
            }
            return;
        }
        $this->log("Starting the WebRTC engine of $this using tgcalls protocol {$version->value}", Logger::NOTICE);
        $this->tgcallsController = new Controller(
            $this,
            $this->authKey,
            $this->public->outgoing,
            $version,
            $this->diskJockey,
            $call['connections'] ?? [],
        );
        if ($this->outputFile !== null) {
            $this->tgcallsController->setOutput($this->outputFile, $this->outputFormat);
        }
        // A presentation playlist requested before the engine existed is attached now.
        if ($this->presentationDj !== null) {
            $this->tgcallsController->enablePresentation($this->presentationDj);
        }
    }

    /**
     * Deliver an outgoing signaling packet to the other party.
     *
     * @internal
     */
    public function sendSignalingData(string $data): void
    {
        if ($this->callState === CallState::ENDED) {
            return;
        }
        EventLoop::queue(function () use ($data): void {
            try {
                $this->API->methodCallAsyncRead('phone.sendSignalingData', [
                    'peer' => $this->call,
                    'data' => $data,
                ]);
            } catch (Throwable $e) {
                $this->log("Could not send signaling data for $this: $e", Logger::WARNING);
            }
        });
    }

    /**
     * Handle an incoming signaling packet.
     *
     * @internal
     */
    public function onSignaling(string $data): void
    {
        $this->tgcallsController?->onSignaling($data);
    }

    /**
     * Handle a [phoneCallDiscarded](https://core.telegram.org/constructor/phoneCallDiscarded)
     * coming from the other party.
     *
     * @internal
     */
    public function onDiscarded(array $call): void
    {
        $reason = $call['reason'] ?? null;
        if ($reason !== null) {
            $this->public->discardReason = DiscardReason::tryFrom($reason['_']);
            // A call migrated to a conference carries the slug of the newly created conference,
            // see https://core.telegram.org/api/calls#migrating-to-a-conference-call.
            $this->public->conferenceSlug = $reason['slug'] ?? null;
        }
        $local = $this->public->discardReason;
        if ($local === null || $local === DiscardReason::MIGRATE_CONFERENCE_CALL) {
            // We cannot echo back a migration reason, it requires a conference slug of our own.
            $local = DiscardReason::HANGUP;
        }
        $this->discard($local);
    }

    /**
     * Called by the WebRTC engine when the connection is irrecoverably broken.
     *
     * @internal
     */
    public function onConnectionFailed(): void
    {
        if ($this->callState === CallState::ENDED) {
            return;
        }
        $this->log("The WebRTC connection of $this failed, discarding the call!", Logger::ERROR);
        $this->discard(DiscardReason::DISCONNECTED);
    }

    /**
     * Get call emojis (will return null if the call is not inited yet).
     *
     * @return ?list{string, string, string, string}
     */
    public function getVisualization(): ?array
    {
        return $this->visualization;
    }

    /**
     * Get the media state of the other party.
     *
     * @psalm-mutation-free
     */
    public function getRemoteMediaState(): ?MediaState
    {
        return $this->tgcallsController?->getRemoteMediaState();
    }

    /**
     * Discard call.
     *
     * @param int<1, 5> $rating  Call rating in stars
     * @param string    $comment Additional comment on call quality.
     */
    public function discard(DiscardReason $reason = DiscardReason::HANGUP, ?int $rating = null, ?string $comment = null): self
    {
        if ($this->callState === CallState::ENDED) {
            return $this;
        }
        $this->API->waitForInit();
        $this->API->cleanupCall($this->public->callID);
        $this->callState = CallState::ENDED;
        $this->diskJockey->discard();
        $this->skip();

        $this->log("Now closing $this");
        $this->tgcallsController?->close();
        $this->tgcallsController = null;
        $this->legacyController?->close();
        $this->legacyController = null;

        $this->log(sprintf(Lang::$current_lang['call_discarding'], $this->public->callID), Logger::VERBOSE);
        try {
            $this->API->methodCallAsyncRead('phone.discardCall', [
                'video' => $this->public->video,
                'peer' => $this->call,
                'duration' => time() - $this->public->date,
                'connection_id' => 0,
                'reason' => ['_' => $reason->value],
            ]);
        } catch (CallAlreadyAcceptedError|CallAlreadyDeclinedError) {
        }
        if ($rating !== null) {
            $this->log('Setting rating for call '.$this->public->callID.'...', Logger::VERBOSE);
            $this->API->methodCallAsyncRead('phone.setCallRating', ['peer' => $this->call, 'rating' => $rating, 'comment' => $comment]);
        }
        return $this;
    }

    #[\Override]
    public function log(string $message, int $level = Logger::NOTICE): void
    {
        $this->API->logger->logger($message, $level);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function isCallEnded(): bool
    {
        return $this->callState === CallState::ENDED;
    }

    /**
     * Set the output file or stream for the incoming media.
     *
     * A {@see RecordingFormat::Webm} or {@see RecordingFormat::Mkv} target records both the incoming
     * audio and video, muxed into a Matroska file in pure PHP; {@see RecordingFormat::Opus} keeps the
     * audio-only behaviour, writing an OGG OPUS stream. When `$format` is null it is autodetected from
     * the extension of `$file`, but only if a {@see LocalFile} was passed (a raw stream defaults to OGG).
     */
    public function setOutput(LocalFile|WritableStream $file, MediaDestination $dest = MediaDestination::Camera, ?RecordingFormat $format = null): void
    {
        if ($dest === MediaDestination::Presentation) {
            $this->tgcallsController?->setPresentationOutput($file);
            return;
        }
        $this->outputFile = $file instanceof LocalFile ? $file : null;
        $this->outputFormat = $format;
        $this->tgcallsController?->setOutput($file, $format);
        $this->legacyController?->setOutput($file);
    }

    /**
     * The playlist engine for a given media stream: the main disk jockey for {@see MediaDestination::Camera},
     * or a separate video-only one for {@see MediaDestination::Presentation} (the screencast), created and
     * attached to the WebRTC engine on first use.
     */
    private function dj(MediaDestination $dest): DjLoop
    {
        if ($dest === MediaDestination::Camera) {
            return $this->diskJockey;
        }
        if ($this->presentationDj === null) {
            $this->presentationDj = new DjLoop($this, videoOnly: true);
            Assert::true($this->presentationDj->start());
            $this->tgcallsController?->enablePresentation($this->presentationDj);
        }
        return $this->presentationDj;
    }

    /**
     * Mute or unmute the outgoing audio stream.
     */
    public function setMuted(bool $muted): void
    {
        if ($this->muted === $muted) {
            return;
        }
        $this->muted = $muted;
        if ($muted) {
            $this->pause();
        } else {
            $this->resume();
        }
        $this->tgcallsController?->sendMediaState($muted);
    }

    /**
     * Whether the outgoing audio stream is muted.
     */
    public function isMuted(): bool
    {
        return $this->muted;
    }

    /**
     * Play a file, transmitting its audio and, if it carries a transmittable one, its video.
     */
    public function play(LocalFile|RemoteUrl|ReadableStream $file, MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->dj($dest)->play($file);
    }

    /**
     * When called, skips to the next file in the playlist.
     */
    public function skip(MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->dj($dest)->skip();
    }
    /**
     * Stops playing all files, clears the main and the hold playlist.
     */
    public function stop(MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->dj($dest)->stopPlaying();
    }
    /**
     * Pauses the currently playing file.
     */
    public function pause(MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->dj($dest)->pausePlaying();
    }
    /**
     * Resumes the currently playing file.
     */
    public function resume(MediaDestination $dest = MediaDestination::Camera): void
    {
        $this->dj($dest)->resumePlaying();
    }
    /**
     * Whether the file we're currently playing is paused.
     */
    public function isPaused(MediaDestination $dest = MediaDestination::Camera): bool
    {
        return $this->dj($dest)->isAudioPaused();
    }
    /**
     * Files to play on hold.
     */
    public function playOnHold(MediaDestination $dest = MediaDestination::Camera, LocalFile|RemoteUrl|ReadableStream ...$files): void
    {
        $this->dj($dest)->playOnHold(...$files);
    }
    /**
     * Get info about the media currently being played.
     */
    public function getCurrent(MediaDestination $dest = MediaDestination::Camera): LocalFile|RemoteUrl|string|null
    {
        return $this->dj($dest)->getCurrent();
    }

    /**
     * Get call state.
     */
    public function getCallState(): CallState
    {
        return $this->callState;
    }

    /**
     * Get call representation.
     *
     * @psalm-mutation-free
     */
    #[\Override]
    public function __toString(): string
    {
        return isset($this->public) ? $this->public->__toString() : 'ended call';
    }
}
