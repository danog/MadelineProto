<?php

declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\FileRefExtractor\Ops;

use danog\MadelineProto\FileRefExtractor\TLContext;
use danog\MadelineProto\FileRefExtractor\TypedOp;
use Webmozart\Assert\Assert;

final readonly class ArrayOp implements TypedOp
{
    /** @var TypedOp[] */
    private readonly array $values;
    public function __construct(TypedOp ...$values)
    {
        $this->values = $values;
    }
    public function normalize(array $stack, string $current, bool $ignoreFlag): ?\danog\MadelineProto\FileRefExtractor\TypedOp
    {
        $final = [];
        $isDifferent = false;
        foreach ($this->values as $value) {
            $normalized = $value->normalize($stack, $current, $ignoreFlag);
            if ($normalized === null) {
                return null;
            }
            if ($normalized !== $value) {
                $isDifferent = true;
            }
            $final[] = $normalized;
        }
        if ($isDifferent) {
            return new self(...$final);
        }
        return $this;
    }

    public function getType(TLContext $tl): string
    {
        return 'Vector<' . $this->values[0]->getType($tl) . '>';
    }

    public function build(TLContext $tl): array
    {
        $t = $this->values[0]->getType($tl);
        $arr = [];
        foreach ($this->values as $key => $value) {
            $value = $value->build($tl);
            Assert::eq($value['type'], $t, "Expected type '$t' at position $key but got '{$value['type']}'");
            $arr[$key] = $value['op'];
        }
        return [
            '_' => 'typedOp',
            'type' => $this->getType($tl),
            'op' => [
                '_' => 'vectorOp',
                'values' => $arr,
            ],
        ];
    }
}
