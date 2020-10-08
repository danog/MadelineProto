<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;
use danog\MadelineProto\Settings\DatabaseAbstract;

interface DbType
{
    /**
     * @param string            $table
     * @param null|DbType|array $value
     * @param DatabaseAbstract  $settings
     *
     * @return Promise<self>
     */
    public static function getInstance(string $table, $value, $settings): Promise;
}
