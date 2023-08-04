<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\MTProto;

abstract class AbstractButtonQuery extends AbstractQuery
{
    /** @var string Data associated with the callback button. Be aware that a bad client can send arbitrary data in this field. */
    public readonly string $data;

    /** @internal */

    /**
     * @readonly
     * @var list<string> Regex matches, if a filter regex is present
     *
     */
    public ?array $matches = null;
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API, $rawCallback);
        $this->data = $rawCallback['data'];
    }
}
