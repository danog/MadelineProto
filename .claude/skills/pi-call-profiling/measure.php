<?php
// Measures how late the playback tracks release frames, relative to the file's own timestamps.
// Usage: php measure.php <file> <lookahead ms> <seconds> [busy ms per 10ms, to simulate other calls]
require __DIR__.'/../../../vendor/autoload.php';

use danog\MadelineProto\LocalFile;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Loop\VoIP\DjLoop;
use danog\MadelineProto\Settings\VoIP;
use danog\MadelineProto\Tgcalls\CallControllerInterface;
use danog\MadelineProto\Tgcalls\OpusPlaybackTrack;
use danog\MadelineProto\Tgcalls\VideoPlaybackTrack;
use Revolt\EventLoop;

[$_, $file, $lookahead, $seconds] = $argv;
$busy = (float) ($argv[4] ?? 0);
$call = new class((int) $lookahead) implements CallControllerInterface {
    public bool $ended = false;
    public function __construct(private int $lookahead) {}
    public function log(string $message, int $level = Logger::NOTICE): void {}
    public function isCallEnded(): bool { return $this->ended; }
    public function getVoIPSettings(): VoIP { return (new VoIP)->setPlaybackLookahead($this->lookahead); }
    public function __toString(): string { return 'measure'; }
};
$dj = new DjLoop($call);
$audio = new OpusPlaybackTrack($dj, $call);
$video = new VideoPlaybackTrack($dj, $call);
$dj->play(new LocalFile($file));

$results = new ArrayObject;
$sinks = [];
foreach (['audio' => [$audio, 48000], 'video' => [$video, 90000]] as $kind => [$track, $clock]) {
    $track->setSink($sinks[] = new class($kind, $clock, $results) implements \Webrtc\RTP\MediaStreamTrack\MediaStreamTrackSink {
        private ?array $first = null;
        public function __construct(private string $kind, private int $clock, private ArrayObject $results) {}
        public function onFrame(\Webrtc\AVCodec\Frame\FrameInterface|\Webrtc\Codecs\EncodedPacket $packet): bool
        {
            $now = hrtime(true) / 1e9;
            $ts = $packet->getTimestamp();
            $this->first ??= [$now, $ts];
            $list = $this->results[$this->kind] ?? [];
            $list[] = ($now - $this->first[0]) - ($ts - $this->first[1]) / $this->clock;
            $this->results[$this->kind] = $list;
            return true;
        }
    });
}
if ($busy > 0) {
    // Other calls keeping the event loop busy: block for $busy ms every 10ms.
    EventLoop::repeat(0.01, static function () use ($busy): void {
        $end = hrtime(true) + $busy * 1e6;
        while (hrtime(true) < $end);
    });
}
EventLoop::delay((float) $seconds, static function () use ($call, $audio, $video, $results): void {
    $call->ended = true;
    $audio->stop();
    $video->stop();
    foreach ($results as $kind => $lateness) {
        // Lateness relative to the earliest frame, so a constant offset doesn't count.
        $min = min($lateness);
        $lateness = array_map(static fn ($l) => ($l - $min) * 1000, $lateness);
        $gaps = 0;
        sort($lateness);
        $n = count($lateness);
        printf("%s: %d frames, lateness ms p50=%.1f p90=%.1f p99=%.1f max=%.1f, >15ms: %.1f%%\n", $kind, $n,
            $lateness[(int) ($n * .5)], $lateness[(int) ($n * .9)], $lateness[(int) ($n * .99)], $lateness[$n - 1],
            100 * count(array_filter($lateness, static fn ($l) => $l > 15)) / $n);
    }
    exit(0);
});
EventLoop::run();
