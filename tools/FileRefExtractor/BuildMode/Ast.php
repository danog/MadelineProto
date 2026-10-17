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

namespace danog\MadelineProto\FileRefExtractor\BuildMode;

use AssertionError;
use danog\MadelineProto\FileRefExtractor\BuildMode;
use danog\MadelineProto\FileRefExtractor\TLContext;
use danog\MadelineProto\FileRefExtractor\TLWrapper;
use danog\MadelineProto\Magic;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\TL;
use ReflectionClass;
use SebastianBergmann\Diff\Differ;
use Webmozart\Assert\Assert;

final class Ast implements BuildMode
{
    /** @var array<string, string> */
    public array $storedByPath = [];
    /** @var array<string, array{type: string, extractor: array}> */
    public array $stored = [];
    public int $storedFlags = 0;

    public ?string $curKey = null;

    private array $output = [];
    private array $skipped = [];
    private array $actions = [];
    private ?string $needsParent = null;
    private array $needsParentList = [];

    public function __construct(
        private readonly array $blacklistedPredicates,
        public readonly bool $allowUnpacking,
        private array $outputSchema = []
    ) {
    }

    public function getSources(): array
    {
        return $this->output;
    }
    public function getNeedsParentList(): array
    {
        return $this->needsParentList;
    }

    public static function crc(string $schema): string
    {
        $clean = preg_replace(['/:bytes /', '/;/', '/#[a-f0-9]+ /', '/ [a-zA-Z0-9_]+\\:flags\\.[0-9]+\\?true/', '/[<]/', '/[>]/', '/  /', '/^ /', '/ $/', '/\\?bytes /', '/{/', '/}/'], [':string ', '', ' ', '', ' ', ' ', ' ', '', '', '?string ', '', ''], $schema);
        $id = hash('crc32b', $clean);
        $id = str_pad($id, 8, '0', STR_PAD_LEFT);
        return $id;
    }

    public function finalize(
        TLWrapper $tl,
        int $layer,
        array $outgoingCons,
        array $incomingCons,
        array $incomingTraversalPairs,
        array $outgoingTraversalPairs,
        string $refMapFile,
        string $refMapFileJson
    ): void {
        $locations = [];

        $fileIdCons = [];
        foreach ($outgoingCons as $predicate => [$cons, $id, $fileref]) {
            $fileIdCons[$cons] = true;
            $locations[] = [
                '_' => 'locationOutgoing',
                'predicate' => $predicate,
                'type' => $tl->getConstructorOrMethod($predicate)['type'],
                //'id_field' => $id,
                //'file_reference_field' => $fileref,
                'stored_constructor' => $cons,
            ];
        }
        foreach ($incomingCons as $predicate => [$cons]) {
            $fileIdCons[$cons] = true;
            $locations[] = [
                '_' => 'locationIncoming',
                'predicate' => $predicate,
                'type' => $tl->getConstructorOrMethod($predicate)['type'],
                //'id_field' => $id,
                //'file_reference_field' => $fileref,
                'stored_constructor' => $cons,
            ];
        }
        $dbSchema = "boolFalse#bc799737 = Bool;\nboolTrue#997275b5 = Bool;\ntrue#3fedd339 = True;\nvector#1cb5c415 {t:Type} # [ t ] = Vector t;\n\n";
        foreach ($fileIdCons as $cons => $_) {
            $dbSchema .= $this->stringifySchema($cons, ['id' => 'long'], "FileId")."\n";
        }
        $dbSchema .= "\n";

        foreach ($this->outputSchema as $constructor => $params) {
            $dbSchema .= $this->stringifySchema($constructor, $params, "FileSource")."\n";
        }
        $dbSchemaJSON = (new TL(null))->toJson($dbSchema);

        $actions = [];
        foreach ($this->actions as $action) {
            if ($action['action']['_'] === 'callOp') {
                $action['action']['args'] = array_values($action['action']['args']);
            }
            $actions[] = $action;
        }
        $value = [
            '_' => 'fileReferenceMap',
            'layer' => $layer,
            'db_schema' => $dbSchema,
            'db_schema_json' => json_encode($dbSchemaJSON, flags: JSON_THROW_ON_ERROR),
            //'locations' => $locations,
            //'sources' => array_merge(...array_values($this->output)),
            'traversers_incoming' => $incomingTraversalPairs,
            'traversers_outgoing' => $outgoingTraversalPairs,
            'skipped_incoming_sources' => $this->skipped,
            'refresh_actions' => $actions,
        ];
        Magic::start(false);

        $s = new TLSchema;
        $s = $s->setOther(['filerefs' => __DIR__ . '/../../../src/TL_file_ref_map_schema.tl']);
        $TL = new TL((new ReflectionClass(MTProto::class))->newInstanceWithoutConstructor());
        $TL->init($s);

        $json = $TL->toJson(__DIR__ . '/../../../src/TL_file_ref_map_schema.tl');
        foreach ($json['constructors'] as $constructor) {
            Assert::keyNotExists($this->blacklistedPredicates, $constructor['predicate'], "{$constructor['predicate']} is blacklisted and cannot be used in the schema");
        }
        foreach ($json['methods'] as $method) {
            Assert::keyNotExists($this->blacklistedPredicates, $method['method'], "{$method['method']} is blacklisted and cannot be used in the schema");
        }

        $serialized = $TL->serializeObject(['type' => 'FileReferenceMap'], $value, '');
        $valueDe = $TL->deserialize($serialized, ['type' => '', 'connection' => null, 'encrypted' => true]);
        if ($value != $valueDe) {
            $differ = new Differ;
            $sortedValue = $this->sortKeysRecursive($value);
            $sortedValueDe = $this->sortKeysRecursive($valueDe);
            $diff = $differ->diff(
                json_encode($sortedValue, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
                json_encode($sortedValueDe, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
            );
            Assert::true(false, "Deserialized value does not match original value. Diff:\n$diff");
        }
        file_put_contents($refMapFile, $serialized);
        file_put_contents($refMapFileJson, json_encode($valueDe, flags: JSON_THROW_ON_ERROR));
    }

    private function stringifySchema(string $constructor, array $params, string $cType): string
    {
        Assert::keyNotExists($this->blacklistedPredicates, $constructor, "$constructor is blacklisted and cannot be used in the schema");
        $paramsStr = "$constructor ";
        foreach ($params as $name => $type) {
            Assert::notContains($type, 'InputPeer', "$constructor cannot contain InputPeer");
            Assert::notContains($type, 'InputUser', "$constructor cannot contain InputUser");
            Assert::notContains($type, 'InputChannel', "$constructor cannot contain InputChannel");
            if ($constructor !== 'fileSourceBotApp'
                && $constructor !== 'fileSourceTheme'
                && $constructor !== 'fileSourceWallPaper'
                && $constructor !== 'fileSourceSavedMusic'
            ) {
                Assert::notContains($name, 'access_hash', "$constructor cannot contain an access hash");
            }
            $paramsStr .= "$name:$type ";
        }
        $paramsStr .= "= $cType;";

        $id = self::crc($paramsStr);
        $paramsStr = substr($paramsStr, \strlen($constructor)+1);
        return "$constructor#$id $paramsStr";
    }
    public function addNode(TLContext $ctx, ?array $action = null, ?string $why = null): void
    {
        if ($action !== null) {
            Assert::keyExists($action, 'stored_constructor');

            $constructor = $action['stored_constructor'];
            unset($action['stored_constructor']);

            $stored = $this->stored;
            $flags = [];
            if ($this->storedFlags) {
                foreach ($stored as $name => ['type' => $type]) {
                    if (str_starts_with($type, 'flags.')) {
                        $flags[$name] = $type;
                    }
                }
                $stored = [
                    'flags' => ['type' => '#'],
                    ...$stored,
                ];
            }
            $skipped = [];

            if (isset($this->outputSchema[$constructor])) {
                $existing = $this->outputSchema[$constructor];
                foreach ($existing as $name => $type) {
                    if (str_starts_with($type, 'flags.')) {
                        if (isset($flags[$name])) {
                            unset($flags[$name], $stored[$name]);
                        } else {
                            $skipped[]= $name;
                        }
                    }
                }
                if ($flags) {
                    throw new AssertionError("Have leftover flags: ".implode(' ', $flags));
                }
                foreach ($existing as $name => $type) {
                    if (isset($stored[$name])) {
                        if ($stored[$name]['type'] === $type) {
                            unset($stored[$name]);
                        } else {
                            throw new AssertionError("Type mismatch for $constructor.$name: have {$stored[$name]['type']}, need $type");
                        }
                    } elseif (!str_starts_with($type, 'flags.') && $name !== 'flags') {
                        throw new AssertionError("Missing pre-existing parameter $constructor.$name for $constructor");
                    }
                }
                foreach ($stored as $name => ['type' => $type]) {
                    throw new AssertionError("Leftover parameter $constructor.$name:$type for ".$this->stringifySchema($constructor, $existing, 'FileSource'));
                }
            } else {
                $types = [];
                foreach ($stored as $name => ['type' => $type]) {
                    $types[$name] = $type;
                }
                $this->outputSchema[$constructor] = $types;
            }

            if (isset($this->actions[$constructor])) {
                $existingAction = $this->actions[$constructor];
                Assert::eq($constructor, $existingAction['stored_constructor']);
                $existingAction = $existingAction['action'];
                Assert::eq($existingAction['_'], $action['_']);

                // It's okay to fill missing params as the source of the data is always the same,
                // aka the source_constructor will always be of the same type, it should have all
                // needed flags, and the behavior will be consistent.
                if ($action['_'] === 'getMessageOp') {
                    foreach (['quick_reply_shortcut_id'] as $k) {
                        if (!isset($existingAction[$k]) && isset($action[$k])) {
                            $existingAction[$k] = $action[$k];
                        } elseif (isset($existingAction[$k]) && !isset($action[$k])) {
                            $action[$k] = $existingAction[$k];
                        }
                    }
                } else {
                    Assert::eq($action['_'], 'callOp');
                    foreach ($action['args'] as $k => $arg) {
                        Assert::string($k);
                        if (!isset($existingAction['args'][$arg['key']])) {
                            $existingAction['args'][$arg['key']] = $arg;
                        }
                    }
                    foreach ($existingAction['args'] as $k => $arg) {
                        Assert::string($k);
                        if (!isset($action['args'][$arg['key']])) {
                            $action['args'][$arg['key']] = $arg;
                        }
                    }
                }
                Assert::eq($existingAction, $action, "Mismatched actions for $constructor");

                $this->actions[$constructor]['action'] = $existingAction;
            } else {
                $this->actions[$constructor] = [
                    '_' => 'refreshAction',
                    'stored_constructor' => $constructor,
                    'action' => $action,
                ];
            }

            $needsNotParents = [];
            if ($action['_'] === 'getMessageOp') {
                $needsNotParents = [
                    [
                        '_' => 'predicate',
                        'predicate' => 'messages.getScheduledMessages',
                        'is_constructor' => false,
                    ],
                    [
                        '_' => 'predicate',
                        'predicate' => 'updateNewScheduledMessage',
                        'is_constructor' => true,
                    ],
                ];
            }

            $out = [
                '_' => 'source',
                'predicate' => [
                    '_' => 'predicate',
                    'predicate' => $ctx->position,
                    'is_constructor' => $ctx->isConstructor,
                ],
                'stored_constructor' => $constructor,
                'stored_params' => array_column($this->stored, 'extractor'),
                'skipped_flags' => $skipped,
                'needs_not_parents' => $needsNotParents,
            ];
            if ($this->needsParent !== null) {
                $out['needs_parent'] = [
                    '_' => 'predicate',
                    'predicate' => $this->needsParent,
                    'is_constructor' => $ctx->tl->isConstructor($this->needsParent),
                ];
                $this->needsParentList[$this->needsParent] = true;
            }
            $this->output[$ctx->position][] = $out;

            $this->storedFlags = 0;
            $this->stored = [];
            $this->storedByPath = [];
            Assert::null($why);
        } elseif ($why !== null) {
            $this->skipped[] = [
                '_' => 'skippedSource',
                'why' => $why,
                'predicate' => [
                    '_' => 'predicate',
                    'predicate' => $ctx->position,
                    'is_constructor' => $ctx->isConstructor,
                ],
            ];
            Assert::null($action);
            Assert::isEmpty($this->stored);
            Assert::isEmpty($this->storedByPath);
            Assert::eq($this->storedFlags, 0);
        } else {
            throw new AssertionError("Either 'action' or 'why' must be provided.");
        }
        $this->needsParent = null;
        $this->curKey = null;
    }

    public function getNeedsParent(): ?string
    {
        return $this->needsParent;
    }

    public function setNeedsParent(string $needsParent): void
    {
        if ($this->needsParent !== null && $this->needsParent !== $needsParent) {
            throw new \LogicException("Cannot change needsParent from {$this->needsParent} to {$needsParent} once it has been set.");
        }
        $this->needsParent = $needsParent;
    }
    private function sortKeysRecursive(array &$array): array
    {
        ksort($array);
        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                $array[$key] = $this->sortKeysRecursive($value);
            }
        }
        return $array;
    }
}
