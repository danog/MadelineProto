<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Query;

/** Represents a query sent by the user by clicking on a "Play game" button in a chat. */
final class ChatGameQuery extends GameQuery
{
    use ChatTrait;
}
