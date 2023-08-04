<?php

declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

abstract class AbstractGameQuery extends AbstractQuery
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
