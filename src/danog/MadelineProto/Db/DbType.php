<?php

namespace danog\MadelineProto\Db;

interface DbType
{
    static function getInstance(string $name, $value = null, string $tablePrefix = '', array $settings = []): self;
}