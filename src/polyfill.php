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
