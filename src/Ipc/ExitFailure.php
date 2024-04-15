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

namespace danog\MadelineProto\Ipc;

use danog\MadelineProto\Exception as MadelineProtoException;
use ReflectionClass;
use Throwable;

use function Amp\Parallel\Context\flattenThrowableBacktrace as ContextFlattenThrowableBacktrace;

/**
 * @internal
 */
final class ExitFailure
{
    /** @var class-string<\Throwable> */
    private string $type;

    private array $props;

    private ?self $previous = null;

    public function __construct(Throwable $exception)
    {
        $this->type = $exception::class;
        $props = [];
        $f = new ReflectionClass($exception);
        do {
            foreach ($f->getProperties() as $prop) {
                if ($prop->isStatic()) {
                    continue;
                }
                $value = $prop->getValue($exception);
                if ($prop->getName() === 'trace' && \is_array($value)) {
                    $value = ContextFlattenThrowableBacktrace($exception);
                } elseif ($prop->getName() === 'previous' && $value instanceof \Throwable) {
                    $value = new self($value);
                }
                $props[$f->getName()][$prop->getName()] = $value;
            }
        } while ($f = $f->getParentClass());

        $this->props = $props;
    }

    public function getException(): \Throwable
    {
        $prev = new MadelineProtoException("Client backtrace");

        $refl = new \ReflectionClass($this->type);
        $exception = $refl->newInstanceWithoutConstructor();

        $props = $this->props;
        $props[\Exception::class]['previous'] = $this->previous?->getException();

        foreach ($props as $class => $subprops) {
            $class = new ReflectionClass($class);
            foreach ($class->getProperties() as $prop) {
                $key = $prop->getName();
                if (!\array_key_exists($key, $subprops)) {
                    continue;
                }
                $value = $subprops[$key];
                if ($key === 'previous') {
                    if ($value instanceof self) {
                        $value = $value->getException();
                    } elseif ($value === null) {
                        $value = $prev;
                    }
                } elseif ($key === 'tlTrace') {
                    $value = "$value\n\nClient TL trace:".$prev->getTLTrace();
                }
                try {
                    $prop->setValue($exception, $value);
                } catch (\Throwable) {
                }
            }
        }

        return $exception;
    }
}
