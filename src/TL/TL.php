<?php

declare(strict_types=1);

/**
 * TL module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\TL;

use Amp\Future;
use danog\MadelineProto\Lang;
use danog\MadelineProto\Logger;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\MTProto\MTProtoOutgoingMessage;
use danog\MadelineProto\SecretPeerNotInDbException;
use danog\MadelineProto\SecurityException;
use danog\MadelineProto\Settings\TLSchema;
use danog\MadelineProto\TL\Types\Button;
use danog\MadelineProto\TL\Types\Bytes;
use danog\MadelineProto\Tools;
use Webmozart\Assert\Assert;

use const STR_PAD_LEFT;

use function Amp\async;
use function Amp\Future\awaitAll;

/**
 * @psalm-import-type TBeforeMethodResponseDeserialization from TLCallback
 * @psalm-import-type TAfterMethodResponseDeserialization from TLCallback
 *
 * @psalm-import-type TBeforeConstructorSerialization from TLCallback
 * @psalm-import-type TBeforeConstructorDeserialization from TLCallback
 * @psalm-import-type TAfterConstructorDeserialization from TLCallback
 * @psalm-import-type TTypeMismatch from TLCallback
 *
 * TL serialization.
 *
 * @internal
 */
final class TL implements TLInterface
{
    /**
     * Highest available secret chat layer version.
     *
     */
    private int $secretLayer = -1;
    /**
     * Constructors.
     *
     */
    private TLConstructors $constructors;
    /**
     * Methods.
     *
     */
    private TLMethods $methods;
    /**
     * Descriptions.
     *
     */
    private array $tdDescriptions;

    /** @var array<string, list<TBeforeMethodResponseDeserialization>> */
    private array $beforeMethodResponseDeserialization;

    /** @var array<string, list<TAfterMethodResponseDeserialization>> */
    private array $afterMethodResponseDeserialization;

    /** @var array<string, TBeforeConstructorSerialization> */
    private array $beforeConstructorSerialization;
    /** @var array<string, list<TBeforeConstructorDeserialization>> */
    private array $beforeConstructorDeserialization;
    /** @var array<string, list<TAfterConstructorDeserialization>> */
    private array $afterConstructorDeserialization;

    /** @var array<string, TTypeMismatch> */
    private array $typeMismatch;

    /**
     * API instance.
     */
    private ?MTProto $API = null;
    public function __sleep()
    {
        return [
            'secretLayer',
            'constructors',
            'methods',
            'tdDescriptions',
            'API',
        ];
    }
    /**
     * Constructor function.
     *
     * @param MTProto $API API instance
     */
    public function __construct(?MTProto $API = null)
    {
        if ($API) {
            $this->API = $API;
        }
    }
    /**
     * Get secret chat layer version.
     */
    public function getSecretLayer(): int
    {
        return $this->secretLayer;
    }
    /**
     * Get constructors.
     */
    public function getConstructors(): TLConstructors
    {
        return $this->constructors;
    }
    /**
     * Get methods.
     */
    public function getMethods(): TLMethods
    {
        return $this->methods;
    }
    /**
     * Get TL descriptions.
     */
    public function getDescriptions(): array
    {
        return $this->tdDescriptions;
    }
    /**
     * Get TL descriptions.
     */
    public function &getDescriptionsRef(): array
    {
        return $this->tdDescriptions;
    }
    /**
     * Initialize TL parser.
     *
     * @param TLSchema     $files   Scheme files
     * @param list<TLCallback> $objects TL Callback objects
     */
    public function init(TLSchema $files, array $objects = []): void
    {
        $this->API?->logger?->logger('Loading TL schemes...', Logger::VERBOSE);
        $this->updateCallbacks($objects);
        $this->constructors = new TLConstructors();
        $this->methods = new TLMethods();
        $this->tdDescriptions = ['types' => [], 'constructors' => [], 'methods' => []];
        foreach (\array_filter([
            'api' => $files->getAPISchema(),
            'mtproto' => $files->getMTProtoSchema(),
            'secret' => $files->getSecretSchema(),
            ...$files->getOther(),
        ]) as $scheme_type => $file) {
            $this->API?->logger?->logger(\sprintf(Lang::$current_lang['file_parsing'], \basename($file)), Logger::VERBOSE);
            $filec = \file_get_contents(Tools::absolute($file));
            $TL_dict = \json_decode($filec, true);
            if ($TL_dict === null) {
                $TL_dict = ['methods' => [], 'constructors' => []];
                $type = 'constructors';
                $layer = null;
                $tl_file = \explode("\n", $filec);
                $key = 0;
                $e = null;
                $class = null;
                $dparams = [];
                $lineBuf = '';
                foreach ($tl_file as $line) {
                    $line = \rtrim($line);
                    if (\preg_match('|^//@|', $line)) {
                        $list = \explode(' @', \str_replace('//', ' ', $line));
                        foreach ($list as $elem) {
                            if ($elem === '') {
                                continue;
                            }
                            $elem = \explode(' ', $elem, 2);
                            if ($elem[0] === 'class') {
                                $elem = \explode(' ', $elem[1], 2);
                                $class = $elem[0];
                                continue;
                            }
                            if ($elem[0] === 'description') {
                                if (!\is_null($class)) {
                                    $this->tdDescriptions['types'][$class] = $elem[1];
                                    $class = null;
                                } else {
                                    $e = $elem[1];
                                }
                                continue;
                            }
                            if ($elem[0] === 'param_description') {
                                $elem[0] = 'description';
                            }
                            $dparams[$elem[0]] = $elem[1];
                        }
                        continue;
                    }
                    $line = \preg_replace(['|//.*|', '|^\\s+$|'], '', $line);
                    if ($line === '') {
                        continue;
                    }
                    if ($line === '---functions---') {
                        $type = 'methods';
                        continue;
                    }
                    if ($line === '---types---') {
                        $type = 'constructors';
                        continue;
                    }
                    if (\preg_match('|^===(\\d*)===|', $line, $matches)) {
                        $layer = (int) $matches[1];
                        continue;
                    }
                    if (\str_starts_with($line, 'vector#')) {
                        continue;
                    }
                    if (\str_contains($line, ' ?= ')) {
                        continue;
                    }
                    $line = \preg_replace(['/[(]([\\w\\.]+) ([\\w\\.]+)[)]/', '/\\s+/'], ['$1<$2>', ' '], $line);
                    if (!\str_contains($line, ';')) {
                        $lineBuf .= $line;
                        continue;
                    } elseif ($lineBuf) {
                        $lineBuf .= $line;
                        $line = $lineBuf;
                        $lineBuf = '';
                    }
                    $name = \preg_replace(['/#.*/', '/\\s.*/'], '', $line);
                    if (\in_array($name, ['bytes', 'int128', 'int256', 'int512', 'int', 'long', 'double', 'string', 'bytes', 'object', 'function'], true)) {
                        /*if (!(\in_array($scheme_type, ['ton_api', 'lite_api'], true) && $name === 'bytes')) {
                              continue;
                          }*/
                        continue;
                    }
                    if (\in_array($scheme_type, ['ton_api', 'lite_api'], true)) {
                        $clean = \preg_replace(['/;/', '/#[a-f0-9]+ /', '/ [a-zA-Z0-9_]+\\:flags\\.[0-9]+\\?true/', '/[<]/', '/[>]/', '/  /', '/^ /', '/ $/', '/{/', '/}/'], ['', ' ', '', ' ', ' ', ' ', '', '', '', ''], $line);
                    } else {
                        $clean = \preg_replace(['/:bytes /', '/;/', '/#[a-f0-9]+ /', '/ [a-zA-Z0-9_]+\\:flags\\.[0-9]+\\?true/', '/[<]/', '/[>]/', '/  /', '/^ /', '/ $/', '/\\?bytes /', '/{/', '/}/'], [':string ', '', ' ', '', ' ', ' ', ' ', '', '', '?string ', '', ''], $line);
                    }
                    $id = \hash('crc32b', $clean);
                    if (\preg_match('/^[^\\s]+#([a-f0-9]*)/i', $line, $matches)) {
                        $nid = \str_pad($matches[1], 8, '0', STR_PAD_LEFT);
                        /*if ($id !== $nid) {
                            $this->API?->logger?->logger(\sprintf('CRC32 mismatch (%s, %s) for %s', $id, $nid, $line), Logger::ERROR);
                        }*/
                        $id = $nid;
                    }
                    if (!\is_null($e)) {
                        $this->tdDescriptions[$type][$name] = ['description' => $e, 'params' => $dparams];
                        $e = null;
                        $dparams = [];
                    }
                    $TL_dict[$type][$key][$type === 'constructors' ? 'predicate' : 'method'] = $name;
                    $TL_dict[$type][$key]['id'] = $a = \strrev(\hex2bin($id));
                    $TL_dict[$type][$key]['params'] = [];
                    $TL_dict[$type][$key]['type'] = \preg_replace(['/.+\\s+=\\s+/', '/;/'], '', $line);
                    if ($layer !== null) {
                        $TL_dict[$type][$key]['layer'] = $layer;
                    }
                    if ($name !== 'vector' && $TL_dict[$type][$key]['type'] !== 'Vector t') {
                        foreach (\explode(' ', \preg_replace(['/^[^\\s]+\\s/', '/=\\s[^\\s]+/', '/\\s$/'], '', $line)) as $param) {
                            if ($param === '') {
                                continue;
                            }
                            if ($param[0] === '{') {
                                continue;
                            }
                            if ($param === '#') {
                                continue;
                            }
                            $explode = \explode(':', $param);
                            $TL_dict[$type][$key]['params'][] = ['name' => $explode[0], 'type' => $explode[1]];
                        }
                    }
                    $key++;
                }
            } else {
                foreach ($TL_dict['constructors'] as $key => $value) {
                    $TL_dict['constructors'][$key]['id'] = Tools::packSignedInt($TL_dict['constructors'][$key]['id']);
                }
                foreach ($TL_dict['methods'] as $key => $value) {
                    $TL_dict['methods'][$key]['id'] = Tools::packSignedInt($TL_dict['methods'][$key]['id']);
                }
            }

            if (empty($TL_dict) || empty($TL_dict['constructors']) || !isset($TL_dict['methods'])) {
                throw new Exception(Lang::$current_lang['src_file_invalid'].$file);
            }
            $this->API?->logger?->logger('Translating objects...', Logger::ULTRA_VERBOSE);
            foreach ($TL_dict['constructors'] as $elem) {
                if ($scheme_type === 'secret') {
                    $this->secretLayer = \max($this->secretLayer, $elem['layer']);
                }
                $this->constructors->add($elem, $scheme_type);
            }
            $this->API?->logger?->logger('Translating methods...', Logger::ULTRA_VERBOSE);
            foreach ($TL_dict['methods'] as $elem) {
                $this->methods->add($elem);
                if ($scheme_type === 'secret') {
                    $this->secretLayer = \max($this->secretLayer, $elem['layer']);
                }
            }
        }
        if (isset($files->getOther()['td'])) {
            foreach ($this->constructors->by_id as $id => $data) {
                $name = $data['predicate'];
                if ($this->constructors->findById($id) === false) {
                    unset($this->tdDescriptions['constructors'][$name]);
                } else {
                    if (!\count($this->tdDescriptions['constructors'][$name]['params'])) {
                        continue;
                    }
                    foreach ($this->tdDescriptions['constructors'][$name]['params'] as $k => $param) {
                        $this->tdDescriptions['constructors'][$name]['params'][$k] = \str_replace('nullable', 'optional', $param);
                    }
                }
            }
            foreach ($this->methods->by_id as $id => $data) {
                $name = $data['method'];
                if ($this->methods->findById($id) === false) {
                    unset($this->tdDescriptions['methods'][$name]);
                } else {
                    foreach ($this->tdDescriptions['methods'][$name]['params'] as $k => $param) {
                        $this->tdDescriptions['constructors'][$name]['params'][$k] = \str_replace('nullable', 'optional', $param);
                    }
                }
            }
        }
        $files->upgrade();
    }
    /**
     * Get TL namespaces.
     */
    public function getMethodNamespaces(): array
    {
        $res = [];
        foreach ($this->methods->method_namespace as $pair) {
            $a = \key($pair);
            $res[$a] = $a;
        }
        return $res;
    }
    /**
     * Get namespaced methods (method => namespace).
     */
    public function getMethodsNamespaced(): array
    {
        return $this->methods->method_namespace;
    }
    /**
     * Update TL callbacks.
     *
     * @param list<TLCallback> $callbacks TL callbacks
     */
    public function updateCallbacks(array $callbacks): void
    {
        $this->beforeMethodResponseDeserialization = $this->mergeCallbacks(\array_map(
            fn (TLCallback $t) => [
                $t->getMethodBeforeResponseDeserializationCallbacks(),
                $t->areDeserializationCallbacksMutuallyExclusive(),
                $t::class
            ],
            $callbacks
        ));
        $this->afterMethodResponseDeserialization = $this->mergeCallbacks(\array_map(
            fn (TLCallback $t) => [
                $t->getMethodAfterResponseDeserializationCallbacks(),
                $t->areDeserializationCallbacksMutuallyExclusive(),
                $t::class
            ],
            $callbacks
        ));

        $this->beforeConstructorSerialization = \array_merge(...\array_map(
            fn (TLCallback $t) => $t->getConstructorBeforeSerializationCallbacks(),
            $callbacks
        ));
        $this->beforeConstructorDeserialization = $this->mergeCallbacks(\array_map(
            fn (TLCallback $t) => [
                $t->getConstructorBeforeDeserializationCallbacks(),
                $t->areDeserializationCallbacksMutuallyExclusive(),
                $t::class
            ],
            $callbacks
        ));
        $this->afterConstructorDeserialization = $this->mergeCallbacks(\array_map(
            fn (TLCallback $t) => [
                $t->getConstructorAfterDeserializationCallbacks(),
                $t->areDeserializationCallbacksMutuallyExclusive(),
                $t::class
            ],
            $callbacks
        ));

        $this->typeMismatch = \array_merge(...\array_map(
            fn (TLCallback $t) => $t->getTypeMismatchCallbacks(),
            $callbacks
        ));
    }
    /**
     * @var array<string, list<list{(callable(mixed): void), array}>>
     */
    private array $mutexSideEffects = [];
    /**
     * @var list<Future>
     */
    private array $futureSideEffects = [];
    /**
     * @template T
     *
     * @param list<list{array<string, list<T>>, bool, string}> $callbacks
     * @return array<string, list<T>>
     */
    private function mergeCallbacks(array $callbacks): array
    {
        $result = [];
        foreach ($callbacks as [$map, $mutex, $queueId]) {
            foreach ($map as $constructor => $list) {
                $this->mutexSideEffects[$queueId] ??= [];
                if ($mutex) {
                    $result[$constructor] = [
                        ...$result[$constructor] ?? [],
                        function (...$v) use ($list, $queueId): void {
                            foreach ($list as $cb) {
                                $this->mutexSideEffects[$queueId][] = [$cb, $v];
                            }
                        }
                    ];
                } else {
                    $result[$constructor] = [
                        ...$result[$constructor] ?? [],
                        function (...$v) use ($list): void {
                            foreach ($list as $cb) {
                                $this->futureSideEffects[] = async($cb, ...$v);
                            }
                        }
                    ];
                }
            }
        }
        return $result;
    }
    /**
     * Deserialize bool.
     *
     * @param string $id Constructor ID
     */
    private function deserializeBool(string $id): bool
    {
        $tl_elem = $this->constructors->findById($id);
        if ($tl_elem === false) {
            throw new Exception(Lang::$current_lang['bool_error']);
        }
        return $tl_elem['predicate'] === 'boolTrue';
    }
    /**
     * Serialize TL object.
     *
     * @param array   $type   TL type definition
     * @param mixed   $object Object to serialize
     * @param string  $ctx    Context
     * @param integer $layer  Layer version
     */
    public function serializeObject(array $type, mixed $object, string|int $ctx, int $layer = -1)
    {
        switch ($type['type']) {
            case 'int':
                if (!\is_numeric($object)) {
                    throw new Exception(Lang::$current_lang['not_numeric']);
                }
                return Tools::packSignedInt((int) $object);
            case '#':
                if (!\is_int($object)) {
                    throw new Exception(Lang::$current_lang['not_numeric']);
                }
                return Tools::packUnsignedInt($object);
            case 'strlong':
                return $object;
            case 'long':
                if (\is_object($object)) {
                    return \str_pad(\strrev($object->toBytes()), 8, \chr(0));
                }
                if (\is_string($object) && \strlen($object) === 8) {
                    return $object;
                }
                if (\is_string($object) && \strlen($object) === 9 && $object[0] === 'a') {
                    return \substr($object, 1);
                }
                if (\is_array($object) && $type['name'] === 'hash') {
                    return Tools::genVectorHash($object);
                }
                if (\is_array($object) && \count($object) === 2) {
                    return \pack('l2', ...$object); // For bot API on 32bit
                }
                if (!\is_numeric($object)) {
                    throw new Exception(Lang::$current_lang['not_numeric']);
                }
                return Tools::packSignedLong((int) $object);
            case 'int128':
                if (\strlen($object) !== 16) {
                    $object = \base64_decode($object);
                    if (\strlen($object) !== 16) {
                        throw new Exception(Lang::$current_lang['long_not_16']);
                    }
                }
                return (string) $object;
            case 'int256':
                if (\strlen($object) !== 32) {
                    $object = \base64_decode($object);
                    if (\strlen($object) !== 32) {
                        throw new Exception(Lang::$current_lang['long_not_32']);
                    }
                }
                return (string) $object;
            case 'int512':
                if (\strlen($object) !== 64) {
                    $object = \base64_decode($object);
                    if (\strlen($object) !== 64) {
                        throw new Exception(Lang::$current_lang['long_not_64']);
                    }
                }
                return (string) $object;
            case 'double':
                return Tools::packDouble(\is_int($object) ? (float) $object : $object);
            case 'string':
                if ($object instanceof Bytes || \is_int($object) || \is_float($object)) {
                    $object = (string) $object;
                }
                if (!\is_string($object)) {
                    throw new Exception(Lang::$current_lang['string_required']);
                }
                $l = \strlen($object);
                $concat = '';
                if ($l <= 253) {
                    $concat .= \chr($l);
                    $concat .= $object;
                    $concat .= \pack('@'.Tools::posmod(-$l - 1, 4));
                } else {
                    $concat .= \chr(254);
                    $concat .= \substr(Tools::packSignedInt($l), 0, 3);
                    $concat .= $object;
                    $concat .= \pack('@'.Tools::posmod(-$l, 4));
                }
                return $concat;
            case 'bytes':
                if (\is_array($object) && isset($object['_']) && $object['_'] === 'bytes') {
                    $object = \base64_decode($object['bytes']);
                }
                if ($object instanceof Bytes || \is_int($object) || \is_float($object)) {
                    $object = (string) $object;
                }
                if (!\is_string($object)) {
                    throw new Exception(Lang::$current_lang['string_required']);
                }
                $l = \strlen($object);
                $concat = '';
                if ($l <= 253) {
                    $concat .= \chr($l);
                    $concat .= $object;
                    $concat .= \pack('@'.Tools::posmod(-$l - 1, 4));
                } else {
                    $concat .= \chr(254);
                    $concat .= \substr(Tools::packSignedInt($l), 0, 3);
                    $concat .= $object;
                    $concat .= \pack('@'.Tools::posmod(-$l, 4));
                }
                return $concat;
            case 'waveform':
                if (\is_array($object) && isset($object['_']) && $object['_'] === 'bytes') {
                    $object = \base64_decode($object['bytes']);
                }
                if (\is_array($object)) {
                    $object = self::compressWaveform($object);
                }
                if ($object instanceof Bytes) {
                    $object = (string) $object;
                }
                if (!\is_string($object)) {
                    throw new Exception(Lang::$current_lang['string_required']);
                }
                $l = \strlen($object);
                $concat = '';
                if ($l <= 253) {
                    $concat .= \chr($l);
                    $concat .= $object;
                    $concat .= \pack('@'.Tools::posmod(-$l - 1, 4));
                } else {
                    $concat .= \chr(254);
                    $concat .= \substr(Tools::packSignedInt($l), 0, 3);
                    $concat .= $object;
                    $concat .= \pack('@'.Tools::posmod(-$l, 4));
                }
                return $concat;
            case 'Bool':
                return $this->constructors->findByPredicate((bool) $object ? 'boolTrue' : 'boolFalse')['id'];
            case 'true':
                return;
            case '!X':
                return $object;
            case 'Vector t':
                if (!\is_array($object)) {
                    throw new Exception(Lang::$current_lang['array_invalid']);
                }
                if (isset($object['_'])) {
                    throw new Exception(\sprintf(Lang::$current_lang['array_invalid'], $type['subtype']));
                }
                $concat = $this->constructors->findByPredicate('vector')['id'];
                $concat .= Tools::packUnsignedInt(\count($object));
                foreach ($object as $k => $current_object) {
                    $concat .= ($this->serializeObject(['type' => $type['subtype']], $current_object, $k, $layer));
                }
                return $concat;
            case 'vector':
                if (!\is_array($object)) {
                    throw new Exception(Lang::$current_lang['array_invalid']);
                }
                $concat = Tools::packUnsignedInt(\count($object));
                foreach ($object as $k => $current_object) {
                    $concat .= ($this->serializeObject(['type' => $type['subtype']], $current_object, $k, $layer));
                }
                return $concat;
            case 'Object':
                if (\is_string($object)) {
                    return $object;
                }
        }
        if ($type['type'] === 'InputMessage' && !\is_array($object)) {
            $object = ['_' => 'inputMessageID', 'id' => $object];
        } elseif (isset($this->typeMismatch[$type['type']]) && (!\is_array($object) || isset($object['_']) && $this->constructors->findByPredicate($object['_'])['type'] !== $type['type'])) {
            $object = $this->typeMismatch[$type['type']]($object);
            if (!isset($object['_'])) {
                if (!isset($object[$type['type']])) {
                    throw new \danog\MadelineProto\Exception(\sprintf(Lang::$current_lang['could_not_convert_object'], $type['type']));
                }
                $object = $object[$type['type']];
            }
        }
        if (!isset($object['_'])) {
            $constructorData = $this->constructors->findByPredicate($type['type'], $layer);
            if ($constructorData === false) {
                throw new Exception(Lang::$current_lang['predicate_not_set']);
            }
            $object['_'] = $constructorData['predicate'];
        }
        if (isset($this->beforeConstructorSerialization[$object['_']])) {
            $object = $this->beforeConstructorSerialization[$object['_']]($object);
        }
        $predicate = $object['_'];
        $constructorData = $this->constructors->findByPredicate($predicate, $layer);
        if ($constructorData === false) {
            $this->API->logger->logger($object, Logger::FATAL_ERROR);
            throw new Exception(\sprintf(Lang::$current_lang['type_extract_error'], $predicate));
        }
        if ($bare = $type['type'] != '' && $type['type'][0] === '%') {
            $type['type'] = \substr($type['type'], 1);
        }
        if ($predicate === $type['type']) {
            $bare = true;
        }
        if ($predicate === 'messageEntityMentionName') {
            $constructorData = $this->constructors->findByPredicate('inputMessageEntityMentionName');
        }
        $concat = $bare ? '' : $constructorData['id'];
        return $concat.($this->serializeParams($constructorData, $object, '', $layer));
    }
    /**
     * Serialize method.
     *
     * @param string $method    Method name
     * @param mixed  $arguments Arguments
     */
    public function serializeMethod(string $method, mixed $arguments)
    {
        $tl = $this->methods->findByMethod($method);
        if ($tl === false) {
            throw new Exception(Lang::$current_lang['method_not_found'].$method);
        }
        return $tl['id'].$this->serializeParams($tl, $arguments, $method, -1);
    }
    /**
     * Serialize parameters.
     *
     * @param array   $tl        TL object definition
     * @param string  $ctx       Context
     * @param integer $layer     Layer
     */
    private function serializeParams(array $tl, array|Button $arguments, string|int $ctx, int $layer)
    {
        $serialized = '';
        $arguments = $this->API->botAPIToMTProto($arguments instanceof Button ? $arguments->jsonSerialize() : $arguments);
        foreach ($tl['params'] as $cur_flag) {
            if (isset($cur_flag['pow'])) {
                $arguments[$cur_flag['flag']] ??= 0;
                switch ($cur_flag['type']) {
                    case 'true':
                        $arguments[$cur_flag['flag']] = isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']] ? $arguments[$cur_flag['flag']] | $cur_flag['pow'] : $arguments[$cur_flag['flag']] & ~$cur_flag['pow'];
                        unset($arguments[$cur_flag['name']]);
                        break;
                    case 'Bool':
                        $arguments[$cur_flag['name']] = isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']] && ($arguments[$cur_flag['flag']] & $cur_flag['pow']) != 0;
                        if (($arguments[$cur_flag['flag']] & $cur_flag['pow']) === 0) {
                            unset($arguments[$cur_flag['name']]);
                        }
                        break;
                    default:
                        $arguments[$cur_flag['flag']] = isset($arguments[$cur_flag['name']]) && $arguments[$cur_flag['name']] !== null ? $arguments[$cur_flag['flag']] | $cur_flag['pow'] : $arguments[$cur_flag['flag']] & ~$cur_flag['pow'];
                        break;
                }
            }
        }
        foreach ($tl['params'] as $current_argument) {
            if (!isset($arguments[$current_argument['name']])) {
                if (isset($current_argument['pow']) && ($current_argument['type'] === 'true' || ($arguments[$current_argument['flag']] & $current_argument['pow']) === 0)) {
                    //$this->API->logger->logger('Skipping '.$current_argument['name'].' of type '.$current_argument['type');
                    continue;
                }
                if ($current_argument['name'] === 'random_bytes') {
                    $serialized .= $this->serializeObject(['type' => 'bytes'], Tools::random(15 + 4 * Tools::randomInt(modulus: 3)), 'random_bytes');
                    continue;
                }
                if ($current_argument['name'] === 'data' && isset($tl['method']) && \in_array($tl['method'], ['messages.sendEncrypted', 'messages.sendEncryptedFile', 'messages.sendEncryptedService'], true) && isset($arguments['message'])) {
                    $serialized .= $this->serializeObject($current_argument, $this->API->encryptSecretMessage($arguments['peer']['chat_id'], $arguments['message'], $arguments['queuePromise']), 'data');
                    continue;
                }
                if ($current_argument['name'] === 'random_id') {
                    switch ($current_argument['type']) {
                        case 'long':
                            $serialized .= Tools::random(8);
                            continue 2;
                        case 'int':
                            $serialized .= Tools::random(4);
                            continue 2;
                        case 'Vector t':
                            if (isset($arguments['id'])) {
                                $serialized .= $this->constructors->findByPredicate('vector')['id'];
                                $serialized .= Tools::packUnsignedInt(\count($arguments['id']));
                                $serialized .= Tools::random(8 * \count($arguments['id']));
                                continue 2;
                            }
                    }
                }
                if ($current_argument['type'] === 'long') {
                    $serialized .= "\0\0\0\0\0\0\0\0";
                    continue;
                }
                if ($current_argument['type'] === 'double') {
                    $serialized .= "\0\0\0\0\0\0\0\0";
                    continue;
                }
                if ($tl['type'] === 'InputMedia' && $current_argument['name'] === 'mime_type') {
                    $serialized .= ($this->serializeObject($current_argument, $arguments['file']['mime_type'], $current_argument['name'], $layer));
                    continue;
                }
                if (\in_array($current_argument['type'], ['bytes', 'string', 'int'], true)) {
                    $serialized .= "\0\0\0\0";
                    continue;
                }
                if (($id = $this->constructors->findByPredicate(\lcfirst($current_argument['type']).'Empty', $tl['layer'] ?? -1)) && $id['type'] === $current_argument['type']) {
                    $serialized .= $id['id'];
                    continue;
                }
                if (($id = $this->constructors->findByPredicate('input'.$current_argument['type'].'Empty', $tl['layer'] ?? -1)) && $id['type'] === $current_argument['type']) {
                    $serialized .= $id['id'];
                    continue;
                }
                switch ($current_argument['type']) {
                    case 'Vector t':
                    case 'vector':
                        $arguments[$current_argument['name']] = [];
                        break;
                    case 'DataJSON':
                    case '%DataJSON':
                        $arguments[$current_argument['name']] = null;
                        break;
                    default:
                        throw new Exception(Lang::$current_lang['params_missing'].' '.$current_argument['name']);
                }
            }
            if (\in_array($current_argument['type'], ['DataJSON', '%DataJSON'], true)) {
                $arguments[$current_argument['name']] = ['_' => 'dataJSON', 'data' => \json_encode($arguments[$current_argument['name']])];
            }
            if (isset($current_argument['subtype']) && \in_array($current_argument['subtype'], ['DataJSON', '%DataJSON'], true)) {
                \array_walk($arguments[$current_argument['name']], function (&$arg): void {
                    $arg = ['_' => 'dataJSON', 'data' => \json_encode($arg)];
                });
            }
            if ($current_argument['type'] === 'InputFile' && (!\is_array($arguments[$current_argument['name']]) || !(isset($arguments[$current_argument['name']]['_']) && $this->constructors->findByPredicate($arguments[$current_argument['name']]['_'])['type'] === 'InputFile'))) {
                $arguments[$current_argument['name']] = ($this->API->upload($arguments[$current_argument['name']]));
            }
            if ($current_argument['type'] === 'InputEncryptedChat' && (!\is_array($arguments[$current_argument['name']]) || isset($arguments[$current_argument['name']]['_']) && $this->constructors->findByPredicate($arguments[$current_argument['name']]['_'])['type'] !== $current_argument['type'])) {
                if (\is_array($arguments[$current_argument['name']])) {
                    $arguments[$current_argument['name']] = ($this->API->getInfo($arguments[$current_argument['name']]))['InputEncryptedChat'];
                } else {
                    if (!$this->API->hasSecretChat($arguments[$current_argument['name']])) {
                        throw new SecretPeerNotInDbException;
                    }
                    $arguments[$current_argument['name']] = $this->API->getSecretChat($arguments[$current_argument['name']])['InputEncryptedChat'];
                }
            }
            //$this->API->logger->logger('Serializing '.$current_argument['name'].' of type '.$current_argument['type');
            $serialized .= ($this->serializeObject($current_argument, $arguments[$current_argument['name']], $current_argument['name'], $layer));
        }
        return $serialized;
    }
    /**
     * Get length of TL payload.
     *
     * @param resource|string $stream Stream
     * @param array           $type   Type identifier
     */
    public function getLength($stream, array $type = ['type' => '']): int
    {
        if (\is_string($stream)) {
            $res = \fopen('php://memory', 'rw+b');
            \fwrite($res, $stream);
            \fseek($res, 0);
            $stream = $res;
        } elseif (!\is_resource($stream)) {
            throw new Exception(Lang::$current_lang['stream_handle_invalid']);
        }
        $this->deserialize($stream, $type);
        Assert::null($this->getSideEffects());
        return \ftell($stream);
    }

    /**
     * @var array<string, Future>
     */
    private array $lastMutexSideEffect = [];
    public function getSideEffects(): ?Future
    {
        foreach ($this->mutexSideEffects as $key => $sideEffects) {
            if (!$sideEffects) {
                continue;
            }
            $this->mutexSideEffects[$key] = [];
            $lastMutexSideEffect = $this->lastMutexSideEffect[$key] ?? null;
            $this->lastMutexSideEffect[$key] = async(function () use ($lastMutexSideEffect, $sideEffects): void {
                $lastMutexSideEffect?->await();
                foreach ($sideEffects as [$cb, $v]) {
                    $cb(...$v);
                }
            });
            $this->futureSideEffects []= $this->lastMutexSideEffect[$key];
        }
        if (!$this->futureSideEffects) {
            return null;
        }
        $sideEffects = $this->futureSideEffects;
        $this->futureSideEffects = [];
        return async(awaitAll(...), $sideEffects);
    }
    /**
     * Extracts a waveform.
     *
     * @internal Don't use this manually.
     */
    public static function extractWaveform(string $x): array
    {
        $values = \array_pad(\array_values(\unpack('C*', $x)), 63, 0);

        $result = \array_fill(0, 100, 0);
        $bitPos = 0;
        foreach ($result as &$value) {
            $start = $bitPos & 7;
            $bytePos = $bitPos >> 3;
            $value = $values[$bytePos] >> $start;
            if ($start > 3) {
                $value |= $values[$bytePos+1] << (8 - $start);
            }
            $value &= 31;

            $bitPos += 5;
        }
        return $result;
    }
    /**
     * Compresses a waveform.
     *
     * @internal Don't use this manually, just pass an array of integers to $attribute['waveform'].
     */
    public static function compressWaveform(array $x): string
    {
        if (\count($x) !== 100) {
            throw new Exception(Lang::$current_lang['waveform_must_have_100_values']);
        }
        $values = \array_fill(0, 63, 0);
        $bitPos = 0;
        foreach ($x as $value) {
            if (!\is_int($value) || $value < 0 || $value > 31) {
                throw new Exception(Lang::$current_lang['waveform_value']);
            }
            $start = $bitPos & 7;
            $bytePos = $bitPos >> 3;
            $values[$bytePos] |= ($value << $start) & 0xFF;
            if ($start > 3) {
                $values[$bytePos+1] |= $value >> (8 - $start);
            }
            $bitPos += 5;
        }
        return \pack('C63', ...$values);
    }
    /**
     * Deserialize TL object.
     *
     * @param string|resource $stream    Stream
     * @param array           $type      Type identifier
     */
    public function deserialize($stream, array $type)
    {
        if (\is_string($stream)) {
            $res = \fopen('php://memory', 'rw+b');
            \fwrite($res, $stream);
            \fseek($res, 0);
            $stream = $res;
        } elseif (!\is_resource($stream)) {
            throw new Exception(Lang::$current_lang['stream_handle_invalid']);
        }
        switch ($type['type']) {
            case 'Bool':
                return $this->deserializeBool(\stream_get_contents($stream, 4));
            case 'int':
                return Tools::unpackSignedInt(\stream_get_contents($stream, 4));
            case '#':
                return \unpack('V', \stream_get_contents($stream, 4))[1];
            case 'strlong':
                return \stream_get_contents($stream, 8);
            case 'long':
                return Tools::unpackSignedLong(\stream_get_contents($stream, 8));
            case 'double':
                return Tools::unpackDouble(\stream_get_contents($stream, 8));
            case 'int128':
                return \stream_get_contents($stream, 16);
            case 'int256':
                return \stream_get_contents($stream, 32);
            case 'int512':
                return \stream_get_contents($stream, 64);
            case 'waveform':
            case 'string':
            case 'bytes':
                $l = \ord(\stream_get_contents($stream, 1));
                if ($l > 254) {
                    throw new Exception(Lang::$current_lang['length_too_big']);
                }
                if ($l === 254) {
                    $long_len = \unpack('V', \stream_get_contents($stream, 3).\chr(0))[1];
                    $x = \stream_get_contents($stream, $long_len);
                    $resto = Tools::posmod(-$long_len, 4);
                    if ($resto > 0) {
                        \stream_get_contents($stream, $resto);
                    }
                } else {
                    $x = $l ? \stream_get_contents($stream, $l) : '';
                    $resto = Tools::posmod(-($l + 1), 4);
                    if ($resto > 0) {
                        \stream_get_contents($stream, $resto);
                    }
                }
                if (!\is_string($x)) {
                    throw new Exception("Generated value isn't a string");
                }
                if ($type['type'] === 'bytes') {
                    return new Types\Bytes($x);
                }
                if ($type['type'] === 'waveform') {
                    return self::extractWaveform($x);
                }
                return $x;
            case 'Vector t':
                $id = \stream_get_contents($stream, 4);
                $constructorData = $this->constructors->findById($id);
                if ($constructorData === false) {
                    $constructorData = $this->methods->findById($id);
                    $constructorData['predicate'] = 'method_'.$constructorData['method'];
                }
                if ($constructorData === false) {
                    throw new Exception(\sprintf(Lang::$current_lang['type_extract_error_id'], $type['type'], \bin2hex(\strrev($id))));
                }
                switch ($constructorData['predicate']) {
                    case 'gzip_packed':
                        return $this->deserialize(
                            \gzdecode(
                                (string) $this->deserialize(
                                    $stream,
                                    ['type' => 'bytes', 'connection' => $type['connection']],
                                ),
                            ),
                            ['type' => '', 'connection' => $type['connection']],
                        );
                    case 'Vector t':
                    case 'vector':
                        break;
                    default:
                        throw new Exception('Invalid vector constructor: '.$constructorData['predicate']);
                }
                // no break
            case 'vector':
                $count = \unpack('V', \stream_get_contents($stream, 4))[1];
                $result = [];
                $type['type'] = $type['subtype'];
                $splitSideEffects = isset($type['splitSideEffects']);
                if ($splitSideEffects) {
                    unset($type['splitSideEffects']);
                }
                for ($i = 0; $i < $count; $i++) {
                    $v = $this->deserialize($stream, $type);
                    if ($splitSideEffects) {
                        $v['sideEffects'] = $this->getSideEffects();
                    }
                    $result[] = $v;
                }
                return $result;
        }
        if ($type['type'] != '' && $type['type'][0] === '%') {
            $checkType = \substr($type['type'], 1);
            $constructorData = $this->constructors->findByType($checkType);
            if ($constructorData === false) {
                throw new Exception(Lang::$current_lang['constructor_not_found'].$checkType);
            }
        } else {
            $constructorData = $this->constructors->findByPredicate($type['type']);
            if ($constructorData === false) {
                $id = \stream_get_contents($stream, 4);
                $constructorData = $this->constructors->findById($id);
                if ($constructorData === false) {
                    $constructorData = $this->methods->findById($id);
                    if ($constructorData === false) {
                        throw new Exception(\sprintf(Lang::$current_lang['type_extract_error_id'], $type['type'], \bin2hex(\strrev($id))));
                    }
                    $constructorData['predicate'] = 'method_'.$constructorData['method'];
                }
            }
        }
        if ($constructorData['predicate'] === 'gzip_packed') {
            if (!isset($type['subtype'])) {
                $type['subtype'] = '';
            }
            return $this->deserialize(
                \gzdecode(
                    (string) $this->deserialize(
                        $stream,
                        ['type' => 'bytes'],
                    ),
                ),
                ['type' => '', 'connection' => $type['connection'], 'subtype' => $type['subtype']],
            );
        }
        if ($constructorData['type'] === 'Vector t') {
            $constructorData['connection'] = $type['connection'];
            $constructorData['subtype'] = $type['subtype'] ?? '';
            $constructorData['type'] = 'vector';
            return $this->deserialize($stream, $constructorData);
        }
        if ($constructorData['predicate'] === 'boolTrue') {
            return true;
        }
        if ($constructorData['predicate'] === 'boolFalse') {
            return false;
        }
        $x = ['_' => $constructorData['predicate']];
        if (isset($this->beforeConstructorDeserialization[$x['_']])) {
            foreach ($this->beforeConstructorDeserialization[$x['_']] as $callback) {
                $callback($x['_']);
            }
        }
        foreach ($constructorData['params'] as $arg) {
            if (isset($arg['pow'])) {
                switch ($arg['type']) {
                    case 'true':
                        $x[$arg['name']] = ($x[$arg['flag']] & $arg['pow']) !== 0;
                        continue 2;
                    case 'Bool':
                        if (($x[$arg['flag']] & $arg['pow']) === 0) {
                            $x[$arg['name']] = false;
                            continue 2;
                        }
                        // no break
                    default:
                        if (($x[$arg['flag']] & $arg['pow']) === 0) {
                            continue 2;
                        }
                }
            }
            if ($x['_'] === 'rpc_result' && $arg['name'] === 'result' && isset($type['connection']->outgoing_messages[$x['req_msg_id']])) {
                /** @var MTProtoOutgoingMessage */
                $message = $type['connection']->outgoing_messages[$x['req_msg_id']];
                foreach ($this->beforeMethodResponseDeserialization[$message->getConstructor()] ?? [] as $callback) {
                    $callback($type['connection']->outgoing_messages[$x['req_msg_id']]->getConstructor());
                }
                if ($message->getType() && \str_contains($message->getType(), '<')) {
                    $arg['subtype'] = \str_replace(['Vector<', '>'], '', $message->getType());
                }
            }
            if (isset($type['connection'])) {
                $arg['connection'] = $type['connection'];
            }
            $x[$arg['name']] = $this->deserialize($stream, $arg);
            if ($arg['name'] === 'random_bytes') {
                if (\strlen((string) $x[$arg['name']]) < 15) {
                    throw new SecurityException('Random_bytes is too small!');
                }
                unset($x[$arg['name']]);
            }
        }
        if ($x['_'] === 'dataJSON') {
            return \json_decode($x['data'], true);
        } elseif ($constructorData['type'] === 'JSONValue') {
            switch ($x['_']) {
                case 'jsonNull':
                    return;
                case 'jsonObject':
                    $res = [];
                    foreach ($x['value'] as $pair) {
                        $res[$pair['key']] = $pair['value'];
                    }
                    return $res;
                default:
                    return $x['value'];
            }
        } elseif ($x['_'] === 'photoStrippedSize') {
            $x['inflated'] = new Types\Bytes(Tools::inflateStripped((string) $x['bytes']));
        }
        if (isset($this->afterConstructorDeserialization[$x['_']])) {
            foreach ($this->afterConstructorDeserialization[$x['_']] as $callback) {
                $callback($x);
            }
        } elseif ($x['_'] === 'rpc_result'
            && isset($type['connection']->outgoing_messages[$x['req_msg_id']])
            && isset($this->afterMethodResponseDeserialization[$type['connection']->outgoing_messages[$x['req_msg_id']]->getConstructor()])) {
            foreach ($this->afterMethodResponseDeserialization[$type['connection']->outgoing_messages[$x['req_msg_id']]->getConstructor()] as $callback) {
                $callback($type['connection']->outgoing_messages[$x['req_msg_id']], $x['result']);
            }
        }
        /** @psalm-suppress InvalidArgument */
        if ($x['_'] === 'message' && isset($x['reply_markup']['rows'])) {
            foreach ($x['reply_markup']['rows'] as $key => $row) {
                foreach ($row['buttons'] as $bkey => $button) {
                    $x['reply_markup']['rows'][$key]['buttons'][$bkey] = new Types\Button($this->API, $x, $button);
                }
            }
        }
        unset($x['flags'], $x['flags2']);
        return $x;
    }
}
