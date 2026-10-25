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
use InvalidArgumentException;

/**
 * Renders the generated blocks of the file reference documentation (https://core.telegram.org/api/file-references) from the file reference map.
 *
 * A generated block is delimited by `<!-- start NAME [INDENT] -->` and `<!-- end NAME -->`: only the content between the markers is replaced.
 * INDENT is the nesting level of the block within a markdown list (two spaces per level).
 *
 * @internal
 */
final class DocsRenderer
{
    private const SOURCES_PAGE = '/api/file-references/sources';

    /** Incoming traversers, sorted by constructor and name. */
    private readonly array $incoming;
    /** Outgoing traversers, sorted by constructor and name. */
    private readonly array $outgoing;
    /** @var array<string, true> */
    private array $anchors = [];

    /**
     * @param array  $map       Decoded JSON file reference map (src/file_ref_map.json).
     * @param string $mapSchema TL schema of the map file (src/TL_file_ref_map_schema.tl).
     */
    public function __construct(private readonly array $map, private readonly string $mapSchema)
    {
        $this->incoming = self::sortTraversers($map['traversers_incoming']);
        $this->outgoing = self::sortTraversers($map['traversers_outgoing']);
        // Check that the anchors of all sources are unique.
        foreach ($this->incoming as $traverser) {
            foreach ($traverser['push_sources'] ?? [] as $source) {
                $anchor = self::sourceAnchor($source);
                if (isset($this->anchors[$anchor])) {
                    throw new AssertionError("Duplicate source anchor $anchor");
                }
                $this->anchors[$anchor] = true;
            }
        }
    }

    /**
     * Replace the content of all generated blocks of a documentation page.
     */
    public function renderPage(string $page): string
    {
        $result = preg_replace_callback(
            '/^([ \t]*<!-- start ([a-z_ ]+?)(?: (\d+))? -->\n)(.*?)(\n[ \t]*<!-- end \2 -->)$/ms',
            function (array $matches): string {
                $indent = str_repeat('  ', (int) ($matches[3] ?? 0));
                $content = $this->renderBlock($matches[2]);
                $content = implode("\n", array_map(
                    static fn (string $line): string => $line === '' ? '' : $indent.$line,
                    explode("\n", $content),
                ));
                return $matches[1]."\n".$content."\n".$matches[5];
            },
            $page,
            flags: PREG_UNMATCHED_AS_NULL,
        );
        if ($result === null) {
            throw new AssertionError(preg_last_error_msg());
        }
        // Every start marker must have been matched by the regex above, with its end marker.
        preg_match_all('/<!-- start ([a-z_ ]+?)(?: \d+)? -->/', $page, $starts);
        preg_match_all('/<!-- end ([a-z_ ]+?) -->/', $page, $ends);
        if ($starts[1] !== $ends[1]) {
            throw new AssertionError('Mismatched block markers: '.json_encode($starts[1]).' vs '.json_encode($ends[1]));
        }
        return $result;
    }

    /**
     * Render the content of a generated block.
     */
    public function renderBlock(string $name): string
    {
        return match ($name) {
            'db schema' => self::codeBlock($this->map['db_schema']),
            'schema' => self::codeBlock($this->mapSchema),
            'fileid' => self::codeBlock($this->dbSchemaLines('fileId')),
            'filesource' => self::codeBlock($this->dbSchemaLines('fileSource')),
            'traversers_incoming' => self::list(array_map($this->renderTraverser(...), $this->incoming)),
            'traversers_outgoing' => self::list(array_map($this->renderTraverser(...), $this->outgoing)),
            'incoming_locations' => self::list(array_map(
                $this->renderTraverser(...),
                self::filterTraversers($this->map['traversers_incoming'], 'traverseCommitSourceLocation'),
            )),
            'outgoing_locations' => self::list(array_map(
                $this->renderTraverser(...),
                self::filterTraversers($this->map['traversers_outgoing'], 'traverseSwapLocation'),
            )),
            'sources' => self::list($this->renderSources()),
            'skipped_incoming_sources' => self::list(array_map(
                static fn (array $skipped): string => self::mdLink($skipped['predicate']['predicate'], $skipped['predicate']['is_constructor']).' - '.$skipped['why'],
                $this->map['skipped_incoming_sources'],
            )),
            'refresh_actions' => self::list(array_map(
                fn (array $action): string => $action['stored_constructor'].' - '.self::code($this->renderOp($action['action'])),
                $this->map['refresh_actions'],
            )),
            default => throw new InvalidArgumentException("Unknown generated block $name"),
        };
    }

    private static function sortTraversers(array $traversers): array
    {
        usort(
            $traversers,
            static fn (array $a, array $b): int => [$a['_'], $a['predicate'] ?? $a['name']] <=> [$b['_'], $b['predicate'] ?? $b['name']],
        );
        return $traversers;
    }

    /** Traversers of the given constructor, in map order. */
    private static function filterTraversers(array $traversers, string $constructor): array
    {
        return array_values(array_filter($traversers, static fn (array $t): bool => $t['_'] === $constructor));
    }

    private function dbSchemaLines(string $prefix): string
    {
        $lines = array_filter(
            explode("\n", $this->map['db_schema']),
            static fn (string $line): bool => str_starts_with($line, $prefix),
        );
        return implode("\n", $lines);
    }

    private static function codeBlock(string $content): string
    {
        return "```\n$content\n```";
    }

    private static function list(array $items): string
    {
        return implode("\n", array_map(static fn (string $item): string => "- $item", $items));
    }

    private static function code(string $code): string
    {
        return "<pre><code>$code</code></pre>";
    }

    private static function kind(bool $isConstructor): string
    {
        return $isConstructor ? 'constructor' : 'method';
    }

    /** Markdown link to a constructor or method. */
    private static function mdLink(string $name, bool $isConstructor): string
    {
        return "[$name](/".self::kind($isConstructor)."/$name)";
    }

    /** HTML link to a constructor, method or type, for use within code blocks. */
    private static function htmlLink(string $name, string $kind): string
    {
        return "<a href='/$kind/$name'>$name</a>";
    }

    private static function predicateLink(array $predicate): string
    {
        return self::htmlLink($predicate['predicate'], self::kind($predicate['is_constructor']));
    }

    private static function slug(string $name): string
    {
        return str_replace('.', '-', strtolower($name));
    }

    private static function sourceAnchor(array $source): string
    {
        $anchor = 'source-'.self::slug($source['predicate']['predicate']).'-'.self::slug($source['stored_constructor']);
        if (isset($source['needs_parent'])) {
            $anchor .= '-'.self::slug($source['needs_parent']['predicate']);
        }
        foreach ($source['needs_not_parents'] as $predicate) {
            $anchor .= '-without-'.self::slug($predicate['predicate']);
        }
        return $anchor;
    }

    /** The `(needs …)`/`(unless …)` conditions of a source. */
    private static function sourceConditions(array $source): array
    {
        $conditions = [];
        if (isset($source['needs_parent'])) {
            $conditions []= '(needs '.self::predicateLink($source['needs_parent']).')';
        }
        if ($source['needs_not_parents']) {
            $conditions []= '(unless '.implode(', ', array_map(self::predicateLink(...), $source['needs_not_parents'])).')';
        }
        return $conditions;
    }

    private function renderTraverser(array $traverser): string
    {
        $fields = [];
        if (isset($traverser['name'])) {
            $fields['name'] = self::htmlLink($traverser['name'], 'method');
        }
        if (isset($traverser['predicate'])) {
            $fields['predicate'] = self::htmlLink($traverser['predicate'], 'constructor');
        }
        if (isset($traverser['stored_constructor'])) {
            $fields['stored_constructor'] = $traverser['stored_constructor'];
        }
        if (!empty($traverser['params'])) {
            $fields['params'] = '['.implode(', ', array_column($traverser['params'], 'name')).']';
        }
        if (!empty($traverser['push_sources'])) {
            $fields['push_sources'] = '['.implode(', ', array_map(
                static fn (array $source): string => implode(' ', [
                    "<a href='".self::SOURCES_PAGE.'#'.self::sourceAnchor($source)."'>{$source['stored_constructor']}</a>",
                    ...self::sourceConditions($source),
                ]),
                $traverser['push_sources'],
            )).']';
        }
        if (!empty($traverser['is_needed_parent'])) {
            $fields['is_needed_parent'] = 'true';
        }
        if (isset($traverser['type'])) {
            $fields['type'] = self::htmlLink($traverser['type'], 'type');
        }
        return self::code($traverser['_'].self::renderArgs($fields, '{', '}'));
    }

    /** @return list<string> */
    private function renderSources(): array
    {
        $result = [];
        foreach ($this->incoming as $traverser) {
            $sources = $traverser['push_sources'] ?? [];
            foreach ($sources as $k => $source) {
                $line = \count($sources) > 1 ? '('.($k+1).') ' : '';
                $line .= '<a id="'.self::sourceAnchor($source).'"></a>';
                $line .= self::mdLink($source['predicate']['predicate'], $source['predicate']['is_constructor']);
                $line .= ' - ';

                $fields = [];
                foreach ($source['stored_params'] as $extractor) {
                    $path = $this->renderPath($extractor['from'], $source);
                    $fields[$extractor['to']] = $extractor['_'] === 'extractAndStore' ? $path : "{$extractor['_']}($path)";
                }
                foreach ($source['skipped_flags'] as $flag) {
                    $fields[$flag] = 'false';
                }
                $line .= self::code(implode(' ', [
                    ...self::sourceConditions($source),
                    $source['stored_constructor'].self::renderArgs($fields, '{', '}'),
                ]));
                $result []= $line;
            }
        }
        return $result;
    }

    private function renderPath(array $path, array $source): string
    {
        $root = match ($path['_']) {
            'path' => $source['predicate'],
            'pathParent' => $source['needs_parent'] ?? throw new AssertionError('A pathParent requires needs_parent'),
        };
        $result = [];
        foreach ($path['parts'] as $k => $part) {
            // Only the first part of a path may point to a method.
            $isConstructor = $k === 0 ? $root['is_constructor'] : true;
            if ($k === 0 && $part['constructor'] !== $root['predicate']) {
                throw new AssertionError("Path does not start from {$root['predicate']}");
            }
            // The predicate of the source itself is linked as in the source title.
            $name = $part['constructor'] === $source['predicate']['predicate']
                ? self::mdLink($part['constructor'], $isConstructor)
                : self::htmlLink($part['constructor'], self::kind($isConstructor));
            $param = $part['param'] === '' ? '(return value)' : $part['param'];
            $result []= "$name.$param".$this->renderFlag($part['flag']);
        }
        return implode('.', $result);
    }

    private function renderFlag(array $flag): string
    {
        return match ($flag['_']) {
            'paramNotFlag' => '',
            'paramIsFlagAbortIfEmpty' => '?abort_if_empty',
            'paramIsFlagPassthrough' => '?passthrough',
            'paramIsFlagFallback' => '?fallback('.$this->renderOp($flag['fallback']).')',
        };
    }

    /**
     * @param array<string, string> $args
     */
    private static function renderArgs(array $args, string $open, string $close): string
    {
        $result = [];
        foreach ($args as $key => $value) {
            $result []= "$key: $value";
        }
        return $open.implode(', ', $result).$close;
    }

    /** @param list<array> $args typedOpArg constructors */
    private function renderTypedOpArgs(array $args, string $open, string $close): string
    {
        $result = [];
        foreach ($args as $arg) {
            $result[$arg['key']] = $this->renderOp($arg['value']);
        }
        return self::renderArgs($result, $open, $close);
    }

    /** Render an ActionOp, a TypedOp or a TypedOpOp. */
    private function renderOp(array $op): string
    {
        return match ($op['_']) {
            'typedOp' => $this->renderOp($op['op']),
            'getMessageOp' => '[getMessageOp](/api/file-references#getmessageop)'.self::renderArgs([
                'peer' => $this->renderOp($op['peer']),
                'id' => $this->renderOp($op['id']),
                'quick_reply_shortcut_id' => $this->renderOp($op['quick_reply_shortcut_id']),
            ], '(', ')'),
            'callOp' => self::htmlLink($op['method'], 'method').$this->renderTypedOpArgs($op['args'], '(', ')'),
            'constructorOp' => self::htmlLink($op['constructor'], 'constructor').$this->renderTypedOpArgs($op['args'], '{', '}'),
            'vectorOp' => '['.implode(', ', array_map($this->renderOp(...), $op['values'])).']',
            'copyOp', 'getInputChannelByIdOp', 'getInputUserByIdOp', 'getInputPeerByIdOp' => "{$op['_']}({$op['from']})",
            'intLiteralOp', 'longLiteralOp' => (string) $op['value'],
            'doubleLiteralOp' => json_encode($op['value'], JSON_THROW_ON_ERROR|JSON_PRESERVE_ZERO_FRACTION),
            'stringLiteralOp' => self::stringLiteral($op['value']),
            'bytesLiteralOp' => 'base64_decode('.self::stringLiteral($op['value']['bytes']).')',
            'boolLiteralOp' => match ($op['value']) {
                true, ['_' => 'boolTrue'] => 'true',
                false, ['_' => 'boolFalse'] => 'false',
            },
            'themeFormatLiteralOp' => '$themeFormat',
        };
    }

    private static function stringLiteral(string $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    }
}
