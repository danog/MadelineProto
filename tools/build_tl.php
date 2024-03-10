<?php declare(strict_types=1);

use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\Builder;
use danog\MadelineProto\TL\SecretBuilder;

require __DIR__.'/../vendor/autoload.php';

\danog\MadelineProto\Magic::start(true);

$builder = new Builder(new TLSchema, __DIR__.'/../src/TL/TLParser.php', 'danog\\MadelineProto\\TL');
$builder->build();

$builder = new SecretBuilder(new TLSchema, __DIR__.'/../src/TL/SecretTLParser.php', 'danog\\MadelineProto\\TL');
$builder->build();
