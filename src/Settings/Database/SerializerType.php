<?php declare(strict_types=1);

namespace danog\MadelineProto\Settings\Database;

enum SerializerType
{
    case SERIALIZE;
    case IGBINARY;
    case JSON;
    case STRING;
}
