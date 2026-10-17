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

final readonly class ConstructorOp implements TypedOp
{
    /** @param TypedOp[] $args */
    public function __construct(
        private readonly string $constructor,
        private readonly array $args
    ) {
        Assert::allIsInstanceOf($args, TypedOp::class);
    }

    public function normalize(array $stack, string $current, bool $ignoreFlag): ?\danog\MadelineProto\FileRefExtractor\TypedOp
    {
        $final = [];
        $isDifferent = false;
        foreach ($this->args as $from => $to) {
            $normalized = $to->normalize($stack, $current, $ignoreFlag);
            if ($normalized === null) {
                return null;
            }
            if ($normalized !== $to) {
                $isDifferent = true;
            }
            $final[$from] = $normalized;
        }
        if ($isDifferent) {
            return new self($this->constructor, $final);
        }
        return $this;
    }
    public function getType(TLContext $tl): string
    {
        return $tl->tl->tl->getConstructors()->findByPredicate($this->constructor)['type'];
    }

    public function build(TLContext $tl): array
    {
        $final = [];
        $tl->validateParams($this->constructor, true, $this->args);
        /*$orig = $tl->buildMode->curKey;
        Assert::notNull($orig);*/
        foreach ($this->args as $from => $to) {
            //$final[] = ['_' => 'typedOpArg', 'key' => $from, 'value' => $tl->build($to, "{$orig}_$from")];
            $final[] = ['_' => 'typedOpArg', 'key' => $from, 'value' => $tl->build($to, $from)];
        }
        return [
            '_' => 'typedOp',
            'type' => $this->getType($tl),
            'op' => [
                '_' => 'constructorOp',
                'constructor' => $this->constructor,
                'args' => $final,
            ],
        ];
    }
}
