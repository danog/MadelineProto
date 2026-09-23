<?php declare(strict_types=1);

use Amp\CancelledException;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use danog\MadelineProto\API;
use danog\MadelineProto\Logger;
use danog\MadelineProto\Magic;
use danog\MadelineProto\PTSException;
use danog\MadelineProto\RPCError\BusinessConnectionNotAllowedError;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\Tools;
use Revolt\EventLoop;
use Webmozart\Assert\Assert;

use function Amp\async;
use function Amp\Future\await;

/*
Copyright 2016-2020 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
 */

require 'vendor/autoload.php';
$logger = new Logger(new SettingsLogger);

set_error_handler(['\danog\MadelineProto\Exception', 'ExceptionErrorHandler']);

$classes = [];
foreach (new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        __DIR__.'/../src/',
        FilesystemIterator::CURRENT_AS_PATHNAME|FilesystemIterator::SKIP_DOTS,
    ),
    RecursiveIteratorIterator::LEAVES_ONLY,
) as $f
) {
    $f = realpath($f);
    $f = substr($f, strlen(realpath(__DIR__.'/../src/'))+1);
    if (str_ends_with($f, '.php')) {
        if ($f === 'polyfill.php' || basename($f) === 'entry.php') {
            continue;
        }
        $f = str_replace(['/', '.php', 'src\\'], ['\\', '', ''], $f);
        $f = "danog\\MadelineProto\\$f";
        $classes[$f] = true;
    }
}

foreach ($classes as $class => $_) {
    if (!class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
        throw new \RuntimeException("Class $class does not exist");
    }
}

/**
 * @internal
 */
function getTLSchema(): TLSchema
{
    $layerFile = glob(__DIR__."/../src/TL_telegram_v*.tl")[0];
    return (new TLSchema)->setAPISchema($layerFile)->setSecretSchema('')->setFuzzMode(true);
}

/**
 * Get TL info of layer.
 *
 * @internal
 *
 * @return void
 */
function getTL(TLSchema $schema)
{
    $layer = new TL();
    $layer->init($schema);

    return ['methods' => $layer->getMethods(), 'constructors' => $layer->getConstructors()];
}
$schema = getTLSchema();
$layer = getTL($schema);
$res = '';

Magic::start(true);
$auth = '';
try {
    $auth = getenv('TELERPC_AUTH_TOKEN') ?: '';
} catch (Throwable) {
}
Assert::true(preg_match('/^[a-zA-Z0-9_-]*$/', $auth) === 1, 'TELERPC_AUTH_TOKEN can only contain a-z, A-Z, 0-9, _ and -');

$client = HttpClientBuilder::buildDefault();
if ($auth) {
    $res = json_decode(
        (
            $client
                ->request(new Request('https://report-rpc-error.madelineproto.xyz/?auth='.$auth.'&cleanup=1'))
        )->getBody()->buffer(),
        true,
    );
    Assert::true($res['ok']);
    echo "Cleaned up old reports".PHP_EOL;
} else {
    echo "No TELERPC_AUTH_TOKEN set, not cleaning up old reports".PHP_EOL;
}

if ($auth) {
    $layerNumber = $schema->getLayer();
    $res = json_decode(
        (
            $client
                ->request(new Request('https://report-rpc-error.madelineproto.xyz/?auth='.$auth.'&setLayer='.$layerNumber))
        )->getBody()->buffer(),
        true,
    );
    Assert::true($res['ok']);
    echo "Set layer number to $layerNumber".PHP_EOL;
} else {
    echo "No TELERPC_AUTH_TOKEN set, not setting layer number".PHP_EOL;
}

$settings = new Settings;
$settings->setSchema($schema);
$settings->getLogger()->setLevel(Logger::ULTRA_VERBOSE);

echo "Unauthed:".PHP_EOL;
$unauthed = new \danog\MadelineProto\API('fuzz_unauth.madeline');
$unauthed->updateSettings($settings);
Assert::false($unauthed->getSelf(), "fuzz_unauth.madeline is authed!");
$unauthed->restart();

echo "Bot login:".PHP_EOL;
$bot = new \danog\MadelineProto\API('fuzz_bot.madeline');
$bot->start();
$bot->updateSettings($settings);
Assert::true($bot->isSelfBot(), "fuzz_bot.madeline is not a bot!");
$u = $bot->getSelf()['username'];
Assert::true($bot->getSelf()['bot_business'], "fuzz_bot.madeline ($u) is not a business bot, enable business mode in botfather!");
$bot->restart();

echo "User login:".PHP_EOL;
$user = new \danog\MadelineProto\API('fuzz_user.madeline');
$user->start();
$user->updateSettings($settings);
Assert::true($user->isSelfUser(), "fuzz_user.madeline is not a user!");
$user->restart();

$user->getSelf();
$bot->getSelf();

$cId = null;

$toggleBusiness = static function (bool $enable) use ($user, $bot, $layer, &$cId): void {
    $bot->getUpdates();

    Logger::log($enable ? "Initializing business connection..." : "Deinitializing business connection...");
    $rights = ['_' => 'businessBotRights'];
    // Use the local schema: fetching the TL object over IPC would serialize the entire remote session.
    foreach ($layer['constructors']->findByPredicate('businessBotRights')['params'] as $param) {
        if ($param['type'] === 'true') {
            $rights[$param['name']] = true;
        }
    }

    foreach ([$enable, !$enable] as $deleted) {
        $user->account->updateConnectedBot(
            bot: $bot->getSelf()['username'],
            deleted: $deleted,
            rights: $rights,
            recipients: [
                '_' => 'inputBusinessBotRecipients',
                'existing_chats' => true,
                'new_chats' => true,
                'contacts' => true,
                'non_contacts' => true,
            ],
        );
    }
    $cId = null;
    $offset = 0;
    do {
        foreach ($bot->getUpdates(['offset' => $offset, 'timeout' => 10.0]) as $u) {
            $offset = $u['update_id'] + 1;
            $u = $u['update'];
            if ($u['_'] !== 'updateBotBusinessConnect') {
                continue;
            }
            if ($u['connection']['disabled'] !== !$enable) {
                continue;
            }
            $cId = $u['connection']['connection_id'];
            break 2;
        }
    } while (true);
    $bot->account->getBotBusinessConnection(
        connection_id: $cId,
    );
    $bot->setNoop();

    Logger::log($enable ? "Initialized business connection!" : "Deinitialized business connection!");
};

$toggleBusiness(true);

function call(API $API, string $method, array $args = []): void
{
    Tools::getVar($API, 'wrapper')->getAPI()->methodCallAsyncRead($method, $args);
}

function getCancellationReason(CancelledException $e): string
{
    return $e->getPrevious()?->getMessage() ?? $e->getMessage();
}

$methods = [];

$unauthedNames = [];
foreach ($layer['methods']->by_id as $constructor) {
    $name = $constructor['method'];
    if (strtolower($name) === 'account.deleteaccount'
        || !str_contains($name, '.')) {
        continue;
    }
    $unauthedNames []= $name;
}

$names = [];
foreach ($layer['methods']->by_id as $constructor) {
    $name = $constructor['method'];
    if (strtolower($name) === 'account.deleteaccount'
        || strtolower($name) === 'auth.logout'
        || $name === 'auth.resetAuthorizations'
        || $name === 'auth.dropTempAuthKeys'
        || $name === 'account.resetAuthorization'
        || $name === 'account.resetPassword'
        || $name === 'account.updateUsername'
        || $name === 'photos.updateProfilePhoto'
        || $name === 'photos.uploadProfilePhoto'
        || $name === 'payments.resolveStarGiftOffer' // tmp
        || !str_contains($name, '.')) {
        continue;
    }
    $names []= $constructor['method'];
}

// unauthed + (bot, user, business, business invalid) + bot disconnected
$total = count($unauthedNames) + (count($names) * 5);
$done = 0;
$started = hrtime(true);

$progress = static function () use (&$done, $total, $started): void {
    $percent = $total ? ($done * 100) / $total : 100.0;
    $elapsed = (hrtime(true) - $started) / 1e9;
    $eta = $done ? ($elapsed / $done) * ($total - $done) : 0.0;
    fprintf(
        STDERR,
        "Progress: %6.2f%% (%d/%d), elapsed %s, ETA %s\n",
        $percent,
        $done,
        $total,
        gmdate('H:i:s', (int) $elapsed),
        gmdate('H:i:s', (int) $eta),
    );
};

$wait = static function (bool $force = false) use (&$methods, $progress): void {
    if (count($methods) >= 10 || $force) {
        Logger::log("Processing ".implode(", ", array_keys($methods)));
        await($methods);
        Logger::log("Done!");
        Assert::isEmpty($methods, "Some methods were not processed!");
        $progress();
    }
};

/**
 * Spawn a fuzzing task, tracking completion for the progress indicator.
 *
 * @param string  $key  Unique task name
 * @param Closure $task Task to run, must throw RuntimeException on cancellation
 */
$spawn = static function (string $key, Closure $task) use (&$methods, &$done): void {
    $methods[$key] = async(static function () use ($key, $task, &$methods, &$done): void {
        try {
            $task();
        } catch (CancelledException $e) {
            throw new \RuntimeException("Got cancellation for $key: ".getCancellationReason($e), previous: $e);
        } finally {
            unset($methods[$key]);
            $done++;
        }
    });
};

foreach ($unauthedNames as $name) {
    $spawn("unauthed $name", static function () use ($unauthed, $name): void {
        try {
            call($unauthed, $name);
        } catch (RPCErrorException|PTSException) {
        }
    });
    $wait();
}

foreach ($names as $name) {
    $spawn("bot $name", static function () use ($bot, $name): void {
        try {
            call($bot, $name);
        } catch (RPCErrorException|PTSException) {
        }
    });
    $spawn("user $name", static function () use ($user, $name): void {
        try {
            call($user, $name);
        } catch (RPCErrorException|PTSException) {
        }
    });
    $spawn("business $name", static function () use ($bot, $name, $cId, $client, $auth): void {
        $ok = true;
        try {
            call($bot, $name, ['businessConnectionId' => $cId]);
        } catch (PTSException|BusinessConnectionNotAllowedError) {
            $ok = false;
        } catch (RPCErrorException $e) {
            echo "Got ".$e->getMessage()." for business $name".PHP_EOL;
        }

        if ($ok && $auth) {
            $res = json_decode(
                (
                    $client
                        ->request(new Request('https://report-rpc-error.madelineproto.xyz/?auth='.$auth.'&error=BUSINESS_CONNECTION_INVALID&method='.urlencode($name).'&code=400'))
                )->getBody()->buffer(),
                true,
            );
        }
    });
    $spawn("business invalid $name", static function () use ($bot, $name): void {
        try {
            call($bot, $name, ['businessConnectionId' => '']);
        } catch (RPCErrorException|PTSException) {
        }
    });

    $wait();
}

$wait(true);

$toggleBusiness(false);

foreach ($names as $name) {
    $spawn("bot disconnected $name", static function () use ($bot, $name): void {
        try {
            call($bot, $name);
        } catch (RPCErrorException|PTSException) {
        }
    });

    $wait();
}

$wait(true);

Assert::eq($done, $total, "Expected $total tasks, ran $done!");
echo "Fuzzing complete!".PHP_EOL;

unset($bot, $user, $unauthed, $wait, $spawn, $progress, $toggleBusiness);

// Give time for error reporting routine to finish
EventLoop::run();
