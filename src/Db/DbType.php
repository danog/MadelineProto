<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Settings\Database\DatabaseAbstract;

interface DbType
{
    /**
     * @param DatabaseAbstract $settings
     */
    public static function getInstance(string $table, DbType|array|null $previous, $settings): static;
}
