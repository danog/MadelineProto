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
            'mention', 'messageEntityMention' => new Mention($entity),
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


    /**
     * Convert entity to bot API entity.
     */
    public function toBotAPI(): array
    {
        $data = ['offset' => $this->offset, 'length' => $this->length];
        switch (true) {
            case $this instanceof CustomEmoji:
                $data['type'] = 'custom_emoji';
                $data['custom_emoji_id'] = $this->documentId;
                return $data;
            case $this instanceof Phone:
                $data['type'] = 'phone_number';
                return $data;
            case $this instanceof Blockquote:
                $data['type'] = 'block_quote';
                return $data;
            case $this instanceof Mention:
                $data['type'] = 'mention';
                return $data;
            case $this instanceof Hashtag:
                $data['type'] = 'hashtag';
                return $data;
            case $this instanceof BotCommand:
                $data['type'] = 'bot_command';
                return $data;
            case $this instanceof Url:
                $data['type'] = 'url';
                return $data;
            case $this instanceof TextUrl:
                $data['type'] = 'text_url';
                $data['url'] = $this->url;
                return $data;
            case $this instanceof Email:
                $data['type'] = 'email';
                return $data;
            case $this instanceof Bold:
                $data['type'] = 'bold';
                return $data;
            case $this instanceof Strike:
                $data['type'] = 'strikethrough';
                return $data;
            case $this instanceof Spoiler:
                $data['type'] = 'spoiler';
                return $data;
            case $this instanceof Underline:
                $data['type'] = 'underline';
                return $data;
            case $this instanceof Italic:
                $data['type'] = 'italic';
                return $data;
            case $this instanceof Code:
                $data['type'] = 'code';
                return $data;
            case $this instanceof Pre:
                $data['type'] = 'pre';
                $data['language'] = $this->language;
                return $data;
            case $this instanceof TextUrl:
                $data['type'] = 'text_url';
                return $data;
            case $this instanceof Mention:
                $data['type'] = 'mention';
                return $data;
            case $this instanceof MentionName:
                $data['type'] = 'text_mention';
                $data['user'] = ['id' => $this->userId];
                return $data;
            default:
                throw new AssertionError("Unreachable");
        }
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
