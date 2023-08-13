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

use danog\MadelineProto\RPCErrorException;
use danog\MadelineProto\TL\Exception;
use RuntimeException;
use Throwable;

use function Amp\Parallel\Context\flattenThrowableBacktrace as ContextFlattenThrowableBacktrace;

/**
 * @internal
 */
final class ExitFailure
{
    private string $type;

    private string $message;

    private int|string $code;

    /** @var list<array<non-empty-string, list<scalar>|scalar>> */
    private array $trace;

    private ?string $tlTrace = null;

    private ?self $previous = null;

    private ?string $localized = null;

    public function __construct(Throwable $exception)
    {
        $this->type = $exception::class;
        $this->message = $exception->getMessage();
        $this->code = $exception->getCode();
        $this->trace = ContextFlattenThrowableBacktrace($exception);
        if (\method_exists($exception, 'getTLTrace')) {
            $this->tlTrace = $exception->getTLTrace();
        }

        if ($exception instanceof RPCErrorException) {
            $this->localized = $exception->getLocalization();
        }

        if ($previous = $exception->getPrevious()) {
            $this->previous = new self($previous);
        }
    }

    public function getException(): object
    {
        $previous = $this->previous ? $this->previous->getException() : null;

        try {
            if ($this->type === Exception::class) {
                $exception = new $this->type($this->message);
            } else {
                $exception = new $this->type($this->message, $this->code, $previous);
            }
        } catch (Throwable $e) {
            $exception = new RuntimeException($this->message, $this->code, $previous);
        }

        if ($this->tlTrace && \method_exists($exception, 'setTLTrace')) {
            $exception->setTLTrace($this->tlTrace);
        }
        if ($this->localized && \method_exists($exception, 'setLocalization')) {
            $exception->setLocalization($this->localized);
        }
        return $exception;
    }
}
