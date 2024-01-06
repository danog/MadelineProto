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
        return array_map(static fn (array $entity): MessageEntity => match ($entity['_']) {
            'messageEntityMention' => new Mention($entity),
            'messageEntityHashtag' => new Hashtag($entity),
            'messageEntityBotCommand' => new BotCommand($entity),
            'messageEntityEmail' => new Email($entity),
            'messageEntityBold' => new Bold($entity),
            'messageEntityItalic' => new Italic($entity),
            'messageEntityUrl' => new Url($entity),
            'messageEntityCode' => new Code($entity),
            'messageEntityPre' => new Pre($entity),
            'messageEntityTextUrl' => new TextUrl($entity),
            'messageEntityMentionName' => new MentionName($entity),
            'inputMessageEntityMentionName' => new InputMentionName($entity),
            'messageEntityPhone' => new Phone($entity),
            'messageEntityCashtag' => new Cashtag($entity),
            'messageEntityUnderline' => new Underline($entity),
            'messageEntityStrike' => new Strike($entity),
            'messageEntityBlockquote' => new Blockquote($entity),
            'messageEntityBankCard' => new BankCard($entity),
            'messageEntitySpoiler' => new Spoiler($entity),
            'messageEntityCustomEmoji' => new CustomEmoji($entity),
            default => new Unknown($entity)
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
