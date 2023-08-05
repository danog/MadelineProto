<?php

declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Query;

use danog\MadelineProto\EventHandler\CallbackQuery;
use danog\MadelineProto\EventHandler\Query;
use danog\MadelineProto\MTProto;

/** Represents a query sent by the user by clicking on a "Play game" button. */
abstract class GameQuery extends CallbackQuery
{
    /** @var string Short name of a Game to be returned, serves as the unique identifier for the game */
    public readonly string $gameShortName;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API, $rawCallback);
        $this->gameShortName = $rawCallback['game_short_name'];
    }
}
