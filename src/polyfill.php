<?php declare(strict_types=1);

namespace danog\MadelineProto\Db;

if (class_exists('\\danog\\MadelineProto\\Db\\NullCache\\MysqlArray')) {
    return;
}

if (\PHP_OS_FAMILY === 'Windows') {
    echo "WARNING: MadelineProto runs around 10x slower on windows due to OS and PHP limitations. Make sure to deploy MadelineProto in production only on Linux or Mac OS machines for maximum performance.".PHP_EOL;
}

use danog\AsyncOrm\Internal\Containers\CacheContainer;
use danog\AsyncOrm\Internal\Driver\MysqlArray;
use danog\AsyncOrm\Internal\Driver\PostgresArray;
use danog\AsyncOrm\Internal\Driver\RedisArray;
use danog\MadelineProto\GroupCall\GroupCallController;
use danog\MadelineProto\Tgcalls\PrivateCallController;
use phpseclib4\Math\BigInteger;

class_alias(MysqlArray::class, '\\danog\\MadelineProto\\Db\\NullCache\\MysqlArray');
class_alias(PostgresArray::class, '\\danog\\MadelineProto\\Db\\NullCache\\PostgresArray');
class_alias(RedisArray::class, '\\danog\\MadelineProto\\Db\\NullCache\\RedisArray');

class_alias(MysqlArray::class, '\\danog\\MadelineProto\\Db\\MysqlArray');
class_alias(PostgresArray::class, '\\danog\\MadelineProto\\Db\\PostgresArray');
class_alias(PostgresArray::class, '\\danog\\MadelineProto\\Db\\PostgresArrayBytea');
class_alias(RedisArray::class, '\\danog\\MadelineProto\\Db\\RedisArray');
class_alias(CacheContainer::class, '\\danog\\MadelineProto\\Db\\CacheContainer');

class_alias(BigInteger::class, '\\phpseclib3\\Math\\BigInteger');

class_alias(PrivateCallController::class, '\\danog\\MadelineProto\\VoIPController');

// Backwards-compatibility aliases for the call classes moved under EventHandler\Calls (and the
// GroupCallController moved under GroupCall). PrivateCall was previously named VoIP.
class_alias(\danog\MadelineProto\EventHandler\Calls\PrivateCall::class, '\\danog\\MadelineProto\\VoIP');

if ((PHP_MINOR_VERSION === 2 && PHP_VERSION_ID < 80204)
    || PHP_MAJOR_VERSION < 8
    || (PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION < 2)
) {
    echo('MadelineProto requires PHP 8.3.1+ (recommended) or 8.2.14+.'.PHP_EOL);
    die(1);
}
