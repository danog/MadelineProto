<?php declare(strict_types=1);

namespace danog\MadelineProto\Settings\Database;

enum SerializerType: string
{
    case SERIALIZE = 'serialize';
    case IGBINARY = 'igbinary';
    case JSON = 'json';
    case STRING = 'string';
}
