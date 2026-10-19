<?php declare(strict_types=1);

/**
 * Send the changelog of a release to the chats of CHATS on Telegram (without the diff of the layer, too big, the
 * internal changes and the updates of dependabot), with a link to the phar sent to @madelineprotophar by
 * tests/send_phar.sh, once it's fully released: see tests/release_notes.sh.
 *
 * Usage: php tests/announce_release.php <tag>. Needs GITHUB_TOKEN (able to edit the releases) and PHAR_BOT_TOKEN
 * (the bot that sends the phars to @madelineprotophar, see tests/send_phar.sh; a member of the chats of CHATS, an
 * admin allowed to post in the channels and to pin messages where the changelog is pinned).
 * Does nothing if the release is still a draft or a pre-release, or was already announced; if it fails midway,
 * running it again sends the rest (the steps done, messages sent and pins, are counted in the body of the release).
 *
 * With DRY_RUN=1, prints the messages instead (of any release, PHAR_BOT_TOKEN is not needed).
 */

use danog\TelegramEntities\Entities;
use danog\TelegramEntities\EntityTools;

require __DIR__.'/../vendor/autoload.php';

// Chat => whether the first message of the changelog is pinned there.
const CHATS = [
    '@MadelineProto' => false,
    '@MadeLine_Farsi' => false,
    '@pwrtelegramgroupru' => true,
];
const MAX_LENGTH = 4096;
// More fail with ENTITIES_TOO_LONG.
const MAX_ENTITIES = 100;
// The size of the entities is limited too (undocumented, "a limit on the total message entities size" according
// to TDLib's developers): measured as the URLs plus about 40 bytes per entity, 11569 bytes were accepted and 11688
// rejected (the changelog of 8.8.8). With a margin.
const MAX_ENTITIES_SIZE = 10000;
const ANNOUNCED = '<!-- announced -->';
// How many of the steps (messages sent and pins) were done, if not all of them, and the message to pin next.
const DONE = '/<!-- announced (\d+) steps(?: pin (\d+))? -->\n?/';

$tag = $argv[1] ?? '';
$repository = getenv('GH_REPO') ?: 'danog/MadelineProto';
$token = getenv('GITHUB_TOKEN') ?: getenv('GH_TOKEN');
$botToken = getenv('PHAR_BOT_TOKEN');
$dryRun = (bool) getenv('DRY_RUN');
if ($tag === '' || !$token || (!$botToken && !$dryRun)) {
    fwrite(STDERR, "Usage: GITHUB_TOKEN=... PHAR_BOT_TOKEN=... php {$argv[0]} <tag>\n");
    exit(1);
}

/**
 * Call an API, returning the decoded JSON response.
 */
function call(string $method, string $url, array $headers, ?array $body = null): array
{
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => implode("\r\n", [...$headers, 'Content-Type: application/json', 'User-Agent: MadelineProto']),
        'content' => $body === null ? null : json_encode($body),
        'ignore_errors' => true,
    ]]);
    $response = file_get_contents($url, false, $context);
    $status = (int) explode(' ', $http_response_header[0] ?? '')[1];
    if ($response === false || $status >= 300) {
        throw new RuntimeException("$method $url: $status $response");
    }
    return json_decode($response, true);
}

function github(string $method, string $path, ?array $body = null): array
{
    global $token;
    return call($method, "https://api.github.com/$path", ["Authorization: Bearer $token", 'Accept: application/vnd.github+json'], $body);
}

/**
 * Escape text of GitHub markdown for Telegram markdown, without the escapes of GitHub's.
 */
function text(string $text): string
{
    return EntityTools::markdownEscape(preg_replace('/\\\\([\\\\`*_{}\[\]()#+\-.!|~>])/', '$1', $text));
}

/**
 * Convert a line of GitHub markdown to Telegram markdown: only links (also bare URLs), bold and code are kept, and
 * mentions of GitHub users (@user, @app[bot]) link to them, or Telegram would show them as Telegram usernames.
 */
function inline(string $line): string
{
    $result = '';
    $offset = 0;
    preg_match_all('/\[([^\]]+)\]\(([^)\s]+)\)|\*\*(.+?)\*\*|`([^`]+)`|(https?:\/\/[^\s<>()]+)|(?<![\w@\/.])@([A-Za-z0-9](?:[A-Za-z0-9]|-(?=[A-Za-z0-9])){0,38})(\[bot\])?(?![\w\/-])/', $line, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach ($matches as $match) {
        $result .= text(substr($line, $offset, $match[0][1] - $offset));
        if (($match[1][1] ?? -1) !== -1 && $match[1][0] !== '') {
            // Telegram drops the link of code.
            $text = trim($match[1][0], '`');
            $result .= '['.text($text).']('.EntityTools::markdownUrlEscape($match[2][0]).')';
        } elseif (($match[3][1] ?? -1) !== -1 && $match[3][0] !== '') {
            $result .= '*'.text($match[3][0]).'*';
        } elseif (($match[4][1] ?? -1) !== -1 && $match[4][0] !== '') {
            $result .= '`'.EntityTools::markdownCodeEscape($match[4][0]).'`';
        } elseif (($match[6][1] ?? -1) !== -1 && $match[6][0] !== '') {
            // The accounts of GitHub apps (@dependabot[bot]) are at /apps/.
            $url = 'https://github.com/'.(($match[7][1] ?? -1) !== -1 ? 'apps/' : '').$match[6][0];
            $result .= '['.EntityTools::markdownEscape($match[0][0]).']('.EntityTools::markdownUrlEscape($url).')';
        } else {
            $result .= '['.EntityTools::markdownEscape($match[5][0]).']('.EntityTools::markdownUrlEscape($match[5][0]).')';
        }
        $offset = $match[0][1] + strlen($match[0][0]);
    }
    return $result.text(substr($line, $offset));
}

$release = github('GET', "repos/$repository/releases/tags/".rawurlencode($tag));
if (($release['draft'] || $release['prerelease']) && !$dryRun) {
    echo "$tag is not released yet".PHP_EOL;
    exit(0);
}
$body = (string) $release['body'];
if (str_contains($body, ANNOUNCED) && !$dryRun) {
    echo "$tag was already announced".PHP_EOL;
    exit(0);
}

// Without the diff of the layer, which comes last.
$changelog = explode('<!-- layer-diff -->', $body, 2)[0];
$changelog = trim(preg_replace('/<!--.*?-->/s', '', $changelog));

// Without the internal changes (the section of the generated notes, or of the notes written by hand, which start
// their sections with a label like "Fixes:") and the updates of dependabot.
$filtered = [];
$internal = false;
foreach (preg_split('/\r?\n/', $changelog) as $line) {
    if (preg_match('/^(?:#{1,6}\s+(.*?)|\**([^\s*\-][^:]*):\**)\s*$/', $line, $match)) {
        $internal = (bool) preg_match('/^\**internal( changes)?\**$/i', trim(($match[1] ?? '').($match[2] ?? '')));
    }
    if (!$internal && !str_contains($line, '@dependabot')) {
        $filtered[] = $line;
    }
}
// Without the headings left empty: a heading is kept if text follows it (not the link to the full changelog, which
// ends the generated notes), or a kept heading of a deeper level.
$kept = [];
$content = false;
$nextLevel = 0;
foreach (array_reverse($filtered) as $line) {
    if (preg_match('/^(#{1,6})\s/', $line, $match)) {
        $level = strlen($match[1]);
        if (!$content && $nextLevel <= $level) {
            continue;
        }
        $content = false;
        $nextLevel = $level;
    } elseif (trim($line) !== '' && !str_starts_with($line, '**Full Changelog**')) {
        $content = true;
    }
    $kept[] = $line;
}

$lines = ['*'.EntityTools::markdownEscape("MadelineProto {$release['name']}").'*', inline($release['html_url'])];
// The message with the phar, sent before by tests/send_phar.sh.
if (preg_match('/<!-- phar sent (\d+) -->/', $body, $match)) {
    $lines[] = EntityTools::markdownEscape('Phar: ').inline("https://t.me/madelineprotophar/{$match[1]}");
}
$lines[] = '';
foreach (array_reverse($kept) as $line) {
    if (preg_match('/^#{1,6}\s+(.*)$/', $line, $match)) {
        $lines[] = '*'.text(trim($match[1], '* ')).'*';
    } elseif (preg_match('/^\s*[*-]\s+(.*)$/', $line, $match)) {
        $lines[] = EntityTools::markdownEscape('• ').inline($match[1]);
    } else {
        $lines[] = inline($line);
    }
}

/**
 * The size of entities, as limited by MAX_ENTITIES_SIZE.
 */
function entitiesSize(array $entities): int
{
    return array_sum(array_map(static fn (array $entity): int => 40 + strlen($entity['url'] ?? ''), $entities));
}

// Messages of at most MAX_LENGTH characters (as Telegram counts them), MAX_ENTITIES entities and MAX_ENTITIES_SIZE,
// split between lines.
$messages = [];
$current = '';
foreach ($lines as $line) {
    $candidate = $current === '' ? $line : "$current\n$line";
    $entities = $current === '' ? null : Entities::fromMarkdown($candidate);
    if ($entities !== null && (EntityTools::mbStrlen($entities->message) > MAX_LENGTH || count($entities->entities) > MAX_ENTITIES || entitiesSize($entities->entities) > MAX_ENTITIES_SIZE)) {
        $messages[] = $current;
        $candidate = $line;
    }
    $current = $candidate;
}
$messages[] = $current;

// Every message to every chat, each followed by its pin if it's the first message of a chat where it's pinned.
$steps = [];
foreach (CHATS as $chat => $pin) {
    foreach ($messages as $i => $message) {
        $steps[] = ['send', $chat, $i];
        if ($pin && $i === 0) {
            $steps[] = ['pin', $chat, $i];
        }
    }
}

// Done by a previous run, which failed midway.
$done = preg_match(DONE, $body, $match) ? (int) $match[1] : 0;
$toPin = isset($match[2]) ? (int) $match[2] : null;
$body = rtrim(preg_replace(DONE, '', $body));
foreach ($steps as $step => [$action, $chat, $i]) {
    $entities = Entities::fromMarkdown(preg_replace("/\n{3,}/", "\n\n", $messages[$i]));
    if ($dryRun) {
        if ($action === 'pin') {
            printf("--- pin message 1 in %s%s\n", $chat, $step < $done ? ' (already pinned)' : '');
            continue;
        }
        printf("--- message %d/%d to %s: %d characters, %d entities of size %d%s\n", $i + 1, count($messages), $chat, EntityTools::mbStrlen($entities->message), count($entities->entities), entitiesSize($entities->entities), $step < $done ? ' (already sent)' : '');
        if ($chat === array_key_first(CHATS)) {
            echo $entities->message, PHP_EOL, json_encode($entities->entities), PHP_EOL;
        }
        continue;
    }
    if ($step < $done) {
        continue;
    }
    if ($action === 'pin') {
        call('POST', "https://api.telegram.org/bot$botToken/pinChatMessage", [], [
            'chat_id' => $chat,
            'message_id' => $toPin,
        ]);
        $toPin = null;
    } else {
        $sent = call('POST', "https://api.telegram.org/bot$botToken/sendMessage", [], [
            'chat_id' => $chat,
            'text' => $entities->message,
            'entities' => $entities->entities,
            'link_preview_options' => ['is_disabled' => true],
        ]);
        if (($steps[$step + 1][0] ?? null) === 'pin') {
            $toPin = $sent['result']['message_id'];
        }
    }
    if ($step + 1 < count($steps)) {
        github('PATCH', "repos/$repository/releases/{$release['id']}", ['body' => "$body\n<!-- announced ".($step + 1).' steps'.($toPin === null ? '' : " pin $toPin")." -->\n"]);
    }
}
if ($dryRun) {
    exit(0);
}
github('PATCH', "repos/$repository/releases/{$release['id']}", ['body' => "$body\n".ANNOUNCED."\n"]);
echo "Sent the changelog of $tag in ".count($messages).' message(s) to '.count(CHATS).' chats'.($done ? ", $done of the steps by a previous run" : '').PHP_EOL;
