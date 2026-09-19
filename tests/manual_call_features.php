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
 * Manual, INTERACTIVE test harness for the call features landed so far. It is NOT a PHPUnit test
 * (it needs live Telegram peers), which is why it lives in tests/ without the `Test.php` suffix and
 * is never picked up by the automated suite.
 *
 * It exercises the features that are callable through the public API today:
 *   1. codec fmtp derived from the played file's bitstream (1:1 video call)
 *   2. incoming 1:1 recording as a Matroska file, WITH the new total-duration element
 *   3. group-call folder recording: one .ogg per transmitting participant
 *   4. the common Call interface (offline self-check)
 *
 * NOT yet covered (not wired to the public API yet): 1:1 / group presentation (screencast).
 *
 * Usage:
 *   php tests/manual_call_features.php check   [video-file]
 *   php tests/manual_call_features.php 1to1    <user>  [video-file] [seconds] [session]
 *   php tests/manual_call_features.php group   <chat>  [video-file] [seconds] [session]
 *
 * `check` runs fully offline (no Telegram, no login). `1to1` and `group` place/join a real call and
 * print, step by step, exactly what to click in your Telegram client.
 *
 * `ffprobe` is used ONLY to inspect the resulting files for verification — never to record; all
 * recording and muxing is pure PHP.
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Call;
use danog\MadelineProto\GroupCall;
use danog\MadelineProto\LocalDirectory;
use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Matroska;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Tools;
use danog\MadelineProto\VoIP;
use danog\MadelineProto\VoIP\CallState;
use Webrtc\Codecs\Codec;

require __DIR__.'/../vendor/autoload.php';

/** Map a Matroska track codec ID to the SDP name our codec table uses. */
const VIDEO_CODECS = [
    'V_VP8' => 'VP8', 'V_VP9' => 'VP9',
    'V_MPEG4/ISO/AVC' => 'H264', 'V_AV1' => 'AV1',
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
    $firstKeyframe = null;
    foreach ($m->tracks as $track) {
        if (($track['type'] ?? 0) !== Matroska::TRACK_TYPE_VIDEO || !isset(VIDEO_CODECS[$track['codec']])) {
            continue;
        }
        $name = VIDEO_CODECS[$track['codec']];
        $params = Codec::fmtpFromBitstream('video/'.$name, $track['private'] ?? '');
        if ($params === [] && $name === 'VP9') {
            // VP9 with no configuration record: read the profile from the first keyframe.
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

/** Inspect a recording with ffprobe (verification only). */
function inspect(string $path): void
{
    clearstatcache();
    $size = is_file($path) ? filesize($path) : 0;
    info('File: '.$path.' ('.number_format((int) $size).' bytes)');
    if ($size < 1024) {
        info('⚠️  File is suspiciously small — the peer may not have transmitted media.');
    }
    if (!shell_exec('command -v ffprobe')) {
        info('(install ffprobe to auto-verify streams & duration)');
        return;
    }
    $streams = shell_exec('ffprobe -v error -show_entries stream=codec_type,codec_name -of csv=p=0 '.escapeshellarg($path).' 2>&1');
    info('Streams: '.trim((string) $streams ?: 'none'));
    $dur = shell_exec('ffprobe -v error -show_entries stream=duration -of csv=p=0 '.escapeshellarg($path).' 2>&1');
    info('Track duration(s): '.trim((string) $dur ?: 'N/A'));
}

function startApi(string $session): API
{
    $settings = new Settings;
    $settings->getLogger()->setLevel(Logger::LEVEL_ERROR);
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

$mode    = $argv[1] ?? 'help';
$arg     = $argv[2] ?? null;
$video   = $argv[3] ?? (__DIR__.'/../av1.webm');
$seconds = (int) ($argv[4] ?? 25);
$session = $argv[5] ?? 'fuzz_user.madeline';

switch ($mode) {
    case 'check':
        box('OFFLINE SELF-CHECK (no Telegram needed)');
        info('VoIP implements Call:      '.((new ReflectionClass(VoIP::class))->implementsInterface(Call::class) ? 'yes ✅' : 'NO ❌'));
        info('GroupCall implements Call: '.((new ReflectionClass(GroupCall::class))->implementsInterface(Call::class) ? 'yes ✅' : 'NO ❌'));
        fwrite(STDERR, "\n");
        info('Codec parameter derivation (feature #1):');
        reportDerivedCodec($argv[2] ?? $video);
        box('SELF-CHECK DONE — nothing to click.');
        break;

    case '1to1':
        if ($arg === null) {
            fwrite(STDERR, "Usage: php tests/manual_call_features.php 1to1 <user> [video] [seconds] [session]\n");
            exit(2);
        }
        if (!is_file($video)) {
            fwrite(STDERR, "Video file $video does not exist.\n");
            exit(1);
        }
        $out = __DIR__.'/../incoming_1to1.mkv';

        box('TEST: 1:1 VIDEO CALL — codec params + recording + duration + resume');
        reportDerivedCodec($video);

        $API = startApi($session);
        $peerId = $API->getId($arg);
        // The call and its recorder now serialize with the session, so if one is already running
        // (e.g. this script was Ctrl-C'd earlier) it came back on start() with recording already
        // resuming — attach to it WITHOUT restarting anything.
        $existing = $peerId !== null ? $API->getCallByPeer($peerId) : null;
        $running = $existing !== null && !in_array($existing->getCallState(), [CallState::ENDED], true);

        if ($running) {
            $call = $existing;
            info('Found an existing call (state: '.$call->getCallState()->name.') — attaching, not restarting.');
            info('Its recording resumed into '.$out.' on startup (appended, not truncated).');
            click("Nothing to do — the call is still up. Press Ctrl-C to detach again (the call keeps running); only hanging up on the OTHER account ends it.");
        } else {
            @unlink($out); // a genuinely new recording starts fresh
            info("Placing a NEW video call to $arg …");
            $call = $API->requestCall($arg, video: true);
            $call->play(new LocalFile($video));
            click("On the OTHER account, ANSWER the incoming call in Telegram, and turn the CAMERA ON.");
            info('Waiting up to 120s for the call to connect…');
            waitFor(static fn (): bool => $call->getCallState() !== CallState::REQUESTED, microtime(true) + 120);
            if ($call->getCallState() !== CallState::RUNNING) {
                info('Call not answered (state: '.$call->getCallState()->name.'). Leaving it as-is (not discarding).');
                exit(1);
            }
            info('Connected ✅  Recording the incoming camera+mic into '.$out);
            click("On the peer, keep the CAMERA on. You should also SEE our video file playing on the peer's screen — check it looks correct (that proves the codec params).");
            $call->setOutput(new LocalFile($out));
        }

        info('Monitoring the call. Ctrl-C to detach WITHOUT ending it; re-run this command to re-attach.');
        info('To test resume: Ctrl-C now, then re-run — the recording must keep growing, not restart.');
        // Watch until the PEER ends the call. Never discard from here.
        $lastReport = 0.0;
        while ($call->getCallState() !== CallState::ENDED) {
            if (microtime(true) - $lastReport > 5) {
                clearstatcache();
                info('… still running ('.$call->getCallState()->name.'); '.$out.' = '.number_format(is_file($out) ? (int) filesize($out) : 0).' bytes');
                $lastReport = microtime(true);
            }
            Tools::sleep(1.0);
        }
        Tools::sleep(1.5);
        box('1:1 RESULT (peer hung up)');
        inspect($out);
        info('Expect: an audio (opus) AND a video stream. If you Ctrl-C+re-ran, the file kept growing across restarts.');
        break;

    case 'group':
        if ($arg === null) {
            fwrite(STDERR, "Usage: php tests/manual_call_features.php group <chat> [video] [seconds] [session]\n");
            exit(2);
        }
        $dir = __DIR__.'/../group_recordings';

        box('TEST: GROUP CALL — folder recording (one .ogg per participant) + resume');
        $API = startApi($session);
        // If we are already joined (this script was Ctrl-C'd earlier), the call and its per-participant
        // recorders came back on start() with recording resuming — attach without rejoining.
        $existing = $API->getGroupCall($arg);
        $joined = $existing !== null && $existing->getCallState() === GroupCallState::JOINED;

        if ($joined) {
            $call = $existing;
            info('Already joined — attaching, not rejoining. Per-participant recordings resumed in '.$dir);
            click("Nothing to do. Ctrl-C to detach (the call keeps running); the recordings keep growing.");
        } else {
            /** @var GroupCall $call */
            $call = $API->joinGroupCall($arg);
            click("Open the SAME group call on one or more OTHER accounts, JOIN, and UNMUTE so they transmit.");
            if (is_file($video)) {
                info('Also playing '.basename($video).' into the call.');
                $call->play(new LocalFile($video));
            }
            info('Recording every transmitting participant into '.$dir.'/<peerId>.ogg');
            $call->setOutput(new LocalDirectory($dir));
        }

        info('Monitoring. Ctrl-C to detach WITHOUT leaving; re-run to re-attach (recordings resume/append).');
        $lastReport = 0.0;
        while ($call->getCallState() === GroupCallState::JOINED) {
            if (microtime(true) - $lastReport > 5) {
                $files = glob($dir.'/*.ogg') ?: [];
                info('… joined; '.count($files).' participant file(s) in '.$dir);
                $lastReport = microtime(true);
            }
            Tools::sleep(1.0);
        }

        box('GROUP RESULT (left)');
        $files = glob($dir.'/*.ogg') ?: [];
        if ($files === []) {
            info('⚠️  No per-participant files — did anyone else actually transmit audio?');
        }
        foreach ($files as $f) {
            info(basename($f).' — '.number_format((int) filesize($f)).' bytes');
        }
        info('Expect one .ogg per participant who transmitted (excluding ourselves).');
        break;

    default:
        fwrite(STDERR, <<<TXT
        MadelineProto call-features manual test.

          php tests/manual_call_features.php check [video-file]
              Offline checks: Call interface + codec-param derivation. No Telegram.

          php tests/manual_call_features.php 1to1 <user> [video] [seconds] [session]
              Place a 1:1 video call, record the incoming camera+mic to incoming_1to1.mkv,
              and verify streams + duration. You answer the call on the other account.

          php tests/manual_call_features.php group <chat> [video] [seconds] [session]
              Join a group call, record every participant into group_recordings/<id>.ogg.
              Other accounts join+unmute.

        Defaults: video=av1.webm, seconds=25, session=fuzz_user.madeline.

        TXT);
        break;
}
