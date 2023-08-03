<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

final class InlineGameQuery extends AbstractGameQuery
{
  
    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API, $rawCallback);
    }
}
