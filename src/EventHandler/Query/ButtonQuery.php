<?php declare(strict_types=1);

namespace danog\MadelineProto\EventHandler\Query;

use danog\MadelineProto\EventHandler\CallbackQuery;
use danog\MadelineProto\EventHandler\Query;
use danog\MadelineProto\MTProto;
use danog\MadelineProto\TL\Types\Bytes;
use ReflectionClass;
use ReflectionProperty;

/** Represents a query sent by the user by clicking on a button. */
abstract class ButtonQuery extends CallbackQuery
{
    /** Data associated with the callback button. Be aware that a bad client can send arbitrary data in this field. */
    public readonly string $data;

    /**
     * @readonly
     * @var list<string> Regex matches, if a filter regex is present.
     */
    public ?array $matches = null;

    /** @internal */
    public function __construct(MTProto $API, array $rawCallback)
    {
        parent::__construct($API, $rawCallback);
        $this->data = (string) $rawCallback['data'];
    }

    /** @internal */
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        $res['data'] = new Bytes($res['data']);
        return $res;
    }
}
