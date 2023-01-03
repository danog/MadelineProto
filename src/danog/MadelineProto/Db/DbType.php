<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Settings\Database\DatabaseAbstract;

interface DbType
{
    /**
     * @param null|DbType|array $previous
     */
    public static function getInstance(string $table, $previous, DatabaseAbstract $settings): static;
}
