<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Message\Rich\Block;

use danog\MadelineProto\EventHandler\Message\Rich\Block;

/**
 * A block that can't be represented (e.g. an instant view block, or a media block whose media is unavailable): it is dropped when the rich message is sent again.
 */
final class Unsupported extends Block
{
}
