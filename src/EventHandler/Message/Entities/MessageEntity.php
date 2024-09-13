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
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        public readonly int $offset,

        /** Length of message entity within message (in UTF-16 code units) */
        public readonly int $length
    ) {
    }

    /**
     * @param  list<array> $entities
     * @return list<self>
     */
    public static function fromRawEntities(array $entities): array
    {
        return array_map(self::fromRawEntity(...), $entities);
    }

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
            'phone_number', 'messageEntityPhone' => new Phone($entity['offset'], $entity['length']),
            'cashtag', 'messageEntityCashtag' => new Cashtag($entity['offset'], $entity['length']),
            'underline', 'messageEntityUnderline' => new Underline($entity['offset'], $entity['length']),
            'strikethrough', 'messageEntityStrike' => new Strike($entity['offset'], $entity['length']),
            'block_quote', 'messageEntityBlockquote' => new Blockquote($entity['offset'], $entity['length']),
            'bank_card', 'messageEntityBankCard' => new BankCard($entity['offset'], $entity['length']),
            'spoiler', 'messageEntitySpoiler' => new Spoiler($entity['offset'], $entity['length']),
            'custom_emoji', 'messageEntityCustomEmoji' => new CustomEmoji($entity['offset'], $entity['length'], $entity['document_id'] ?? $entity['custom_emoji_id']),
            default => throw new AssertionError("Unknown entity type: ".($entity['_'] ?? $entity['type']))
        };
    }

    /**
     * Convert entity to bot API entity.
     */
    abstract public function toBotAPI(): array;
    /**
     * Convert entity to MTProto entity.
     */
    abstract public function toMTProto(): array;

    /** @internal */
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
