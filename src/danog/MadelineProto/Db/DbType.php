<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Settings\Database\DatabaseAbstract;

/** @template TSettings as DatabaseAbstract */
interface DbType
{
    /**
     * @param TSettings $settings
     */
    public static function getInstance(string $table, DbType|array|null $previous, $settings): static;
}
