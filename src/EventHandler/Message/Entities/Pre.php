<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Entities;

/**
 * Message entity representing a preformatted codeblock, allowing the user to specify a programming language for the codeblock.
 */
final class Pre extends MessageEntity
{
    public function __construct(
        /** Offset of message entity within message (in UTF-16 code units) */
        public readonly int $offset,

        /** Length of message entity within message (in UTF-16 code units) */
        public readonly int $length,

        /** Programming language of the code */
        public readonly string $language
    ) {
    }

    public function toBotAPI(): array
    {
        $res = ['type' => 'pre', 'offset' => $this->offset, 'length' => $this->length];
        if ($this->language !== '') {
            $res['language'] = $this->language;
        }
        return $res;
    }
    public function toMTProto(): array
    {
        return ['_' => 'messageEntityPre', 'offset' => $this->offset, 'length' => $this->length, 'language' => $this->language];
    }
}
