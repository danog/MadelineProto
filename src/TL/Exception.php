<?php

declare(strict_types=1);

/**
 * Exception module.
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

namespace danog\MadelineProto\TL;

use danog\MadelineProto\Magic;

use const PHP_EOL;
use const PHP_SAPI;

/**
 * TL deserialization exception.
 */
final class Exception extends \Exception
{
    use PrettyException;
    public function __toString(): string
    {
        $result = static::class.($this->message !== '' ? ': ' : '').$this->message.PHP_EOL.Magic::$revision.PHP_EOL.'TL Trace:'.PHP_EOL.PHP_EOL.$this->getTLTrace().PHP_EOL;
        if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
            $result = str_replace(PHP_EOL, '<br>'.PHP_EOL, $result);
        }
        return $result;
    }
    public function __construct($message, $file = '')
    {
        parent::__construct($message);
        $this->prettifyTL($file);
    }
}
