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
 * @copyright 2016-2025 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\Parallel\Internal;

use Amp\Parallel\Context\ContextException;
use Amp\Parallel\Context\ContextPanicError;
use Throwable;

use function Amp\Parallel\Context\flattenThrowableBacktrace;

/**
 * What a {@see \danog\MadelineProto\Parallel\WebContext} returned, or the exception it threw, sent to the parent
 * once its script ends.
 *
 * Joining it behaves like joining a process context of amphp/parallel: an exception is rethrown as a
 * ContextException, whose previous exception is a ContextPanicError with the class, message, code and trace of the
 * original one.
 *
 * @internal
 *
 * @psalm-type Failure = array{class: class-string<Throwable>, message: string, code: int|string, file: string, line: int, trace: list<array<non-empty-string, scalar|list<scalar>>>, previous: mixed}
 */
final class ContextExit
{
    /**
     * @param Failure|null $failure
     */
    private function __construct(
        private readonly mixed $value,
        private readonly ?array $failure,
    ) {
    }

    public static function success(mixed $value): self
    {
        return new self($value, null);
    }

    public static function failure(Throwable $exception): self
    {
        return new self(null, self::flatten($exception));
    }

    /**
     * @return Failure
     */
    private static function flatten(Throwable $exception): array
    {
        $previous = $exception->getPrevious();
        return [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => flattenThrowableBacktrace($exception),
            'previous' => $previous ? self::flatten($previous) : null,
        ];
    }

    /**
     * @param Failure $failure
     */
    private static function createPanicError(array $failure): ContextPanicError
    {
        return new ContextPanicError(
            $failure['class'],
            $failure['message'],
            $failure['code'],
            $failure['file'],
            $failure['line'],
            $failure['trace'],
            \is_array($failure['previous']) ? self::createPanicError($failure['previous']) : null,
        );
    }

    /**
     * The value returned by the context.
     *
     * @throws ContextException If the context threw.
     */
    public function getResult(): mixed
    {
        if ($this->failure === null) {
            return $this->value;
        }
        $exception = self::createPanicError($this->failure);
        throw new ContextException(
            'Process exited with an uncaught exception: '.$exception->getMessage(),
            previous: $exception,
        );
    }
}
