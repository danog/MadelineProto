<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2023 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Query;

use danog\MadelineProto\EventHandler\CallbackQuery;
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
    /**
     * @readonly
     *
     * @var array<array-key, array<array-key, list{string, int}|null|string>|mixed> Regex matches, if a filter multiple match regex is present
     */
    public ?array $matchesAll = null;

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
