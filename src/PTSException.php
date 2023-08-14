<?php

declare(strict_types=1);

/**
 * PTSException module.
 *
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

namespace danog\MadelineProto;

use Exception;

use const PHP_EOL;

/**
 * Internal error indicating a problem with Telegram's servers.
 */
final class PTSException extends Exception
{
    use TL\PrettyException;
    public function __toString(): string
    {
        return PTSException::class.($this->message !== '' ? ': ' : '').$this->message.PHP_EOL.'TL Trace:'.PHP_EOL.PHP_EOL.$this->getTLTrace().PHP_EOL;
    }
    public function __construct($message, $file = '')
    {
        parent::__construct($message);
        $this->prettifyTL($file);
    }
}
