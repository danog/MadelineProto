<?php

use danog\MadelineProto\AbstractAPIFactory;
use danog\MadelineProto\AnnotationsBuilder;
use danog\MadelineProto\APIFactory;
use danog\MadelineProto\APIWrapper;
use danog\MadelineProto\Async\AsyncConstruct;
use danog\MadelineProto\Bug74586Exception;
use danog\MadelineProto\Connection;
use danog\MadelineProto\DataCenter;
use danog\MadelineProto\DataCenterConnection;
use danog\MadelineProto\Db\DbPropertiesTrait;
use danog\MadelineProto\DocsBuilder;
use danog\MadelineProto\DoHConnector;
use danog\MadelineProto\GarbageCollector;
use danog\MadelineProto\InternalDoc;
use danog\MadelineProto\Lang;
use danog\MadelineProto\LightState;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProtoTools\Crypt;
use danog\MadelineProto\MTProtoTools\MinDatabase;
use danog\MadelineProto\MTProtoTools\PasswordCalculator;
use danog\MadelineProto\MTProtoTools\ReferenceDatabase;
use danog\MadelineProto\MTProtoTools\UpdatesState;
use danog\MadelineProto\NothingInTheSocketException;
use danog\MadelineProto\RSA;
use danog\MadelineProto\Serialization;
use danog\MadelineProto\SessionPaths;
use danog\MadelineProto\SettingsAbstract;
use danog\MadelineProto\SettingsEmpty;
use danog\MadelineProto\Snitch;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\TL\TLCallback;
use danog\MadelineProto\TL\TLConstructors;
use danog\MadelineProto\TL\TLMethods;
use danog\MadelineProto\TON\ADNLConnection;
use danog\MadelineProto\TON\APIFactory as TONAPIFactory;
use danog\MadelineProto\TON\InternalDoc as TONInternalDoc;
use danog\MadelineProto\TON\Lite;
use danog\MadelineProto\VoIP;
use danog\PhpDoc\PhpDocBuilder;

require 'vendor/autoload.php';

$ignore = [ // Disallow list
    AnnotationsBuilder::class,
    APIFactory::class,
    APIWrapper::class,
    AbstractAPIFactory::class,
    Bug74586Exception::class,
    Connection::class,
    ContextConnector::class,
    DataCenter::class,
    DataCenterConnection::class,
    DoHConnector::class,
    DocsBuilder::class,
    InternalDoc::class,
    Lang::class,
    LightState::class,
    Magic::class,
    PhpDocBuilder::class,
    RSA::class,
    Serialization::class,
    SessionPaths::class,
    SettingsEmpty::class,
    SettingsAbstract::class,
    Snitch::class,
    AsyncConstruct::class,
    VoIP::class,

    Crypt::class,
    NothingInTheSocketException::class,

    GarbageCollector::class,
    MinDatabase::class,
    PasswordCalculator::class,
    ReferenceDatabase::class,
    UpdatesState::class,

    TL::class,
    TLConstructors::class,
    TLMethods::class,
    TLCallback::class,

    ADNLConnection::class,
    TONAPIFactory::class,
    TONInternalDoc::class,
    Lite::class,

    \ArrayIterator::class,
];

$filter = function (string $class) use ($ignore): bool {
    if (\in_array($class, $ignore)) {
        return false;
    }
    if (\str_starts_with($class, 'danog\\MadelineProto\\Ipc')
    || \str_starts_with($class, 'danog\\MadelineProto\\Loop\\Update')
    || \str_starts_with($class, 'danog\\MadelineProto\\Loop\\Connection')
    || \str_starts_with($class, 'danog\\MadelineProto\\MTProto\\')
    || \str_starts_with($class, 'danog\\MadelineProto\\MTProtoSession\\')
    || \str_starts_with($class, 'danog\\MadelineProto\\PhpDoc\\')
    || \str_starts_with($class, 'danog\\MadelineProto\\Stream\\')
    || \str_starts_with($class, 'danog\\MadelineProto\\Db\\NullCache')) {
        return false;
    }
    if ($class === DbPropertiesTrait::class) {
        return true;
    }
    $class = new ReflectionClass($class);
    return !$class->isTrait();
};

PhpDocBuilder::fromNamespace()
    ->setFilter($filter)
    ->setOutput(__DIR__.'/../docs/docs/PHP/')
    ->setImage("https://docs.madelineproto.xyz/favicons/android-chrome-256x256.png")
    ->run();
