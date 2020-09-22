<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;
use danog\MadelineProto\Settings\DatabaseAbstract;

interface DbType
{
    /**
     * @param string $name
     * @param null $value
     * @param string $tablePrefix
     * @param DatabaseAbstract $settings
     *
     * @return Promise<self>
     */
    public static function getInstance(string $name, $value = null, string $tablePrefix = '', $settings): Promise;
}
