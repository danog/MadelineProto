<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

use AssertionError;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Master class for message entities.
 */
abstract class MessageEntity implements JsonSerializable
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        public readonly int $offset,

        /** Length of message entity within message (in UTF-16 code units) */
        public readonly int $length
    ) {
    }

    /**
     * @param list<array> $entities
     *
     * @return list<self>
     *
     * @psalm-pure
     */
    public static function fromRawEntities(array $entities): array
    {
        return array_map(self::fromRawEntity(...), $entities);
    }

    /**
     * @psalm-pure
     */
    public static function fromRawEntity(array|self $entity): self
    {
        if ($entity instanceof self) {
            return $entity;
        }
        return match ($entity['_'] ?? $entity['type']) {
            'mention', 'messageEntityMention' => new Mention($entity['offset'], $entity['length']),
            'hashtag', 'messageEntityHashtag' => new Hashtag($entity['offset'], $entity['length']),
            'bot_command', 'messageEntityBotCommand' => new BotCommand($entity['offset'], $entity['length']),
            'email', 'messageEntityEmail' => new Email($entity['offset'], $entity['length']),
            'bold', 'messageEntityBold' => new Bold($entity['offset'], $entity['length']),
            'italic', 'messageEntityItalic' => new Italic($entity['offset'], $entity['length']),
            'url', 'messageEntityUrl' => new Url($entity['offset'], $entity['length']),
            'code', 'messageEntityCode' => new Code($entity['offset'], $entity['length']),
            /** @psalm-suppress MixedArgument */
            'pre', 'messageEntityPre' => new Pre($entity['offset'], $entity['length'], $entity['language'] ?? ''),
            'text_link', 'messageEntityTextUrl' => new TextUrl($entity['offset'], $entity['length'], $entity['url']),
            'text_mention', 'messageEntityMentionName' => new MentionName($entity['offset'], $entity['length'], $entity['user_id'] ?? $entity['user']['id']),
            'inputMessageEntityMentionName' => new InputMentionName($entity['offset'], $entity['length'], $entity['user_id']),
            'phone_number', 'phone', 'messageEntityPhone' => new Phone($entity['offset'], $entity['length']),
            'cashtag', 'messageEntityCashtag' => new Cashtag($entity['offset'], $entity['length']),
            'underline', 'messageEntityUnderline' => new Underline($entity['offset'], $entity['length']),
            'strikethrough', 'messageEntityStrike' => new Strike($entity['offset'], $entity['length']),
            // block_quote and bank_card are the names used before Bot API 10
            'blockquote', 'block_quote' => new Blockquote($entity['offset'], $entity['length']),
            'expandable_blockquote' => new Blockquote($entity['offset'], $entity['length'], true),
            'messageEntityBlockquote' => new Blockquote($entity['offset'], $entity['length'], $entity['collapsed'] ?? false),
            'bank_card_number', 'bank_card', 'messageEntityBankCard' => new BankCard($entity['offset'], $entity['length']),
            'spoiler', 'messageEntitySpoiler' => new Spoiler($entity['offset'], $entity['length']),
            'custom_emoji', 'messageEntityCustomEmoji' => new CustomEmoji($entity['offset'], $entity['length'], $entity['document_id'] ?? $entity['custom_emoji_id']),
            /** @psalm-suppress MixedArgument */
            'date_time' => FormattedDate::fromBotAPIFormat($entity['offset'], $entity['length'], $entity['unix_time'], $entity['date_time_format'] ?? ''),
            'messageEntityFormattedDate' => new FormattedDate(
                $entity['offset'],
                $entity['length'],
                $entity['date'],
                $entity['relative'] ?? false,
                $entity['short_time'] ?? false,
                $entity['long_time'] ?? false,
                $entity['short_date'] ?? false,
                $entity['long_date'] ?? false,
                $entity['day_of_week'] ?? false,
            ),
            'diff_insert', 'messageEntityDiffInsert' => new DiffInsert($entity['offset'], $entity['length']),
            'diff_delete', 'messageEntityDiffDelete' => new DiffDelete($entity['offset'], $entity['length']),
            'diff_replace', 'messageEntityDiffReplace' => new DiffReplace($entity['offset'], $entity['length'], $entity['old_text']),
            'ton_address', 'messageEntityTonAddress' => new TonAddress($entity['offset'], $entity['length']),
            'unknown', 'messageEntityUnknown' => new Unknown($entity['offset'], $entity['length']),
            // Entity types added in newer layers must not prevent wrapping the message they're in.
            default => isset($entity['_']) && str_starts_with($entity['_'], 'messageEntity')
                ? new Unknown($entity['offset'], $entity['length'], $entity)
                : throw new AssertionError("Unknown entity type: ".($entity['_'] ?? $entity['type']))
        };
    }

    /**
     * Convert entity to bot API entity.
     * @psalm-mutation-free
     */
    abstract public function toBotAPI(): array;
    /**
     * Convert entity to MTProto entity.
     * @psalm-mutation-free
     */
    abstract public function toMTProto(): array;

    /** @internal */
    #[\Override]
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}
