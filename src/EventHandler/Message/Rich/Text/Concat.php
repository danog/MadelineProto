<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Text;

use danog\MadelineProto\EventHandler\Message\Rich\RichText;

/**
 * A concatenation of rich texts.
 */
final class Concat extends RichText
{
    /**
     * The concatenated texts.
     *
     * @var list<RichText>
     */
    public readonly array $texts;

    /**
     * @param list<RichText|string> $texts The concatenated texts
     */
    public function __construct(array $texts)
    {
        $this->texts = array_map(RichText::from(...), $texts);
    }
}
