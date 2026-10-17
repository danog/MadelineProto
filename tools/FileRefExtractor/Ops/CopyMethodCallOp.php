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

use danog\MadelineProto\FileRefExtractor\ActionOp;
use danog\MadelineProto\FileRefExtractor\Path;
use danog\MadelineProto\FileRefExtractor\TLContext;
use Webmozart\Assert\Assert;

final readonly class CopyMethodCallOp implements ActionOp
{
    public function __construct(private readonly string $method, private readonly string $stored_constructor)
    {
    }

    public function normalize(array $stack, string $current, bool $ignoreFlag): ?\danog\MadelineProto\FileRefExtractor\ActionOp
    {
        Assert::eq($current, $this->method);
        Assert::isEmpty($stack);
        return $this;
    }

    public function build(TLContext $tl): void
    {
        Assert::eq($tl->position, $this->method, "Current constructor {$tl->position} does not match expected method {$this->method}");
        $method = $tl->tl->tl->getMethods()->findByMethod($this->method);
        $args = [];
        foreach ($method['params'] as $arg) {
            if (isset($arg['pow'])) {
                $args[$arg['name']] = new CopyOp([[$this->method, $arg['name'], Path::FLAG_PASSTHROUGH]]);
            } else {
                if ($arg['type'] === 'InputPeer') {
                    $args[$arg['name']] = new GetInputPeerOp(new Path([[$this->method, $arg['name']]]));
                } elseif ($arg['type'] === 'InputUser') {
                    $args[$arg['name']] = new GetInputUserOp(new Path([[$this->method, $arg['name']]]));
                } else {
                    $args[$arg['name']] = new CopyOp([[$this->method, $arg['name']]]);
                }
            }
        }
        $result = new CallOp(
            $this->method,
            $args,
            $this->stored_constructor
        );
        $result = $result->normalize([], $this->method, false);

        $result->build($tl);
    }
}
