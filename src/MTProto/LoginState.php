<?php

declare(strict_types=1);

/**
 * MTProto Auth key.
 *
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

namespace danog\MadelineProto\MTProto;

use danog\MadelineProto\API;
use Webmozart\Assert\Assert;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class LoginState
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        /** @var API::NOT_LOGGED_IN|API::WAITING_*|API::LOGGED_IN|API::LOGGED_OUT */
        public readonly int $state,
        public readonly ?int $authorizedDc,
    ) {
        if ($state === API::LOGGED_IN) {
            Assert::notNull($authorizedDc, 'If state is LOGGED_IN, authorizedDc must not be null');
        }
    }

    /**
     * @param API::NOT_LOGGED_IN|API::WAITING_*|API::LOGGED_IN|API::LOGGED_OUT $state
     *
     * @psalm-mutation-free
     */
    public function setState(int $state): self
    {
        if ($state === $this->state) {
            return $this;
        }
        return new self($state, $state === API::LOGGED_OUT ? null : $this->authorizedDc);
    }
    /**
     * @param API::NOT_LOGGED_IN|API::WAITING_*|API::LOGGED_IN|API::LOGGED_OUT $state
     *
     * @psalm-mutation-free
     */
    public function setStateDc(int $state, ?int $dc): self
    {
        if ($state === $this->state && $dc === $this->authorizedDc) {
            return $this;
        }
        return new self($state, $dc);
    }
    /**
     * @psalm-mutation-free
     */
    public function setDc(int $dc): self
    {
        $dc = $this->state === API::LOGGED_OUT ? null : $dc;
        if ($dc === $this->authorizedDc) {
            return $this;
        }
        return new self($this->state, $dc);
    }
}
