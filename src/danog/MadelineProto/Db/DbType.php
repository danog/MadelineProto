<?php

namespace danog\MadelineProto\Db;

use Amp\Promise;

interface DbType
{
    /**
     * @param string $name
     * @param null $value
     * @param string $tablePrefix
     * @param array $settings
     *
     * @return Promise<self>
     */
    public static function getInstance(string $name, $value = null, string $tablePrefix = '', array $settings = []): Promise;
}
