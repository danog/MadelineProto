<?php

declare(strict_types=1);

namespace danog\MadelineProto\TL\Conversion;

/**
 * Class that converts HTML or markdown to a message + set of entities.
 *
 * @internal
 */
abstract class Entities
{
    protected static function handleLink(string $href): array
    {
        if (\preg_match('|^mention:(.+)|', $href, $matches) || \preg_match('|^tg://user\\?id=(.+)|', $href, $matches)) {
            return ['_' => 'inputMessageEntityMentionName', 'user_id' => $matches[1]];
        }
        if (\preg_match('|^emoji:(\d+)$|', $href, $matches) || \preg_match('|^tg://emoji\\?id=(.+)|', $href, $matches)) {
            return ['_' => 'messageEntityCustomEmoji', 'document_id' => (int) $matches[1]];
        }
        return ['_' => 'messageEntityTextUrl', 'url' => $href];
    }
}
