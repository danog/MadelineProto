<?php

declare(strict_types=1);

namespace danog\MadelineProto\TL\Conversion;

use AssertionError;
use danog\MadelineProto\Exception;
use danog\MadelineProto\StrTools;
use Throwable;

/**
 * Class that converts Markdown to a message + set of entities.
 */
final class MarkdownEntities extends Entities
{
    /** Converted entities */
    public readonly array $entities;
    /** Converted message */
    public readonly string $message;

    /**
     * @param string $markdown Markdown to parse
     */
    public function __construct(string $markdown)
    {
        $markdown = \str_replace("\r\n", "\n", $markdown);
        try {
            $message = '';
            $messageLen = 0;
            $entities = [];
            $offset = 0;
            $stack = [];
            while ($offset < \strlen($markdown)) {
                $len = \strcspn($markdown, '*_~`[]|!\\', $offset);
                $piece = \substr($markdown, $offset, $len);
                $offset += $len;
                if ($offset === \strlen($markdown)) {
                    $message .= $piece;
                    break;
                }

                $char = $markdown[$offset++];
                $next = $markdown[$offset] ?? '';
                if ($char === '\\') {
                    $message .= $piece.$next;
                    $messageLen += StrTools::mbStrlen($piece)+1;
                    $offset++;
                    continue;
                }

                if ($char === '_' && $next === '_') {
                    $offset++;
                    $char = '__';
                } elseif ($char === '|') {
                    if ($next === '|') {
                        $offset++;
                        $char = '||';
                    } else {
                        $message .= $piece.$char;
                        $messageLen += StrTools::mbStrlen($piece)+1;
                        continue;
                    }
                } elseif ($char === '!') {
                    if ($next === '[') {
                        $offset++;
                        $char = '](';
                    } else {
                        $message .= $piece.$char;
                        $messageLen += StrTools::mbStrlen($piece)+1;
                        continue;
                    }
                } elseif ($char === '[') {
                    $char = '](';
                } elseif ($char === ']') {
                    if (!$stack || \end($stack)[0] !== '](') {
                        $message .= $piece.$char;
                        $messageLen += StrTools::mbStrlen($piece)+1;
                        continue;
                    }
                    if ($next !== '(') {
                        [, $start] = \array_pop($stack);
                        $message .= '['.$piece.$char;
                        $messageLen += StrTools::mbStrlen($piece)+2;
                        continue;
                    }
                    $offset++;
                    $char = "](";
                } elseif ($char === '`' && $next === '`' && ($markdown[$offset+1] ?? '') === '`') {
                    $message .= $piece;
                    $messageLen += StrTools::mbStrlen($piece);

                    $offset += 2;
                    $langLen = \strcspn($markdown, "\n ", $offset);
                    $language = \substr($markdown, $offset, $langLen);
                    $offset += $langLen;
                    if ($markdown[$offset] === "\n") {
                        $offset++;
                    }

                    $piece = '';
                    $posClose = $offset;
                    while (($posClose = \strpos($markdown, '```', $posClose)) !== false) {
                        if ($markdown[$posClose-1] === '\\') {
                            $piece .= \substr($markdown, $offset, ($posClose-$offset)-1)."```";
                            $posClose += 3;
                            $offset = $posClose;
                            continue;
                        }
                        break;
                    }
                    if ($posClose === false) {
                        throw new AssertionError("Unclosed ``` opened @ pos $offset!");
                    }
                    $piece .= \substr($markdown, $offset, $posClose-$offset);

                    $start = $messageLen;

                    $message .= $piece;
                    $pieceLen = StrTools::mbStrlen($piece);
                    $messageLen += $pieceLen;

                    for ($x = \strlen($piece)-1; $x >= 0; $x--) {
                        if (!(
                            $piece[$x] === ' '
                            || $piece[$x] === "\r"
                            || $piece[$x] === "\n"
                        )) {
                            break;
                        }
                        $pieceLen--;
                    }
                    if ($pieceLen > 0) {
                        $entities []= [
                            '_' => 'messageEntityPre',
                            'language' => $language,
                            'offset' => $start,
                            'length' => $pieceLen
                        ];
                    }

                    $offset = $posClose+3;
                    continue;
                }

                if ($stack && \end($stack)[0] === $char) {
                    [, $start] = \array_pop($stack);
                    if ($char === '](') {
                        $posClose = $offset;
                        while (($posClose = \strpos($markdown, ')', $posClose)) !== false) {
                            if ($markdown[$posClose-1] === '\\') {
                                $posClose++;
                                continue;
                            }
                            break;
                        }
                        if ($posClose === false) {
                            throw new AssertionError("Unclosed ) opened @ pos $offset!");
                        }
                        $entity = self::handleLink(\substr($markdown, $offset, $posClose-$offset));
                        $offset = $posClose+1;
                    } else {
                        $entity = match ($char) {
                            '*' => ['_' => 'messageEntityBold'],
                            '_' => ['_' => 'messageEntityItalic'],
                            '__' =>  ['_' => 'messageEntityUnderline'],
                            '`' => ['_' => 'messageEntityCode'],
                            '~' => ['_' => 'messageEntityStrike'],
                            '||' => ['_' => 'messageEntitySpoiler'],
                            default => throw new AssertionError("Unknown char $char @ pos $offset!")
                        };
                    }
                    $message .= $piece;
                    $messageLen += StrTools::mbStrlen($piece);

                    $lengthReal = $messageLen-$start;
                    for ($x = \strlen($message)-1; $x >= 0; $x--) {
                        if (!(
                            $message[$x] === ' '
                            || $message[$x] === "\r"
                            || $message[$x] === "\n"
                        )) {
                            break;
                        }
                        $lengthReal--;
                    }
                    if ($lengthReal > 0) {
                        $entities []= $entity + ['offset' => $start, 'length' => $lengthReal];
                    }
                } else {
                    $message .= $piece;
                    $messageLen += StrTools::mbStrlen($piece);
                    $stack []= [$char, $messageLen];
                }
            }
            if ($stack) {
                throw new AssertionError("Found unclosed markdown elements ".\implode(', ', \array_column($stack, 0)));
            }

            $this->message = $message;
            $this->entities = $entities;
        } catch (Throwable $e) {
            throw new Exception("An error occurred while parsing $markdown: {$e->getMessage()}", $e->getCode());
        }
    }
}
