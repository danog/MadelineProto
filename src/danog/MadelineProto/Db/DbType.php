<?php

namespace danog\MadelineProto\Db;

interface DbType
{
    static function getInstance(array $settings, string $name, $value): self;
}