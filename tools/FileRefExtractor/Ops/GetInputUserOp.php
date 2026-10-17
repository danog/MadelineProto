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
use Webmozart\Assert\Assert;

final readonly class GetInputUserOp implements FieldTransformationOp
{
    public function __construct(
        private Path $path,
    ) {
    }

    public function normalize(array $stack, string $current, bool $ignoreFlag): ?\danog\MadelineProto\FileRefExtractor\TypedOp
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
    public function getType(TLContext $tl): string
    {
        return 'InputUser';
    }

    public function build(TLContext $tl): array
    {
        $type = $this->path->getType($tl);
        if ($type === 'InputUser') {
            return [
                '_' => 'typedOp',
                'type' => $this->getType($tl),
                'op' => [
                    '_' => 'getInputUserByIdOp',
                    'from' => $this->path->buildPath($tl, 'extractUserIdFromInputUserAndStore'),
                ],
            ];
        }
        if ($type === 'long') {
            return [
                '_' => 'typedOp',
                'type' => $this->getType($tl),
                'op' => [
                    '_' => 'getInputUserByIdOp',
                    'from' => $this->path->buildPath($tl, 'extractAndStore'),
                ],
            ];
        }
        Assert::eq($type, 'User', "Expected type 'User' at position {$this->path->path[0][0]} but got '$type'");
        return [
            '_' => 'typedOp',
            'type' => $this->getType($tl),
            'op' => [
                '_' => 'getInputUserByIdOp',
                'from' => $this->path->buildPath($tl, 'extractUserIdFromUserAndStore'),
            ],
        ];
    }
}
