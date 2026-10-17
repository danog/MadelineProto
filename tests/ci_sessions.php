<?php declare(strict_types=1);

/**
 * Create a session for the CI.
 *
 * Usage:
 * php tests/ci_sessions.php [--test] user <session> <variable>   Log in with the authorization in the environment
 *                                                                 variable <variable>: "<DC>:<auth key, base64>",
 *                                                                 as exported by API::exportAuthorization().
 * php tests/ci_sessions.php [--test] bot <session> <variable>    Log in as the bot whose token is in <variable>.
 * php tests/ci_sessions.php [--test] unauth <session>            Don't log in.
 *
 * --test: on the test servers of Telegram, where all of the user accounts of the CI are (along with the
 * bots they interact with).
 *
 * API_ID and API_HASH are needed. Exits with 2 if <variable> is not set.
 *
 * The session is created in this process, rather than in a worker that would outlive it.
 */

use danog\MadelineProto\API;
use danog\MadelineProto\Conversion;
use danog\MadelineProto\Magic;
use danog\MadelineProto\Settings;

require __DIR__.'/../vendor/autoload.php';

$args = array_slice($argv, 1);
$test = ($args[0] ?? null) === '--test';
if ($test) {
    array_shift($args);
}
[$kind, $session, $variable] = $args + [null, null, null];
if (!in_array($kind, ['user', 'bot', 'unauth'], true) || $session === null || ($kind !== 'unauth' && $variable === null)) {
    fwrite(STDERR, "Usage: {$argv[0]} [--test] user|bot|unauth <session> [<variable>]\n");
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

if ($kind === 'user') {
    [$dc, $authKey] = explode(':', $value, 2);
    $API = Conversion::importAuthorization([(int) $dc => base64_decode($authKey, true)], (int) $dc, $session, $settings);
    if (!$API->isSelfUser()) {
        throw new AssertionError("$variable is not the authorization of a user!");
    }
} else {
    $API = new API($session, $settings);
    if ($kind === 'bot') {
        $API->botLogin($value);
    }
}
echo "Created $session".PHP_EOL;
exit(0);
