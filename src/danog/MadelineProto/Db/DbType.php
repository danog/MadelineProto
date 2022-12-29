<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;
use danog\MadelineProto\Settings\Database\DatabaseAbstract;

interface DbType
{
    /**
     * @param null|DbType|array $previous
     * @param DatabaseAbstract  $settings
     *
     * @return Promise<self>
     */
    public static function getInstance(string $table, $previous, $settings): Promise;
}
