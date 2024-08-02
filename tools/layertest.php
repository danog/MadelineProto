<?php declare(strict_types=1);

use danog\MadelineProto\Logger;
use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\Settings\Logger as SettingsLogger;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;

use function Amp\async;

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

if ($argc !== 3) {
    die("Usage: {$argv[0]} layernumber\n");
}
/**
 * Get TL info of layer.
 *
 * @param int $layer Layer number
 *
 * @internal
 *
 * @return void
 */
function getTL($layer)
{
    $layerFile = __DIR__."/../schemas/TL_telegram_v$layer.tl";
    $layer = new TL();
    $layer->init((new TLSchema)->setAPISchema($layerFile)->setSecretSchema(''));

    return ['methods' => $layer->getMethods(), 'constructors' => $layer->getConstructors()];
}
$layer = getTL($argv[1]);
$res = '';

$bot = new \danog\MadelineProto\API('bot.madeline');
$bot->start();
$bot->updateSettings((new TLSchema)->setFuzzMode(true));

$user = new \danog\MadelineProto\API('secret.madeline');
$user->start();
$user->updateSettings((new TLSchema)->setFuzzMode(true));

$methods = [];
foreach ($layer['methods']->by_id as $constructor) {
    $name = $constructor['method'];
    if (strtolower($name) === 'account.deleteaccount'
        || strtolower($name) === 'auth.logout'
        || $name === 'auth.resetAuthorizations'
        || $name === 'auth.dropTempAuthKeys'
        || $name === 'account.resetAuthorization'
        || $name === 'account.resetPassword'
        || !str_contains($name, '.')) {
        continue;
    }
    [$namespace, $method] = explode('.', $name);

    $methods []= async(static function () use ($namespace, $method, $bot): void {
        try {
            $bot->{$namespace}->{$method}();
        } catch (RPCErrorException) {
        }
    });
    $methods []= async(static function () use ($namespace, $method, $user): void {
        try {
            $user->{$namespace}->{$method}();
        } catch (RPCErrorException) {
        }
    });
}

var_dump(array_map('strval', \Amp\Future\awaitAll($methods)[0]));
