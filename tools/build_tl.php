<?php declare(strict_types=1);

use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\Builder;

require __DIR__.'/../vendor/autoload.php';

$builder = new Builder(new TLSchema, __DIR__.'/../src/TL/TLParser.php', 'danog\\MadelineProto\\TL');
$builder->build();
