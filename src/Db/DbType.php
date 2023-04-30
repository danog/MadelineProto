<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Settings\DatabaseAbstract;

interface DbType
{
    public static function getInstance(string $table, DbType|array|null $previous, DatabaseAbstract $settings): static;
}
