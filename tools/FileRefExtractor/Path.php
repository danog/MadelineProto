<?php

declare(strict_types=1);

/**
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

namespace danog\MadelineProto\FileRefExtractor;

use AssertionError;
use danog\MadelineProto\FileRefExtractor\BuildMode\Ast;
use danog\MadelineProto\FileRefExtractor\Ops\CopyOp;
use Webmozart\Assert\Assert;

final readonly class Path
{
    final public const FLAG_UNPACK_ARRAY = 1;
    final public const FLAG_IF_ABSENT_ABORT = 2;
    final public const FLAG_PASSTHROUGH = 4;
    public function __construct(
        /** @var list<list{0: string, 1: string, 2?: int-mask-of<self::FLAG_*>|TypedOp}> */
        public array $path,
        public bool $isFromParent = false,
        private ?string $customName = null,
    ) {
        foreach ($path as $k => $elem) {
            if (\count($elem) !== 2 && \count($elem) !== 3) {
                throw new \InvalidArgumentException('Invalid path part: ' . json_encode($path));
            }
            if (isset($elem[2])) {
                if (!$elem[2] instanceof TypedOp
                && !\is_int($elem[2])
                ) {
                    throw new \InvalidArgumentException('Invalid path part: ' . json_encode($path));
                }
                if (\is_int($elem[2])) {
                    if (($elem[2] & (self::FLAG_IF_ABSENT_ABORT | self::FLAG_PASSTHROUGH)) === 6) {
                        throw new \InvalidArgumentException('Cannot use abort and passthrough flag at the same time: ' . json_encode($path));
                    }
                    if ($k !== \count($path) - 1 && ($elem[2] & self::FLAG_PASSTHROUGH) !== 0) {
                        throw new \InvalidArgumentException('Can only use passthrough flag on last element: ' . json_encode($path));
                    }
                }
            }
        }
    }

    public function normalize(array $stack, string $current, bool $ignoreFlag): ?self
    {
        $new = [];
        foreach ($this->path as $i => $part) {
            if ($ignoreFlag && \array_key_exists(2, $part) && \is_int($part[2]) && ($part[2] & self::FLAG_IF_ABSENT_ABORT)) {
                return null;
            }
            if (isset($part[2]) && $part[2] instanceof TypedOp) {
                $n = $part[2]->normalize($stack, $current, $ignoreFlag);
                if ($n === null) {
                    return null;
                }
                $part[2] = $n;
            }
            $new[$i] = $part;
        }
        if ($this->isFromParent) {
            // From parent
            if ($stack[0][0] === $this->path[0][0]) {
                return new self($new, true, $this->customName);
            }
            return null;
        }
        Assert::eq($this->path[0][0], $current);
        return new self(
            [...$stack, ...$new],
            false,
            $this->customName
        );
    }

    public function buildPath(TLContext $tl, string $extractor): string
    {
        $new = [];
        $finalK = \count($this->path) - 1;
        foreach ($this->path as $k => $part) {
            $consType = $tl->tl->getConstructorOrMethod($part[0])['type'];
            $newPart = [
                '_' => 'pathPart',
                'type' => $consType,
                'constructor' => $part[0],
                'param' => $part[1],
                'param_type' => $part[1] === '' ? $consType : $tl->tl->getParamType($part[0], $part[1]),
                'flag' => ['_' => 'paramNotFlag'],
            ];
            if (isset($part[2])) {
                if ($part[2] instanceof TypedOp) {
                    $fallback = $part[2]->build($tl);
                    array_walk_recursive($fallback, static function ($v): void {
                        if (\is_array($v)
                            && isset($v['_'])
                            && \in_array($v['_'], ['copyOp', 'getInputChannelByIdOp', 'getInputUserByIdOp', 'getInputPeerByIdOp'], true)
                        ) {
                            throw new \InvalidArgumentException("Cannot use {$v['_']} as fallback in TypedOp");
                        }
                    });
                    $newPart['flag'] = ['_' => 'paramIsFlagFallback', 'fallback' => $fallback];
                } elseif (\is_int($part[2])) {
                    if ($part[2] & self::FLAG_UNPACK_ARRAY) {
                        if ($tl->buildMode instanceof Ast && !$tl->buildMode->allowUnpacking) {
                            throw new AssertionError('Cannot use unpack_array flag in Ast mode with backrefs enabled');
                        }
                        $newPart['unpack_vector'] = true;
                    }
                    if ($part[2] & self::FLAG_IF_ABSENT_ABORT) {
                        $newPart['flag'] = ['_' => 'paramIsFlagAbortIfEmpty'];
                    }
                    if ($part[2] & self::FLAG_PASSTHROUGH) {
                        Assert::eq($k, $finalK, 'Can only use passthrough flag on last element');
                        $newPart['flag'] = ['_' => 'paramIsFlagPassthrough'];
                    }
                }
            }
            $new []= $newPart;
        }
        $newPart = end($new);

        $serialized = json_encode([$extractor, $this->isFromParent, $new], flags: JSON_THROW_ON_ERROR);
        if (isset($tl->buildMode->storedByPath[$serialized])) {
            $name = $tl->buildMode->storedByPath[$serialized];
        } else {
            $isFlag = $newPart['flag']['_'] === 'paramIsFlagPassthrough';
            $type = $this->getType($tl);
            if ($extractor === 'extractAndStore') {

            } elseif ($extractor === 'extractInputStickerSetFromDocumentAttributesAndStore') {
                Assert::eq($type, 'Vector<DocumentAttribute>');
                $type = 'InputStickerSet';
            } elseif ($extractor === 'extractInputStickerSetFromStickerSetAndStore') {
                Assert::eq($type, 'StickerSet');
                $type = 'InputStickerSet';
            } elseif ($extractor === 'extractPeerIdFromPeerAndStore') {
                Assert::eq($type, 'Peer');
                $type = 'long';
            } elseif ($extractor === 'extractPeerIdFromInputPeerAndStore') {
                Assert::eq($type, 'InputPeer');
                $type = 'long';
            } elseif ($extractor === 'extractChannelIdFromChannelAndStore') {
                Assert::eq($type, 'Channel');
                $type = 'long';
            } elseif ($extractor === 'extractChannelIdFromInputChannelAndStore') {
                Assert::eq($type, 'InputChannel');
                $type = 'long';
            } elseif ($extractor === 'extractUserIdFromUserAndStore') {
                Assert::eq($type, 'Channel');
                $type = 'long';
            } elseif ($extractor === 'extractUserIdFromInputUserAndStore') {
                Assert::eq($type, 'InputUser');
                $type = 'long';
            } else {
                throw new \InvalidArgumentException('Unknown extractor ' . $extractor);
            }
            if ($isFlag) {
                $flag = $tl->buildMode->storedFlags++;
                $type = "flags.$flag?$type";
            }
            $name = $this->customName ?? $tl->buildMode->curKey;
            Assert::notNull($name);
            if (isset($tl->buildMode->stored[$name])) {
                throw new AssertionError("Need custom name (already have $name) for ".json_encode($this->path));
            }
            $tl->buildMode->stored[$name] = [
                'type' => $type,
                'extractor' => [
                    '_' => $extractor,
                    'from' => ['_' => $this->isFromParent ? 'pathParent': 'path', 'parts' => $new],
                    'to' => $name,
                ],
            ];
            $tl->buildMode->storedByPath[$serialized] = $name;
        }
        if ($this->isFromParent) {
            $tl->buildMode->setNeedsParent($this->path[0][0]);
        }
        return $name;
    }
    public function getType(TLContext $tl): string
    {
        if ($this instanceof CopyOp) {
            Assert::eq($tl->position, $this->path[0][0], "getTypeAtPosition: Current constructor {$tl->position} does not match expected constructor {$this->path[0][0]}");
        }
        $path = $this->path;
        $idx = 0;
        $typeForReturn = null;
        $typeForCheck = null;
        do {
            [$requiredConstructor, $requiredParam] = $path[$idx];
            $expectFlag = $path[$idx][2] ?? null;

            if ($typeForCheck !== null) {
                $consOfType = $tl->tl->getConstructorsOfType($typeForCheck, true);
                $methodsOfType = $tl->tl->getMethodsOfType($typeForCheck, true);

                if (isset($consOfType[$requiredConstructor])) {
                    // OK
                } elseif (isset($methodsOfType[$requiredConstructor])) {
                    // OK
                } else {
                    throw new AssertionError("{$requiredConstructor} is NOT a constructor of type $typeForReturn, path: ".json_encode($path));
                }
            }
            $constructor = $tl->tl->tl->getConstructors()->findByPredicate($requiredConstructor);
            if ($constructor === false) {
                $constructor = $tl->tl->tl->getMethods()->findByMethod($requiredConstructor);
            }
            Assert::notFalse($constructor, "Constructor or method not found for path");

            $typeForReturn = null;
            if ($requiredParam === '') {
                Assert::true(isset($constructor['method']), "Expected method at position $idx in path ".json_encode($path));
                $param = $constructor;
                if (isset($param['subtype'])) {
                    if ($expectFlag & self::FLAG_UNPACK_ARRAY) {
                        $typeForReturn = "Vector<{$param['subtype']}>";
                        $typeForCheck = $param['subtype'];
                    } else {
                        $typeForReturn = "Vector<{$param['subtype']}>";
                        $typeForCheck = "Vector<{$param['subtype']}>";
                    }
                } else {
                    $typeForReturn = $param['type'];
                    $typeForCheck = $param['type'];
                    if (\is_int($expectFlag)) {
                        Assert::eq($expectFlag & self::FLAG_UNPACK_ARRAY, 0, "Expected no flag array at position $idx in path ".json_encode($path));
                    }
                }
                continue;
            }
            $n = $constructor['predicate'] ?? $constructor['method'];
            foreach ($constructor['params'] as $param) {
                if ($param['name'] === $requiredParam) {
                    if (isset($param['subtype'])) {
                        if ($expectFlag & self::FLAG_UNPACK_ARRAY) {
                            $typeForReturn = "Vector<{$param['subtype']}>";
                            $typeForCheck = $param['subtype'];
                        } else {
                            $typeForReturn = "Vector<{$param['subtype']}>";
                            $typeForCheck = "Vector<{$param['subtype']}>";
                        }
                    } else {
                        $typeForReturn = $param['type'];
                        $typeForCheck = $param['type'];
                        if (\is_int($expectFlag)) {
                            Assert::eq($expectFlag & self::FLAG_UNPACK_ARRAY, 0, "Expected no flag array at position $idx in path ".json_encode($path));
                        }
                    }

                    if (isset($param['pow'])) {
                        Assert::notNull($expectFlag);
                        if ($expectFlag instanceof TypedOp) {
                            Assert::eq($typeForReturn, $expectFlag->getType($tl));
                        } elseif ($expectFlag === null) {
                            throw new AssertionError("Got no flag at position $idx in path ".json_encode($path));
                        } elseif (0 === ($expectFlag & (self::FLAG_IF_ABSENT_ABORT | self::FLAG_PASSTHROUGH))) {
                            throw new AssertionError("Got no relevant flags at position $idx in path ".json_encode($path));
                        }
                    } elseif (($expectFlag & (self::FLAG_IF_ABSENT_ABORT | self::FLAG_PASSTHROUGH)) !== 0) {
                        throw new AssertionError("Expected no flag at position $idx, got $expectFlag in path ".json_encode($path));
                    }
                    break;
                }
            }
            Assert::notNull($typeForReturn, "Parameter {$requiredParam} not found in constructor or method $n");
            Assert::notNull($typeForCheck, "Parameter {$requiredParam} not found in constructor or method $n");
        } while (++$idx < \count($path));

        return $typeForReturn;
    }

}
