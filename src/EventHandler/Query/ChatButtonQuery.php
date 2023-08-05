<?php

declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Query;

/** Represents a query sent by the user by clicking on a button in a chat. */
final class ChatButtonQuery extends ButtonQuery
{
    use ChatTrait;
}
