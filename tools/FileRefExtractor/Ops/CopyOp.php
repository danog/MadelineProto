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

use danog\MadelineProto\FileRefExtractor\FieldTransformationOp;
use danog\MadelineProto\FileRefExtractor\Path;
use danog\MadelineProto\FileRefExtractor\TLContext;
use danog\MadelineProto\FileRefExtractor\TypedOp;

final readonly class CopyOp implements FieldTransformationOp
{
    private Path $path;
    /** @param Path|list<list{0: string, 1: string, 2?: int-mask-of<self::FLAG_*>|TypedOp}> $path */
    public function __construct(
        array|Path $path,
    ) {
        $this->path = $path instanceof Path ? $path : new Path($path);
    }

    public function getType(TLContext $tl): string
    {
        return $this->path->getType($tl);
    }

    public function normalize(array $stack, string $current, bool $ignoreFlag): ?TypedOp
    {
        $path = $this->path->normalize($stack, $current, $ignoreFlag);
        if ($path === null) {
            return null;
        }
        if ($path !== $this->path) {
            return new self($path);
        }
        return $this;
    }
    public function build(TLContext $tl): array
    {
        return [
            '_' => 'typedOp',
            'type' => $this->path->getType($tl),
            'op' => [
                '_' => 'copyOp',
                'from' => $this->path->buildPath($tl, 'extractAndStore'),
            ],
        ];
    }
}
