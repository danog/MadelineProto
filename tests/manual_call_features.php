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

/*
 * Manual, INTERACTIVE test harness for every call feature landed so far. It is NOT a PHPUnit test
 * (it needs live Telegram peers), which is why it lives in tests/ without the `Test.php` suffix and
 * is never picked up by the automated suite.
 *
 * It covers the full matrix of call type × media mode:
 *
 *                | audio-only        | audio + video (camera) | screencast (presentation)
 *   -------------+-------------------+------------------------+---------------------------
 *   1:1          | 1to1 <user> audio | 1to1 <user> video      | 1to1 <user> screencast
 *   group        | group <chat> audio| group <chat> video     | group <chat> screencast
 *   conference   | conference audio  | conference video       | conference screencast
 *              (E2E, end-to-end encrypted — the SFU only ever sees ciphertext)
 *
 * For 1:1 and group calls the incoming media is also recorded (Matroska), as a check of the receive
 * path; for conference calls it prints the verification emojis and sends an encrypted in-call message.
 * Every call persists with the session: Ctrl-C DETACHES without ending the call; re-running the same
 * command re-attaches to the running call (recordings resume/append). Only the OTHER party ends it.
 *
 * Usage:
 *   php tests/manual_call_features.php check                 [video-file]
 *   php tests/manual_call_features.php 1to1  <user> <mode>   [file] [session]
 *   php tests/manual_call_features.php group <chat> <mode>   [file] [session]
 *   php tests/manual_call_features.php conference   <mode>   [file] [session]
 *   php tests/manual_call_features.php conference-join <link> <mode> [file] [session]
 *
 * <mode> is one of: audio | video | screencast
 *
 * `check` runs fully offline (no Telegram, no login). The others place/join a real call and print,
 * step by step, exactly what to click in your Telegram client. `ffprobe` is used ONLY to inspect
 * result files for verification — never to record; all recording and muxing is pure PHP.
 *
 * Set CALL_TEST_LOG=<file> to get a verbose MadelineProto log of the call in that file.
 */

use danog\MadelineProto\API;
use danog\MadelineProto\CallStream;
use danog\MadelineProto\EventHandler\Call;
use danog\MadelineProto\EventHandler\Calls\ConferenceCall;
use danog\MadelineProto\GroupCall;
use danog\MadelineProto\EventHandler\Calls\GroupCallState;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\MediaDestination;
use danog\MadelineProto\MultiCall;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tgcalls\E2E\Crypto;
use danog\MadelineProto\Tools;
use danog\MadelineProto\VoIP;
use danog\MadelineProto\VoIP\CallState;
use Webrtc\Codecs\Codec;

require __DIR__.'/../vendor/autoload.php';

/** Map a Matroska track codec ID to the SDP name our codec table uses. */
const VIDEO_CODECS = [
    'V_VP8' => 'VP8', 'V_VP9' => 'VP9',
    'V_MPEG4/ISO/AVC' => 'H264', 'V_MPEGH/ISO/HEVC' => 'H265', 'V_AV1' => 'AV1',
];

function box(string $title): void
{
    $line = str_repeat('=', max(60, strlen($title) + 4));
    fwrite(STDERR, "\n$line\n  $title\n$line\n");
}

function click(string $what): void
{
    fwrite(STDERR, "\n  👉  $what\n\n");
}

function info(string $what): void
{
    fwrite(STDERR, "  •  $what\n");
}

/**
 * Resolve a media mode to what it means for a call: whether it needs a video call/transport, which
 * stream it plays on, and a human description.
 *
 * @return array{video: bool, dest: MediaDestination, desc: string, needsVideoFile: bool}
 */
function modeInfo(string $mode): array
{
    return match ($mode) {
        'audio' => ['video' => false, 'dest' => MediaDestination::Camera, 'desc' => 'audio-only', 'needsVideoFile' => false],
        'video' => ['video' => true, 'dest' => MediaDestination::Camera, 'desc' => 'audio + camera video', 'needsVideoFile' => true],
        'screencast' => ['video' => true, 'dest' => MediaDestination::Presentation, 'desc' => 'screen-share (presentation)', 'needsVideoFile' => true],
        default => throw new \InvalidArgumentException("Unknown media mode '$mode' (expected: audio | video | screencast)"),
    };
}

/** Print the video codec + the fmtp parameters we derive from a file's bitstream (proves feature #1). */
function reportDerivedCodec(string $file): void
{
    if (!is_file($file)) {
        info("(no local file at $file to inspect)");
        return;
    }
    try {
        $m = new Matroska(new LocalFile($file), null);
    } catch (\Throwable $e) {
        info("Could not demux $file for inspection: {$e->getMessage()}");
        return;
    }
    foreach ($m->tracks as $track) {
        if (($track['type'] ?? 0) !== Matroska::TRACK_TYPE_VIDEO || !isset(VIDEO_CODECS[$track['codec']])) {
            continue;
        }
        $name = VIDEO_CODECS[$track['codec']];
        $params = Codec::fmtpFromBitstream('video/'.$name, $track['private'] ?? '');
        if ($params === [] && $name === 'VP9') {
            foreach ($m->frames as $fr) {
                if ($fr['type'] === Matroska::TRACK_TYPE_VIDEO && $fr['keyframe']) {
                    $params = Codec::fmtpFromBitstream('video/VP9', '', $fr['data']);
                    break;
                }
            }
        }
        info("Outgoing video codec: $name");
        info('Advertised fmtp (derived from THIS file): '.(json_encode($params) ?: '{}'));
        return;
    }
    info('No transmittable video track found in '.$file);
}

/** The segment files of a recording requested as `$base` (`name.mkv` → `name.<n>_<streams>.mkv`). */
function segments(string $base): array
{
    $files = glob(substr($base, 0, -4).'.*_*.mkv') ?: [];
    natsort($files);
    return array_values($files);
}

/** Inspect a recording (every segment) with ffprobe (verification only). */
function inspect(string $base): void
{
    clearstatcache();
    $files = segments($base);
    if ($files === []) {
        info('⚠️  No segment files — the peer may not have transmitted media.');
        return;
    }
    foreach ($files as $path) {
        $size = filesize($path);
        info('File: '.basename($path).' ('.number_format((int) $size).' bytes)');
        if (!shell_exec('command -v ffprobe')) {
            info('(install ffprobe to auto-verify streams & duration)');
            continue;
        }
        $streams = shell_exec('ffprobe -v error -show_entries stream=codec_type,codec_name -of csv=p=0 '.escapeshellarg($path).' 2>&1');
        info('   streams: '.str_replace("\n", ' ', trim((string) $streams ?: 'none')));
        $dur = shell_exec('ffprobe -v error -show_entries format=duration -of csv=p=0 '.escapeshellarg($path).' 2>&1');
        info('   duration: '.trim((string) $dur ?: 'N/A'));
    }
}

function startApi(string $session): API
{
    $settings = new Settings;
    $settings->getLogger()->setLevel(Logger::LEVEL_ERROR);
    // CALL_TEST_LOG=<file> writes a verbose MadelineProto log there instead, for debugging a call.
    $logFile = getenv('CALL_TEST_LOG');
    if (is_string($logFile) && $logFile !== '') {
        $settings->getLogger()->setType(Logger::FILE_LOGGER)->setExtra($logFile)->setLevel(Logger::LEVEL_VERBOSE);
    }
    $API = new API($session, $settings);
    $API->start();
    return $API;
}

/** Poll a call's state reactively until it matches, or the deadline passes. */
function waitFor(callable $isDone, float $deadline, float $step = 0.5): void
{
    while (microtime(true) < $deadline && !$isDone()) {
        Tools::sleep($step);
    }
}

/** Play the file into the right stream for the mode, warning if it lacks video when one is needed. */
function transmit(Call $call, array $m, string $file): void
{
    if ($m['needsVideoFile'] && !hasVideoTrack($file)) {
        info('⚠️  '.basename($file).' has no video track — '.$m['desc'].' needs one. Pass a WebM/MKV with video.');
    }
    info('Transmitting '.basename($file).' as '.$m['desc'].' (looped on hold, so it keeps playing until the call ends).');
    $call->play(new LocalFile($file), $m['dest']);
    $call->playOnHold($m['dest'], new LocalFile($file));
}

/** Whether a file has a transmittable video track (best-effort demux). */
function hasVideoTrack(string $file): bool
{
    if (!is_file($file)) {
        return false;
    }
    try {
        $m = new Matroska(new LocalFile($file), null);
    } catch (\Throwable) {
        return false;
    }
    foreach ($m->tracks as $track) {
        if (($track['type'] ?? 0) === Matroska::TRACK_TYPE_VIDEO && isset(VIDEO_CODECS[$track['codec']])) {
            return true;
        }
    }
    return false;
}

$mode    = $argv[1] ?? 'help';

switch ($mode) {
    case 'check':
        $video = $argv[2] ?? (__DIR__.'/../new.webm');
        box('OFFLINE SELF-CHECK (no Telegram needed)');
        $impl = static fn (string $class, string $iface): string => (new ReflectionClass($class))->implementsInterface($iface) ? 'yes ✅' : 'NO ❌';
        info('VoIP implements Call:                '.$impl(VoIP::class, Call::class).' (1:1, not multi-party)');
        info('GroupCall implements Call+MultiCall: '.$impl(GroupCall::class, Call::class).' / '.$impl(GroupCall::class, MultiCall::class));
        info('ConferenceCall Call+MultiCall:       '.$impl(ConferenceCall::class, Call::class).' / '.$impl(ConferenceCall::class, MultiCall::class));
        info('VoIP is NOT MultiCall (1:1):         '.((new ReflectionClass(VoIP::class))->implementsInterface(MultiCall::class) ? 'NO ❌ (should not be)' : 'correct ✅'));
        info('E2E crypto available:           '.(Crypto::available() ? 'yes ✅ ('.(extension_loaded('sodium') ? 'sodium' : 'phpseclib fallback').')' : 'NO ❌'));
        fwrite(STDERR, "\n");
        info('Codec parameter derivation (feature #1):');
        reportDerivedCodec($video);
        box('SELF-CHECK DONE — nothing to click.');
        break;

    case '1to1':
    case '1t1':
        $arg     = $argv[2] ?? null;
        $media   = $argv[3] ?? null;
        $file    = $argv[4] ?? (__DIR__.'/../new.webm');
        $session = $argv[5] ?? 'fuzz_user.madeline';
        if ($arg === null || $media === null) {
            fwrite(STDERR, "Usage: php tests/manual_call_features.php 1to1 <user> <audio|video|screencast> [file] [session]\n");
            exit(2);
        }
        $m = modeInfo($media);
        $out = __DIR__.'/../incoming_1to1_'.$media.'.mkv';

        box("TEST: 1:1 CALL — $m[desc]");
        if ($m['needsVideoFile']) {
            reportDerivedCodec($file);
        }

        $API = startApi($session);
        $peerId = $API->getId($arg);
        $existing = $peerId !== null ? $API->getCallByPeer($peerId) : null;
        $running = $existing !== null && $existing->getCallState() !== CallState::ENDED;

        if ($running) {
            $call = $existing;
            info('Found an existing call (state: '.$call->getCallState()->name.') — attaching, not restarting.');
            click('Nothing to do — the call is still up. Ctrl-C detaches (call keeps running); the OTHER account hangs up to end it.');
        } else {
            foreach (segments($out) as $old) {
                unlink($old);
            }
            info("Placing a NEW ".($m['video'] ? 'video' : 'audio')." call to $arg …");
            $call = $API->requestCall($arg, video: $m['video']);
            transmit($call, $m, $file);
            if ($media === 'screencast') {
                // A screencast is video only (like Telegram's own screen sharing, the audio of a 1:1
                // call is the mic stream): play the file on the main stream too, for its audio. Its
                // video is not transmitted as camera while the screencast is up: as in tgcalls, a
                // screencast replaces the camera, since the peer has a single incoming video.
                info('Also playing '.basename($file).' on the main stream, so that its audio is heard.');
                $call->play(new LocalFile($file), MediaDestination::Camera);
            }
            $ask = match ($media) {
                'audio' => 'ANSWER the call and UNMUTE your mic. You should HEAR our audio file playing.',
                'video' => 'ANSWER the call and turn your CAMERA ON. You should SEE our video file playing.',
                'screencast' => 'ANSWER the call and turn your CAMERA ON. You should SEE our screen-share (the video file) as a shared screen and HEAR its audio.',
            };
            click("On the OTHER account, $ask");
            info('Waiting up to 120s for the call to connect…');
            waitFor(static fn (): bool => $call->getCallState() !== CallState::REQUESTED, microtime(true) + 120);
            if ($call->getCallState() !== CallState::RUNNING) {
                info('Call not answered (state: '.$call->getCallState()->name.'). Leaving it as-is (not discarding).');
                exit(1);
            }
            info('Connected ✅');
            info('Recording the incoming camera/mic into '.$out);
            $streams = $call->setOutput(new LocalFile($out));
            info('The peer currently sends '.CallStream::describe($streams).': the file keeps exactly these tracks, a stream turned off just pauses; only a codec change or the end of the call finishes it.');
            $emojis = $call->getVisualization();
            if ($emojis !== null) {
                info('Call verification emojis (compare with the peer): '.implode(' ', $emojis));
            }
        }

        info('Monitoring. Ctrl-C detaches WITHOUT ending it; re-run to re-attach (recording appends).');
        $lastReport = 0.0;
        while ($call->getCallState() !== CallState::ENDED) {
            if (microtime(true) - $lastReport > 5) {
                clearstatcache();
                $files = segments($out);
                $last = $files === [] ? null : end($files);
                info('… running ('.$call->getCallState()->name.'); '.count($files).' segment(s)'.($last !== null ? ', latest '.basename($last).' = '.number_format((int) filesize($last)).' bytes' : ''));
                $lastReport = microtime(true);
            }
            Tools::sleep(1.0);
        }
        Tools::sleep(1.5);
        box('1:1 RESULT (peer hung up)');
        inspect($out);
        $expect = match ($media) {
            'audio' => 'Expect audio-only segments, plus audio+video ones for whenever you turned the camera on.',
            'video' => 'Expect audio+video segments (and audio-only ones for whenever the camera was off).',
            'screencast' => 'Expect one segment per combination of what you sent: e.g. 0_audio,video, then 1_audio,screen while you shared the screen, and so on.',
        };
        info($expect.' If you Ctrl-C+re-ran, the recording continued across restarts.');
        break;

    case 'group':
        $arg     = $argv[2] ?? null;
        $media   = $argv[3] ?? null;
        $file    = $argv[4] ?? (__DIR__.'/../new.webm');
        $session = $argv[5] ?? 'fuzz_user.madeline';
        if ($arg === null || $media === null) {
            fwrite(STDERR, "Usage: php tests/manual_call_features.php group <chat> <audio|video|screencast> [file] [session]\n");
            exit(2);
        }
        $m = modeInfo($media);
        $dir = __DIR__.'/../group_recordings_'.$media;

        box("TEST: GROUP CALL — $m[desc] + folder recording (one .mkv per participant)");
        if ($m['needsVideoFile']) {
            reportDerivedCodec($file);
        }
        $API = startApi($session);
        $existing = $API->getGroupCall($arg);
        $joined = $existing !== null && $existing->getCallState() === GroupCallState::JOINED;

        if ($joined) {
            $call = $existing;
            info('Already joined — attaching, not rejoining. Per-participant recordings resumed in '.$dir);
            if ($call->getCurrent($m['dest']) === null) {
                info('Nothing is playing any more: starting the transmission again.');
                transmit($call, $m, $file);
            }
            click('Nothing to do. Ctrl-C detaches (call keeps running); recordings keep growing.');
        } else {
            /** @var GroupCall $call */
            $call = $API->joinGroupCall($arg);
            click('Open the SAME group call on one or more OTHER accounts, JOIN, and transmit ('.$m['desc'].').');
            transmit($call, $m, $file);
            info('Recording every transmitting participant into '.$dir.'/<peerId>.<n>_<streams>.mkv');
            $call->setOutputFolder(new LocalDirectory($dir));
        }

        // A freshly re-attached call may still be JOINING; wait until it is actually JOINED.
        info('Waiting for the call to be fully joined…');
        waitFor(static fn (): bool => $call->getCallState() !== GroupCallState::JOINING, microtime(true) + 30);

        info('Monitoring. Ctrl-C detaches WITHOUT leaving; re-run to re-attach (recordings resume/append).');
        $lastReport = 0.0;
        while (in_array($call->getCallState(), [GroupCallState::JOINED, GroupCallState::JOINING], true)) {
            if (microtime(true) - $lastReport > 5) {
                $files = glob($dir.'/*.mkv') ?: [];
                info('… joined; '.count($files).' segment file(s) in '.$dir);
                $lastReport = microtime(true);
            }
            Tools::sleep(1.0);
        }

        box('GROUP RESULT (left)');
        $files = glob($dir.'/*.mkv') ?: [];
        if ($files === []) {
            info('⚠️  No per-participant files — did anyone else actually transmit?');
        }
        foreach ($files as $f) {
            info(basename($f).' — '.number_format((int) filesize($f)).' bytes');
        }
        info('Expect <id>.<n>_<streams>.mkv files per participant who transmitted (excluding ourselves), one per change of what they sent.');
        break;

    case 'conference':
        $media   = $argv[2] ?? null;
        $file    = $argv[3] ?? (__DIR__.'/../new.webm');
        $session = $argv[4] ?? 'fuzz_user.madeline';
        if ($media === null) {
            fwrite(STDERR, "Usage: php tests/manual_call_features.php conference <audio|video|screencast> [file] [session]\n");
            exit(2);
        }
        $m = modeInfo($media);

        box("TEST: E2E CONFERENCE CALL (creator) — $m[desc]");
        info('End-to-end encrypted: the server/SFU only ever sees ciphertext. Crypto backend: '.(extension_loaded('sodium') ? 'sodium' : 'phpseclib fallback'));
        if ($m['needsVideoFile']) {
            reportDerivedCodec($file);
        }
        $API = startApi($session);
        info('Creating a new end-to-end encrypted conference call…');
        $call = $API->createConferenceCall();
        transmit($call, $m, $file);
        $link = $call->exportInvite();
        box('SHARE THIS LINK WITH THE OTHER ACCOUNT TO JOIN');
        info("Conference link: $link");
        info('Open it in any official client, or run on another MadelineProto session:');
        info("  php tests/manual_call_features.php conference-join $link $media [file] [other-session]");
        runConference($call, $media);
        break;

    case 'conference-join':
        $link    = $argv[2] ?? null;
        $media   = $argv[3] ?? null;
        $file    = $argv[4] ?? (__DIR__.'/../new.webm');
        $session = $argv[5] ?? 'fuzz_user.madeline';
        if ($link === null || $media === null) {
            fwrite(STDERR, "Usage: php tests/manual_call_features.php conference-join <link> <audio|video|screencast> [file] [session]\n");
            exit(2);
        }
        $m = modeInfo($media);
        $slug = conferenceSlug($link);

        box("TEST: E2E CONFERENCE CALL (joiner) — $m[desc]");
        $API = startApi($session);
        $existing = $API->getConferenceCallBySlug($slug);
        if ($existing === null) {
            fwrite(STDERR, "The conference link $link is not valid (any more).\n");
            exit(1);
        }
        if ($existing->isJoined()) {
            $call = $existing;
            info('Already in this conference — attaching, not rejoining.');
            info('Currently playing: '.json_encode($call->getCurrent($m['dest'])));
            if ($call->getCurrent($m['dest']) === null) {
                info('Nothing is playing any more: starting the transmission again.');
                transmit($call, $m, $file);
            }
        } else {
            info('Joining the end-to-end encrypted conference…');
            $call = $API->joinConferenceCallBySlug($slug);
            transmit($call, $m, $file);
        }
        runConference($call, $media);
        break;

    default:
        fwrite(STDERR, <<<TXT
            MadelineProto call-features manual test — full matrix (call type × media mode).

              php tests/manual_call_features.php check [video-file]
                  Offline checks: Call interfaces + E2E crypto + codec-param derivation. No Telegram.

              php tests/manual_call_features.php 1to1  <user> <mode> [file] [session]
              php tests/manual_call_features.php group <chat> <mode> [file] [session]
              php tests/manual_call_features.php conference      <mode> [file] [session]
              php tests/manual_call_features.php conference-join <link> <mode> [file] [session]

              <mode> = audio | video | screencast

            1:1 and group record the incoming media (Matroska); conference is end-to-end encrypted and
            prints verification emojis + sends an encrypted in-call message. Ctrl-C detaches without
            ending the call; re-run the same command to re-attach.

            Defaults: file=new.webm, session=fuzz_user.madeline.

            TXT);
        break;
}

/**
 * Drive a conference call after it is up: transmit, compute + print the verification emojis, send an
 * encrypted in-call message, and monitor until Ctrl-C (which detaches without leaving).
 */
/**
 * The slug of a conference link: `https://t.me/call/<slug>`, `t.me/call/<slug>`, `tg://call?slug=<slug>`,
 * or a bare slug.
 */
function conferenceSlug(string $link): string
{
    if (preg_match('~(?:t\.me/call/|[?&]slug=)([A-Za-z0-9_-]+)~', $link, $m)) {
        return $m[1];
    }
    return trim($link, "/ \t\n");
}

function runConference(ConferenceCall $call, string $media): void
{
    $ask = match ($media) {
        'audio' => 'you HEAR our audio',
        'video' => 'you SEE our camera video',
        'screencast' => 'you SEE our shared screen',
    };
    click("On the OTHER account, JOIN the conference and confirm $ask, then compare the verification emojis below.");

    info('Verification (commit/reveal on subchain 1) runs automatically; waiting up to 30s for every participant to reveal their nonce…');
    waitFor(static fn (): bool => $call->getVisualization() !== null, microtime(true) + 30);
    $emojis = $call->getVisualization();
    info('Verification emojis (MUST match on every participant): '.($emojis !== null ? implode(' ', $emojis) : '(not enough participants revealed yet)'));

    try {
        $call->sendMessage('MadelineProto E2E conference test message '.date('H:i:s'));
        info('Sent an end-to-end encrypted in-call message ✅');
    } catch (\Throwable $e) {
        info('Could not send an encrypted message yet: '.$e->getMessage());
    }

    info('Monitoring. Ctrl-C detaches WITHOUT leaving; re-run to re-attach.'.($call->isSharingScreen() ? ' (screen-share active)' : ''));
    $lastReport = 0.0;
    while ($call->isJoined()) {
        if (microtime(true) - $lastReport > 5) {
            $emojis = $call->getVisualization();
            info('… in conference; emojis: '.($emojis !== null ? implode(' ', $emojis) : 'pending'));
            $lastReport = microtime(true);
        }
        Tools::sleep(1.0);
    }
    box('CONFERENCE ENDED');
    info('Expect: the peer saw/heard the '.$media.' stream (all E2E-encrypted) and the emojis matched.');
}
