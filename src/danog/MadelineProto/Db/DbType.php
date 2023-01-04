<?php

declare(strict_types=1);

namespace danog\MadelineProto\Db;

use danog\MadelineProto\Settings\Database\DatabaseAbstract;

/** @template TSettings as DatabaseAbstract */
interface DbType
{
    /**
     * @param null|DbType|array $previous
     * @param TSettings $settings
     */
    public static function getInstance(string $table, $previous, $settings): static;
}
