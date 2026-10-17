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

namespace danog\MadelineProto\FileRefExtractor;

use danog\MadelineProto\TL\TL;
use Webmozart\Assert\Assert;

final class TLWrapper
{
    private readonly array $constructorsOfType;
    private readonly array $methodsOfType;

    public function __construct(
        public readonly TL $tl,
    ) {
        $constructorsOfType = [];
        $methodsOfType = [];
        foreach ($tl->getConstructors()->by_id as $constructor) {
            $t = $constructor['type'];
            $constructorsOfType[$t][$constructor['predicate']] = $constructor;
        }
        foreach ($tl->getMethods()->by_id as $method) {
            $t = isset($method['subtype']) ? "Vector<{$method['subtype']}>" : $method['type'];
            $methodsOfType[$t][$method['method']] = $method;
        }
        $this->constructorsOfType = $constructorsOfType;
        $this->methodsOfType = $methodsOfType;
    }

    public function getParamType(string $constructorOrMethod, string $param): string
    {
        foreach ($this->getConstructorOrMethod($constructorOrMethod)['params'] as $p) {
            if ($p['name'] === $param) {
                return $p['subtype'] ?? $p['type'];
            }
        }
        throw new \InvalidArgumentException("No parameter of name $param found for constructor or method: $constructorOrMethod");
    }
    public function getConstructorOrMethod(string $name): array
    {
        $constructor = $this->tl->getConstructors()->findByPredicate($name);
        if ($constructor) {
            return $constructor;
        }
        $method = $this->tl->getMethods()->findByMethod($name);
        if ($method) {
            return $method;
        }
        throw new \InvalidArgumentException("No constructor or method found with name: $name");
    }
    public function isConstructor(string $constructor): bool
    {
        return (bool) $this->tl->getConstructors()->findByPredicate($constructor);
    }
    public function getConstructorsOfType(string $type, bool $ignoreEmpty = false): array
    {
        $t = $this->constructorsOfType[$type] ?? [];
        if (!$ignoreEmpty) {
            Assert::notEmpty($t, "No constructors found for type: $type");
        }
        return $t;
    }
    public function getMethodsOfType(string $type, bool $ignoreEmpty = false): array
    {
        $t = $this->methodsOfType[$type] ?? [];
        if (!$ignoreEmpty) {
            Assert::notEmpty($t, "No methods found for type: $type");
        }
        return $t;
    }
}
