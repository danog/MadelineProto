<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Master class for message entities.
 */
abstract class MessageEntity implements JsonSerializable
{
    /** Offset of message entity within message (in UTF-16 code units) */
    public readonly int $offset;

    /** Length of message entity within message (in UTF-16 code units) */
    public readonly int $length;

    /** @internal  */
    protected function __construct(array $rawEntities)
    {
        $this->offset = $rawEntities['offset'];
        $this->length = $rawEntities['length'];
    }

    /**
     * @param  list<array> $entities
     * @return list<self>
     */
    public static function fromRawEntities(array $entities): array
    {
        return array_map(static fn (array $entity): MessageEntity => match ($entity['_'] ?? $entity['type']) {
            'text_mention', 'messageEntityMention' => new Mention($entity),
            'hashtag', 'messageEntityHashtag' => new Hashtag($entity),
            'bot_command', 'messageEntityBotCommand' => new BotCommand($entity),
            'email', 'messageEntityEmail' => new Email($entity),
            'bold', 'messageEntityBold' => new Bold($entity),
            'italic', 'messageEntityItalic' => new Italic($entity),
            'url', 'messageEntityUrl' => new Url($entity),
            'code', 'messageEntityCode' => new Code($entity),
            'pre', 'messageEntityPre' => new Pre($entity),
            'text_link', 'messageEntityTextUrl' => new TextUrl($entity),
            'text_mention', 'messageEntityMentionName' => new MentionName($entity),
            'inputMessageEntityMentionName' => new InputMentionName($entity),
            'phone_number', 'messageEntityPhone' => new Phone($entity),
            'cashtag', 'messageEntityCashtag' => new Cashtag($entity),
            'underline', 'messageEntityUnderline' => new Underline($entity),
            'strikethrough', 'messageEntityStrike' => new Strike($entity),
            'blockquote', 'messageEntityBlockquote' => new Blockquote($entity),
            'messageEntityBankCard' => new BankCard($entity),
            'spoiler', 'messageEntitySpoiler' => new Spoiler($entity),
            'custom_emoji', 'messageEntityCustomEmoji' => new CustomEmoji($entity),
        }, $entities);
    }

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
