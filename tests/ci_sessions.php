<?php declare(strict_types=1);

/**
 * Create a session for the CI.
 *
 * Usage:
 * php tests/ci_sessions.php [--test] user <session> <variable>      Log in with the authorization of a user in the
 *                                                                    environment variable <variable>:
 *                                                                    "<DC>:<auth key, base64>", as exported by
 *                                                                    API::exportAuthorization().
 * php tests/ci_sessions.php [--test] bot <session> <variable>       The same, with the authorization of a bot.
 * php tests/ci_sessions.php [--test] bot-token <session> <variable> Log in as the bot whose token is in <variable>.
 * php tests/ci_sessions.php [--test] unauth <session>               Don't log in.
 *
 * --test: on the test servers of Telegram, where all of the user accounts of the CI are (along with the
 * bots they interact with).
 *
 * Logging in a bot with its token creates a new authorization every time: done by every run of the CI, it
 * gets FLOOD_WAITs (auth.importBotAuthorization), so the CI logs its bot in with an exported authorization
 * (BOT_SESSION_AUTH), like its users. Only the daily build logs in with a token (TEST_BOT_TOKEN, once a day).
 *
 * API_ID and API_HASH are needed. Exits with 2 if <variable> is not set.
 *
 * The session is created in this process, rather than in a worker that would outlive it, with the
 * MadelineProto that tests/testing.php uses: the phar of madeline.php with ACTIONS_PHAR or
 * ACTIONS_FORCE_PREVIOUS (so that the session is created by the version under test, see tests/makephar.sh),
 * the sources otherwise.
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Conversion;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;

if (getenv('ACTIONS_PHAR') || getenv('ACTIONS_FORCE_PREVIOUS') || !file_exists(__DIR__.'/../vendor/autoload.php')) {
    include 'madeline.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}

$args = array_slice($argv, 1);
$test = ($args[0] ?? null) === '--test';
if ($test) {
    array_shift($args);
}
[$kind, $session, $variable] = $args + [null, null, null];
if (!in_array($kind, ['user', 'bot', 'bot-token', 'unauth'], true) || $session === null || ($kind !== 'unauth' && $variable === null)) {
    fwrite(STDERR, "Usage: {$argv[0]} [--test] user|bot|bot-token|unauth <session> [<variable>]\n");
    exit(1);
}
$value = $variable === null ? '' : (string) getenv($variable);
if ($kind !== 'unauth' && $value === '') {
    echo "$variable is not set, not creating $session".PHP_EOL;
    exit(2);
}
if (file_exists($session)) {
    echo "$session already exists".PHP_EOL;
    exit(1);
}

Magic::start(light: false);
Magic::$isIpcWorker = true;
$settings = new Settings;
$settings->getAppInfo()->setApiId((int) getenv('API_ID'))->setApiHash((string) getenv('API_HASH'));
$settings->getConnection()->setTestMode($test);

if ($kind === 'user' || $kind === 'bot') {
    [$dc, $authKey] = explode(':', $value, 2);
    $API = Conversion::importAuthorization([(int) $dc => base64_decode($authKey, true)], (int) $dc, $session, $settings);
    if ($kind === 'user' ? !$API->isSelfUser() : !$API->isSelfBot()) {
        throw new AssertionError("$variable is not the authorization of a $kind!");
    }
} else {
    $API = new API($session, $settings);
    if ($kind === 'bot-token') {
        $API->botLogin($value);
    }
}
// Before the session is saved: the versions up to 8.9.0 saved the waiters of the states of the connections that a GC
// cycle didn't free yet, which threw once loaded (the previous version, in tests/makephar.sh).
gc_collect_cycles();
echo "Created $session".PHP_EOL;
exit(0);
