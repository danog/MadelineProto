<?php

namespace danog\MadelineProto\Db;

interface DbType
{
    static function getInstance(string $name, $value, string $tablePrefix, array $settings): self;
}