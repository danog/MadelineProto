<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich;

use JsonSerializable;

/**
 * A button of a [rich message](https://core.telegram.org/bots/api#richmessagebutton), in a buttons block or inline in a text.
 */
abstract class Button implements JsonSerializable
{
    use JsonSerializeTrait;

    /** Text of the button */
    public readonly RichText $text;

    public function __construct(
        RichText|string $text,
        /** Style of the button, null for the default one */
        public readonly ?ButtonStyle $style = null,
    ) {
        $this->text = RichText::from($text);
    }
}
