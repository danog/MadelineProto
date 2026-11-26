<?php

declare(strict_types=1);

/**
 * Internal Psalm plugin for typing MadelineProto's internal methodCallAsyncRead / methodCallAsyncWrite calls.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Psalm;

use Amp\Cancellation;
use danog\MadelineProto\Connection;
use danog\MadelineProto\EventHandler\Media;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Message\RichMessage;
use danog\MadelineProto\Ipc\Client;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\SpecialMethodType;
use danog\MadelineProto\ParseMode;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use danog\MadelineProto\WrappedFuture;
use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\MethodParamsProviderEvent;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodParamsProviderInterface;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\PluginRegistrationSocket;
use Psalm\StatementsSource;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Union;
use SimpleXMLElement;

/**
 * Types the parameters and return values of internal `methodCallAsyncRead` and `methodCallAsyncWrite` calls
 * directly from MadelineProto's TL schema.
 *
 * @internal
 */
final class MethodCallPlugin implements
    PluginEntryPointInterface,
    MethodReturnTypeProviderInterface,
    MethodParamsProviderInterface
{
    private const HANDLED_METHODS = [
        'methodcallasyncread' => true,
        'methodcallasyncwrite' => true,
    ];

    private const MAX_EXPANSION_DEPTH = 2;
    private const MAX_CONSTRUCTORS_PER_UNION = 24;

    private static ?TL $tl = null;

    /** @var array<string, list<array<string, mixed>>>|null */
    private static ?array $constructorsByType = null;

    /** @var array<string, Union> */
    private static array $returnTypeCache = [];

    /** @var array<string, Union> */
    private static array $argsTypeCache = [];

    /** @var array<string, Union> */
    private static array $tlTypeCache = [];

    /** @var array<string, Union>|null */
    private static ?array $commonExtraParamTypes = null;

    #[\Override]
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void
    {
        $registration->registerHooksFromClass(self::class);
    }

    /**
     * @return list<class-string>
     */
    #[\Override]
    public static function getClassLikeNames(): array
    {
        return [
            MTProto::class,
            Connection::class,
            Client::class,
        ];
    }

    #[\Override]
    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $methodLower = $event->getMethodNameLowercase();
        if (!isset(self::HANDLED_METHODS[$methodLower])) {
            return null;
        }
        $tlMethod = self::extractMethodName($event->getCallArgs(), $event->getSource());
        if ($tlMethod === null) {
            return null;
        }
        $returnType = self::resolveMethodReturnType($tlMethod, $event->getSource()->getCodebase());
        if ($returnType === null) {
            return null;
        }
        if ($methodLower === 'methodcallasyncwrite') {
            return new Union([new TGenericObject(WrappedFuture::class, [$returnType])]);
        }
        return $returnType;
    }

    /**
     * @return ?list<FunctionLikeParameter>
     */
    #[\Override]
    public static function getMethodParams(MethodParamsProviderEvent $event): ?array
    {
        $methodLower = $event->getMethodNameLowercase();
        if (!isset(self::HANDLED_METHODS[$methodLower])) {
            return null;
        }
        $callArgs = $event->getCallArgs();
        if ($callArgs === null) {
            return null;
        }
        $tlMethod = self::extractMethodName($callArgs, $event->getStatementsSource());
        if ($tlMethod === null) {
            return null;
        }
        $argsType = self::resolveMethodArgsType($tlMethod, $event->getStatementsSource()?->getCodebase());
        if ($argsType === null) {
            return null;
        }

        $params = [
            new FunctionLikeParameter('method', false, Type::getString()),
            new FunctionLikeParameter('args', false, $argsType),
        ];
        if (strtolower($event->getFqClasslikeName()) !== strtolower(Connection::class)) {
            $params[] = new FunctionLikeParameter(
                'datacenter',
                false,
                new Union([new TInt(), new TNull()]),
                is_optional: true,
                default_type: Type::getNull(),
            );
        }
        return $params;
    }

    /**
     * @param list<PhpParser\Node\Arg> $callArgs
     */
    private static function extractMethodName(array $callArgs, ?StatementsSource $source): ?string
    {
        if (!isset($callArgs[0])) {
            return null;
        }
        $first = $callArgs[0]->value;
        if ($first instanceof PhpParser\Node\Scalar\String_) {
            return $first->value;
        }
        if ($source !== null) {
            $argType = $source->getNodeTypeProvider()->getType($first);
            if ($argType !== null && $argType->isSingleStringLiteral()) {
                return $argType->getSingleStringLiteral()->value;
            }
        }
        return null;
    }

    private static function getTL(): TL
    {
        if (self::$tl === null) {
            $tl = new TL();
            $tl->init(new TLSchema());
            self::$tl = $tl;
        }
        return self::$tl;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function getConstructorsForType(string $type): array
    {
        if (self::$constructorsByType === null) {
            self::$constructorsByType = [];
            foreach (self::getTL()->getConstructors()->by_id as $constructor) {
                self::$constructorsByType[$constructor['type']][] = $constructor;
            }
        }
        return self::$constructorsByType[$type] ?? [];
    }

    public static function resolveMethodReturnType(string $method, ?Codebase $codebase = null): ?Union
    {
        if (isset(self::$returnTypeCache[$method])) {
            return self::$returnTypeCache[$method];
        }
        $info = self::getTL()->getMethods()->findByMethod($method);
        if ($info === false) {
            return null;
        }
        $tlType = isset($info['subtype']) ? 'Vector<'.ltrim((string) $info['subtype'], '%').'>' : ltrim((string) $info['type'], '%');
        return self::$returnTypeCache[$method] = self::tlTypeToUnion($tlType, false, 0, [], $codebase);
    }

    public static function resolveMethodArgsType(string $method, ?Codebase $codebase = null): ?Union
    {
        if (isset(self::$argsTypeCache[$method])) {
            return self::$argsTypeCache[$method];
        }
        $info = self::getTL()->getMethods()->findByMethod($method);
        if ($info === false) {
            return null;
        }

        $properties = self::getCommonExtraParamTypes();
        $isSecretSend = \in_array($method, ['messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService'], true);
        if ($isSecretSend) {
            $properties['message'] = self::arrayType()->setPossiblyUndefined(true);
        }

        $hasEntities = false;
        $hasPeer = false;
        $hasMessage = false;
        foreach ($info['params'] as $param) {
            $name = (string) $param['name'];
            if (\in_array($name, ['flags', 'flags2', 'random_bytes'], true)) {
                continue;
            }
            $paramType = ltrim((string) $param['type'], '%');
            $isVector = isset($param['subtype']);
            if ($isVector) {
                $paramType = 'Vector<'.ltrim((string) $param['subtype'], '%').'>';
            }
            if ($name === 'chat_id' && $method !== 'messages.discardEncryption') {
                $paramType = 'InputPeer';
            }
            if ($name === 'hash' && $paramType === 'long') {
                $intOrString = new Union([new TInt(), new TString()]);
                $union = TypeCombiner::combine([
                    new TInt(),
                    new TString(),
                    Type::getListAtomic($intOrString),
                ], $codebase);
                $optional = true;
            } else {
                $union = self::tlTypeToUnion($paramType, true, 0, [], $codebase);
                $optional = isset($param['pow'])
                    || $isVector
                    || $name === 'random_id'
                    || $name === 'reply_to'
                    || ($isSecretSend && $name === 'data')
                    || \in_array($paramType, ['int', 'long', 'strlong', 'string', 'bytes', 'Bool', 'true'], true)
                    || self::getTL()->getConstructors()->findByPredicate(lcfirst($paramType).'Empty') !== false;
            }

            if ($optional && !$union->isMixed()) {
                $union = self::withNull($union);
            }
            if ($optional) {
                $union = $union->setPossiblyUndefined(true);
            }
            $properties[$name] = $union;

            if ($name === 'entities') {
                $hasEntities = true;
            }
            if ($name === 'peer') {
                $hasPeer = true;
            }
            if ($name === 'message') {
                $hasMessage = true;
            }
        }

        if ($hasEntities || $hasMessage) {
            $properties['parse_mode'] = (new Union([
                new TString(),
                self::arrayAtomic(),
                new TNamedObject(ParseMode::class),
                new TNull(),
            ]))->setPossiblyUndefined(true);
            $properties['reply_to_msg_id'] = (new Union([new TInt(), new TNull()]))->setPossiblyUndefined(true);
            $properties['caption'] = (new Union([new TString(), new TNull()]))->setPossiblyUndefined(true);
        }
        if ($hasPeer && !isset($properties['chat_id'])) {
            $properties['chat_id'] = (new Union([
                self::arrayAtomic(),
                new TInt(),
                new TString(),
                new TNull(),
            ]))->setPossiblyUndefined(true);
        }

        return self::$argsTypeCache[$method] = new Union([TKeyedArray::make($properties)]);
    }

    /**
     * @return array<string, Union>
     */
    private static function getCommonExtraParamTypes(): array
    {
        if (self::$commonExtraParamTypes === null) {
            $nullableInt = new Union([new TInt(), new TNull()]);
            $nullableString = new Union([new TString(), new TNull()]);
            $nullableBool = new Union([new TBool(), new TNull()]);
            self::$commonExtraParamTypes = [
                '_' => Type::getString()->setPossiblyUndefined(true),
                'floodWaitLimit' => $nullableInt->setPossiblyUndefined(true),
                'queueId' => $nullableString->setPossiblyUndefined(true),
                'cancellation' => (new Union([new TNamedObject(Cancellation::class), new TNull()]))->setPossiblyUndefined(true),
                'specialMethodType' => (new Union([new TNamedObject(SpecialMethodType::class), new TNull()]))->setPossiblyUndefined(true),
                'madelineMsgId' => $nullableInt->setPossiblyUndefined(true),
                'timeout' => (new Union([new TInt(), new TFloat(), new TNull()]))->setPossiblyUndefined(true),
                'resumable' => $nullableBool->setPossiblyUndefined(true),
                'resumableCallId' => $nullableString->setPossiblyUndefined(true),
                'resumableCall' => $nullableString->setPossiblyUndefined(true),
                'takeoutId' => $nullableInt->setPossiblyUndefined(true),
                'businessConnectionId' => $nullableString->setPossiblyUndefined(true),
                'noResponse' => $nullableBool->setPossiblyUndefined(true),
                'raw' => $nullableBool->setPossiblyUndefined(true),
                'multiple' => $nullableBool->setPossiblyUndefined(true),
                'applies_to_message' => Type::getMixed()->setPossiblyUndefined(true),
            ];
        }
        return self::$commonExtraParamTypes;
    }

    /**
     * @return array{bool, string}
     */
    private static function unwrapVector(string $type): array
    {
        if (preg_match('/^(?:V|v)ector<(.+)>$/', $type, $m)) {
            return [true, ltrim($m[1], '%')];
        }
        return [false, ltrim($type, '%')];
    }

    /**
     * Convert a TL type directly into a Psalm Union without string parsing.
     *
     * @param array<string, true> $stack
     */
    private static function tlTypeToUnion(string $type, bool $input, int $depth = 0, array $stack = [], ?Codebase $codebase = null): Union
    {
        if ($depth === 0 && $stack === []) {
            $cacheKey = ($input ? 'in:' : 'out:').$type;
            if (isset(self::$tlTypeCache[$cacheKey])) {
                return self::$tlTypeCache[$cacheKey];
            }
        }

        [$isVector, $inner] = self::unwrapVector($type);
        $base = self::resolveBaseType($inner, $input, $depth, $stack, $codebase);

        $result = $isVector ? Type::getList($base) : $base;

        if ($depth === 0 && $stack === []) {
            self::$tlTypeCache[($input ? 'in:' : 'out:').$type] = $result;
        }
        return $result;
    }

    /**
     * @param array<string, true> $stack
     */
    private static function resolveBaseType(string $inner, bool $input, int $depth, array $stack, ?Codebase $codebase): Union
    {
        $primitive = match ($inner) {
            'string', 'bytes', 'int128', 'int256', 'int512' => Type::getString(),
            'waveform' => Type::getList(Type::getInt()),
            'int', 'long', 'strlong', 'int53' => Type::getInt(),
            'int|string', 'long|string' => new Union([new TInt(), new TString()]),
            'double', 'float' => Type::getFloat(),
            'Bool', 'true' => Type::getBool(),
            'DataJSON', 'JSONValue', 'X', '!X', 'Object' => Type::getMixed(),
            'Updates', 'PollResults' => self::arrayType(),
            default => null,
        };
        if ($primitive !== null) {
            return $primitive;
        }

        if ($input) {
            $inputSpecial = match ($inner) {
                'User', 'InputUser', 'Chat', 'InputChannel', 'Peer', 'InputPeer' => new Union([self::arrayAtomic(), new TInt(), new TString()]),
                'InputFile', 'InputEncryptedFile' => Type::getMixed(),
                'InputEncryptedChat', 'InputMessage' => new Union([self::arrayAtomic(), new TInt()]),
                'InputMedia' => new Union([new TNamedObject(Media::class), self::arrayAtomic(), new TString()]),
                'InputCheckPasswordSRP' => new Union([new TString(), self::arrayAtomic()]),
                'RichMessage' => new Union([new TNamedObject(RichMessage::class), self::arrayAtomic(), new TString()]),
                'InputPhoto', 'InputDocument' => new Union([self::arrayAtomic(), new TString(), new TNamedObject(Media::class), new TNamedObject(Message::class)]),
                'ReplyMarkup', 'KeyboardButton' => self::arrayType(),
                default => null,
            };
            if ($inputSpecial !== null) {
                return $inputSpecial;
            }
        } else {
            $outputSpecial = match ($inner) {
                'ReplyMarkup', 'InputFile', 'InputEncryptedFile', 'InputEncryptedChat',
                'InputPhoto', 'InputDocument', 'InputMedia', 'InputMessage',
                'KeyboardButton', 'InputCheckPasswordSRP' => self::arrayType(),
                default => null,
            };
            if ($outputSpecial !== null) {
                return $outputSpecial;
            }
        }

        // Collapse deeply nested recursive containers to generic array to keep union sizes fast and bounded.
        if ($depth >= self::MAX_EXPANSION_DEPTH) {
            return self::arrayType();
        }
        if ($depth >= 1 && \in_array($inner, [
            'User', 'Chat', 'Message', 'MessageMedia', 'WebPage', 'Document', 'Photo',
            'UserFull', 'ChatFull', 'BotInfo', 'StoryItem', 'DecryptedMessage',
            'channels.AdminLogResults', 'messages.Messages', 'messages.Dialogs',
            'messages.BotResults', 'InputBotInlineResult',
        ], true)) {
            return self::arrayType();
        }

        $constructors = self::getConstructorsForType($inner);
        if ($constructors === [] || \count($constructors) > self::MAX_CONSTRUCTORS_PER_UNION) {
            return self::arrayType();
        }

        /** @var list<Atomic> $atomics */
        $atomics = [];
        foreach ($constructors as $constructor) {
            /** @var non-empty-array<string, Union> $fields */
            $fields = [
                '_' => new Union([TLiteralString::make((string) $constructor['predicate'])]),
            ];
            $cyclic = false;
            foreach ($constructor['params'] as $param) {
                $pName = (string) $param['name'];
                if (\in_array($pName, ['flags', 'flags2', 'random_bytes'], true)) {
                    continue;
                }
                $pType = isset($param['subtype'])
                    ? 'Vector<'.ltrim((string) $param['subtype'], '%').'>'
                    : ltrim((string) $param['type'], '%');
                if (isset($stack[$pType])) {
                    $cyclic = true;
                    break;
                }
                $stack[$pType] = true;
                $fieldUnion = self::tlTypeToUnion($pType, $input, $depth + 1, $stack, $codebase);
                unset($stack[$pType]);

                if ($input) {
                    $opt = isset($param['pow'])
                        || isset($param['subtype'])
                        || \in_array($pType, ['int', 'long', 'strlong', 'string', 'bytes', 'Bool', 'true'], true);
                    if ($opt && !$fieldUnion->isMixed()) {
                        $fieldUnion = self::withNull($fieldUnion);
                    }
                } else {
                    $opt = isset($param['pow']) && \is_int($param['pow']) && $pType !== 'true';
                }
                if ($opt) {
                    $fieldUnion = $fieldUnion->setPossiblyUndefined(true);
                }
                $fields[$pName] = $fieldUnion;
            }
            if (!$cyclic) {
                $atomics[] = TKeyedArray::make($fields);
            }
        }

        return $atomics !== [] ? TypeCombiner::combine($atomics, $codebase) : self::arrayType();
    }

    private static function withNull(Union $union): Union
    {
        if ($union->isNullable()) {
            return $union;
        }
        $types = array_values($union->getAtomicTypes());
        $types[] = new TNull();
        return new Union($types);
    }

    private static function arrayAtomic(): TArray
    {
        return new TArray([Type::getArrayKey(), Type::getMixed()]);
    }

    private static function arrayType(): Union
    {
        return new Union([self::arrayAtomic()]);
    }
}
