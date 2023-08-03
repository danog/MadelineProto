<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

class AbstractGameQuery extends AbstractQuery
{
    public readonly string $gameShortName;

    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API);
        $this->gameShortName = $rawCallback['game_short_name'] ?? '';
    }
}
