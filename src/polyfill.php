<?php declare(strict_types=1);

if (class_exists('\\danog\\MadelineProto\\Db\\NullCache\\MysqlArray')) {
    return;
}
use danog\MadelineProto\Db\MysqlArray;
use danog\MadelineProto\Db\PostgresArray;
use danog\MadelineProto\Db\PostgresArrayBytea;
use danog\MadelineProto\Db\RedisArray;

class_alias(MysqlArray::class, '\\danog\\MadelineProto\\Db\\NullCache\\MysqlArray');
class_alias(PostgresArray::class, '\\danog\\MadelineProto\\Db\\NullCache\\PostgresArray');
class_alias(PostgresArrayBytea::class, '\\danog\\MadelineProto\\Db\\NullCache\\PostgresArrayBytea');
class_alias(RedisArray::class, '\\danog\\MadelineProto\\Db\\NullCache\\RedisArray');

if ((PHP_MINOR_VERSION === 2 && PHP_VERSION_ID < 80204)
    || (PHP_MINOR_VERSION === 1 && PHP_VERSION_ID < 80117)
) {
    echo('MadelineProto requires PHP 8.3.1+ (recommended) or 8.2.14+.'.PHP_EOL);
    die(1);
}
