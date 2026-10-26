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

namespace danog\MadelineProto\Tgcalls;

use Amp\ByteStream\ReadableStream;
use Amp\ByteStream\WritableStream;
use Amp\Cancellation;
use Amp\Sync\LocalMutex;
use AssertionError;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Calls\CallStreams;
use danog\MadelineProto\EventHandler\Calls\PrivateCall;
use danog\MadelineProto\Lang;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\Ogg;
use danog\MadelineProto\RecordingEvent;
use danog\MadelineProto\RecordingFormat;
use danog\MadelineProto\RemoteUrl;
use danog\MadelineProto\RestoresDeclaredProperties;
use danog\MadelineProto\RPCError\CallAlreadyAcceptedError;
use danog\MadelineProto\RPCError\CallAlreadyDeclinedError;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Tools;
use danog\MadelineProto\VoIP\CallState;
use danog\MadelineProto\VoIP\DiscardReason;
use danog\MadelineProto\VoIP\MediaState;
use danog\MadelineProto\VoIP\SignalingProtocolVersion;
use phpseclib4\Math\BigInteger;
use Revolt\EventLoop;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * @internal
 * @psalm-import-type StreamMask from \danog\MadelineProto\CallStream
 */
final class PrivateCallController implements CallControllerInterface
{
    use RestoresDeclaredProperties;
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

    /** The recording requested before the engine existed (or to re-arm the legacy engine after a restart): a file, or null. */
    private ?LocalFile $outputFile = null;
    /** The recording requested before the engine existed: a folder, or null. */
    private ?LocalDirectory $outputFolder = null;

    private ?RecordingFormat $outputFormat = null;
    /**
     * The {@see CallStream} flags requested for the pending file recording, or null for every available stream.
     * @var ?StreamMask
     */
    private ?int $outputStreams = null;

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
        $this->call = $call;
        if ($this->public->outgoing) {
            $this->callState = CallState::REQUESTED;
            Assert::true($this->diskJockey->start());
        } else {
            // Every session of the account gets the incoming call, and only the one that accepts it is
            // in it: the disk jockey is only started once we accept it (its playlist works before).
            $this->callState = CallState::INCOMING;
        }
        $this->authMutex = new LocalMutex;
    }

    /** Whether the call was restored from a session, and is yet to be resumed by {@see self::resumeAfterRestart()}. */
    private bool $resumePending = false;

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
        unset($result['authMutex'], $result['resumePending']);

        return $result;
    }

    /**
     * Resume the call after the process restarted.
     *
     * Called by {@see MTProto::wakeup()} once the MTProto session is reset and connected, never from
     * __unserialize(): by then the whole call graph is restored and callState is known-good, and the
     * requests made while resuming are not lost to the reset of the session.
     *
     * @internal
     */
    public function resumeAfterRestart(): void
    {
        if (!$this->resumePending) {
            return;
        }
        $this->resumePending = false;
        if ($this->callState !== CallState::RUNNING) {
            return;
        }
        // The WebRTC/libtgvoip engine restored itself and its transport resumes on its own. Restart
        // the demuxer/reader, the playback producers and the (file-backed) recorders, which were all
        // left dormant during deserialization.
        $this->diskJockey->resumeReader();
        $this->tgcallsController?->resume();
        // The legacy (libtgvoip) engine still re-arms its recorder from the remembered output.
        if ($this->legacyController !== null) {
            $this->applyPendingOutput();
        }
        $this->log("Resumed $this after a restart of the process.");
    }
    /**
     * Wakeup function.
     */
    public function __unserialize(array $data): void
    {
        $this->authMutex = new LocalMutex;
        $this->restoreDeclaredProperties($data);
        // Defensively drop a call that came back without its full essential state (a half-constructed
        // call that slipped into the session): force it ENDED and start nothing. Because no subsystem
        // starts async work during deserialization any more (that is all deferred to resumeAfterRestart()),
        // this is the single point that decides whether a restored call comes back to life at all.
        if (!isset($this->callState) || !isset($this->public) || !isset($this->call)
            || $this->callState === CallState::ENDED
            // A running call always has an engine (it is created in the same synchronous step that
            // starts the call). One without comes from the releases that ran libtgvoip inside this
            // class: its state (voipState, sockets, timestamps, packet queue...) has no counterpart in
            // the engines and was just dropped, so the call cannot be resumed.
            || ($this->callState === CallState::RUNNING && $this->tgcallsController === null && $this->legacyController === null)
        ) {
            $this->callState = CallState::ENDED;
            return;
        }
        if (!isset($this->API->logger)) {
            $this->API->setupLogger();
        }
        $this->diskJockey ??= new DjLoop($this);
        if ($this->callState !== CallState::INCOMING) {
            Assert::true($this->diskJockey->start());
        }
        // Resumed by MTProto::wakeup() once the MTProto session is reset and connected, see resumeAfterRestart():
        // deserialization must stay synchronous, and resuming reopens files, starts loops and makes requests.
        $this->resumePending = true;
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
                $this->log(\sprintf(Lang::$current_lang['call_error_2'], $this->public->callID));
                return false;
            }
            $this->log(\sprintf(Lang::$current_lang['call_confirming'], $this->public->otherID), Logger::VERBOSE);
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
                $this->log(\sprintf(Lang::$current_lang['call_already_accepted'], $params['id']));
                return true;
            } catch (CallAlreadyDeclinedError) {
                // Already ended by the other party: nothing to discard.
                $this->log(Lang::$current_lang['call_already_declined']);
                $this->end();
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
            // An outgoing call has nothing to accept: we are already in it.
            if ($this->public->outgoing || $this->callState === CallState::RUNNING || $this->callState === CallState::ENDED) {
                return $this;
            }
            Assert::eq($this->callState->name, CallState::INCOMING->name);

            $this->log(\sprintf(Lang::$current_lang['accepting_call'], $this->public->otherID), Logger::VERBOSE);
            $dh_config = $this->API->getDhConfig($cancellation);
            if ($this->callState !== CallState::INCOMING) {
                // Ended meanwhile: discarded, or expired, see expireAfterRestart().
                return $this;
            }
            $this->log('Generating b...', Logger::VERBOSE);
            $b = BigInteger::randomRange(Magic::$two, $dh_config['p']->subtract(Magic::$two));
            $g_b = $dh_config['g']->powMod($b, $dh_config['p']);
            Crypt::checkG($g_b, $dh_config['p']);

            $this->callState = CallState::ACCEPTED;
            // Persisted along with the state: if the process restarts while phone.acceptCall is in flight,
            // the server may still accept the call, and the phoneCall that follows completes it with b.
            $this->call['b'] = $b;
            $this->diskJockey->start();
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
                // Accepted by another session of the account, which is in the call: leave it alone.
                $this->log(\sprintf(Lang::$current_lang['call_already_accepted'], $this->public->callID));
                $this->end();
                return $this;
            } catch (CallAlreadyDeclinedError) {
                // Already ended (by the caller, or declined by another session): nothing to discard.
                $this->log(Lang::$current_lang['call_already_declined']);
                $this->end();
                return $this;
            }

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
            if ($this->callState !== CallState::ACCEPTED || !isset($this->call['b'])) {
                return false;
            }

            $this->log(\sprintf(Lang::$current_lang['call_completing'], $this->public->otherID), Logger::VERBOSE);
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
    private static function computeVisualization(#[\SensitiveParameter] string $key, string $g_a): array
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
            $this->diskJockey->setFixedOggGrid();
            $this->legacyController = new LegacyController(
                $this,
                $this->authKey,
                $this->public->outgoing,
                $this->diskJockey,
                $call['connections'] ?? [],
            );
            $this->applyPendingOutput();
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
        $this->applyPendingOutput();
        // A presentation playlist requested before the engine existed is attached now, before the
        // first offer goes out, so that the screencast is in it from the start.
        if ($this->presentationDj !== null) {
            $this->tgcallsController->enablePresentation($this->presentationDj);
        }
        $this->tgcallsController->start();
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
        // The call is already over: end it locally without echoing a phone.discardCall, as TDLib and
        // tdesktop do. An incoming call is also discarded for the other sessions of the account when one
        // of them accepts it (tdesktop's EndedByOtherDevice): echoing the discard from them would hang up
        // the call of the session that is in it.
        $this->end();
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
    public function discard(DiscardReason $reason = DiscardReason::HANGUP, ?int $rating = null, ?string $comment = null, ?string $conferenceSlug = null): self
    {
        if ($this->callState === CallState::ENDED) {
            return $this;
        }
        $this->API->waitForInit();
        $this->end();

        $this->log(\sprintf(Lang::$current_lang['call_discarding'], $this->public->callID), Logger::VERBOSE);
        try {
            $this->API->methodCallAsyncRead('phone.discardCall', [
                'video' => $this->public->video,
                'peer' => $this->call,
                'duration' => time() - $this->public->date,
                'connection_id' => 0,
                'reason' => ['_' => $reason->value] + ($conferenceSlug !== null ? ['slug' => $conferenceSlug] : []),
            ]);
        } catch (CallAlreadyAcceptedError|CallAlreadyDeclinedError) {
        }
        if ($rating !== null) {
            $this->log('Setting rating for call '.$this->public->callID.'...', Logger::VERBOSE);
            $this->API->methodCallAsyncRead('phone.setCallRating', ['peer' => $this->call, 'rating' => $rating, 'comment' => $comment]);
        }
        return $this;
    }

    /**
     * End the call locally, without making any request.
     */
    private function end(): void
    {
        if ($this->callState === CallState::ENDED) {
            return;
        }
        $this->API->cleanupCall($this->public->callID);
        $this->callState = CallState::ENDED;
        $this->diskJockey->discard();
        $this->skip();

        $this->log("Now closing $this");
        $this->tgcallsController?->close();
        $this->tgcallsController = null;
        $this->legacyController?->close();
        $this->legacyController = null;
    }

    /**
     * End an incoming call we never answered once it can no longer be ringing, without making any
     * request: it was answered or declined by another session of the account, or ended while this
     * one was not running (the end of a call is only notified to running sessions).
     *
     * @internal
     *
     * @param int $ringTimeout How long an incoming call rings at most, in seconds.
     *
     * @return bool Whether the call was ended.
     */
    public function forgetIfUnanswered(int $ringTimeout): bool
    {
        if (!$this->isUnanswered() || time() - $this->public->date <= $ringTimeout) {
            return false;
        }
        $this->log("Forgetting $this, which was not answered by this session");
        $this->end();
        return true;
    }

    /**
     * @psalm-mutation-free
     */
    private function isUnanswered(): bool
    {
        return $this->callState === CallState::INCOMING
            // Left behind by a failed phone.acceptCall of older versions (accept() now sets b first).
            || ($this->callState === CallState::ACCEPTED && !isset($this->call['b']));
    }

    /**
     * Expire a call restored from the session whose end we may have missed (the updates of a call are
     * only sent to running sessions), without making any request before it times out, see
     * {@see MTProto::wakeup()}.
     *
     * @internal
     *
     * @param int $ringTimeout    How long an incoming call rings at most, in seconds.
     * @param int $receiveTimeout How long the key exchange of an accepted call takes at most, in seconds.
     */
    public function expireAfterRestart(int $ringTimeout, int $receiveTimeout): void
    {
        if ($this->forgetIfUnanswered($ringTimeout)) {
            return;
        }
        if ($this->isUnanswered()) {
            // Still ringing: forget it once it can no longer ring, unless we answer it.
            $delay = max(0, $this->public->date + $ringTimeout - time()) + 1;
            EventLoop::unreference(EventLoop::delay($delay, function () use ($ringTimeout): void {
                $this->forgetIfUnanswered($ringTimeout);
            }));
        } elseif ($this->callState === CallState::ACCEPTED) {
            // Restarted between our phone.acceptCall and the phoneCall that completes the key exchange,
            // which may have been sent while we were not running: as TDLib, give up once
            // call_receive_timeout_ms expires. We accepted the call: discard it.
            EventLoop::unreference(EventLoop::delay($receiveTimeout, function (): void {
                if ($this->callState !== CallState::ACCEPTED) {
                    return;
                }
                $this->log("The key exchange of $this did not complete, discarding it", Logger::WARNING);
                try {
                    $this->discard(DiscardReason::DISCONNECTED);
                } catch (Throwable $e) {
                    $this->log("Could not discard $this: $e", Logger::ERROR);
                }
            }));
        }
    }

    #[\Override]
    public function log(string $message, int $level = Logger::NOTICE): void
    {
        $this->API->logger->logger($message, $level);
    }

    #[\Override]
    public function getVoIPSettings(): \danog\MadelineProto\Settings\VoIP
    {
        return $this->API->getSettings()->getVoip();
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
     * Record the incoming media into one file (or stream) with a fixed set of tracks, see
     * {@see Controller::setOutput()}. Before the engine exists (the call is still connecting) the
     * request is remembered and applied once it starts; the legacy libtgvoip engine records audio only.
     *
     * @param ?StreamMask $streams The {@see CallStream} flags to record, or null for every stream the peer currently sends.
     *
     * @return StreamMask The streams the peer currently sends, as {@see CallStream} flags (0 while unknown).
     */
    public function setOutput(LocalFile|WritableStream $file, ?RecordingFormat $format = null, ?int $streams = null): int
    {
        if ($streams !== null) {
            CallStream::validate($streams);
        }
        if ($this->legacyController !== null) {
            if ($streams !== null && ($streams & ~CallStream::AUDIO) !== 0) {
                throw new \InvalidArgumentException('The legacy libtgvoip engine carries audio only: choose CallStream::AUDIO.');
            }
            if ($format !== null && $format->isMatroska()) {
                throw new \InvalidArgumentException('The legacy libtgvoip engine records audio only, to OGG OPUS: use RecordingFormat::Opus.');
            }
        }
        $this->outputFile = $file instanceof LocalFile ? $file : null;
        $this->outputFolder = null;
        $this->outputFormat = $format;
        $this->outputStreams = $streams;
        if ($this->tgcallsController !== null) {
            return $this->tgcallsController->setOutput($file, $format, $streams);
        }
        if ($this->legacyController !== null) {
            $this->legacyController->setOutput($file);
            return CallStream::AUDIO;
        }
        return 0;
    }

    /**
     * Record the incoming media into a directory, as a numbered series of `<n>_<streams>.mkv` files,
     * one per combination of streams the peer sends, see {@see Controller::setOutputFolder()}.
     */
    public function setOutputFolder(LocalDirectory $dir, ?RecordingFormat $format = null): void
    {
        $this->outputFile = null;
        $this->outputFolder = $dir;
        $this->outputFormat = $format;
        $this->outputStreams = null;
        if ($this->tgcallsController !== null) {
            $this->tgcallsController->setOutputFolder($dir, $format);
        } elseif ($this->legacyController !== null) {
            $this->legacyController->setOutput(self::legacyFolderFile($dir));
        }
    }

    /**
     * Hand the remembered recording request to the engine that just started (or came back).
     */
    private function applyPendingOutput(): void
    {
        try {
            if ($this->outputFolder !== null) {
                $this->tgcallsController?->setOutputFolder($this->outputFolder, $this->outputFormat);
                $this->legacyController?->setOutput(self::legacyFolderFile($this->outputFolder));
            } elseif ($this->outputFile !== null) {
                $this->tgcallsController?->setOutput($this->outputFile, $this->outputFormat, $this->outputStreams);
                $this->legacyController?->setOutput($this->outputFile);
            }
        } catch (Throwable $e) {
            $this->log("Could not start the recording of $this: $e", Logger::ERROR);
        }
    }

    /**
     * The OGG file the legacy audio-only engine records into when a folder was requested.
     */
    private static function legacyFolderFile(LocalDirectory $dir): LocalFile
    {
        return new LocalFile($dir->create().'/audio.ogg');
    }

    /**
     * The peer's streams or codecs changed, or a recording of them ended: emit a
     * {@see CallStreams} update.
     *
     * @param array<int, string> $codecs By {@see CallStream} flag.
     *
     * @internal
     */
    public function onStreamsChanged(int $streams, array $codecs, ?RecordingEvent $recording, ?LocalFile $file): void
    {
        CallStreamsDispatcher::dispatch($this->API, $this->public, $this->public->otherID, $streams, $codecs, $recording, $file);
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
     * Start sharing a screen: bring up the presentation (screencast) playlist and attach it to the
     * WebRTC engine as a separate outgoing video stream. Idempotent; the screencast is advertised to
     * the peer as soon as a file with video plays on it.
     */
    public function enablePresentation(): void
    {
        $this->dj(MediaDestination::Presentation);
    }

    /**
     * Stop sharing the screen: stop the presentation playlist, which makes the engine advertise the
     * screencast as inactive. The playlist stays attached, ready for the next presentation playback.
     */
    public function disablePresentation(): void
    {
        $this->presentationDj?->stopPlaying();
    }

    /**
     * Whether a screencast is currently being transmitted.
     *
     * @psalm-mutation-free
     */
    public function isSharingScreen(): bool
    {
        return $this->tgcallsController?->isScreencastActive() ?? false;
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
        $this->validateForLegacyEngine($file);
        $this->dj($dest)->play($file);
    }

    /**
     * The libtgvoip engine of a legacy (2.x) call can only play OGG OPUS encoded the way MadelineProto
     * or @libtgvoipbot do, unless realtime conversion is available; check that up front for it. The
     * WebRTC engine demuxes WebM/MKV and OGG itself and needs no such check.
     */
    private function validateForLegacyEngine(LocalFile|RemoteUrl|ReadableStream $file): void
    {
        if ($this->legacyController === null || Tools::canConvertOgg()) {
            return;
        }
        if ($file instanceof ReadableStream) {
            throw new AssertionError('The passed stream was not generated by MadelineProto or @libtgvoipbot, please pre-convert it using @libtgvoipbot or install FFI and ffmpeg to perform realtime conversion!');
        }
        Ogg::validateOgg($file);
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
        foreach ($files as $file) {
            $this->validateForLegacyEngine($file);
        }
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
