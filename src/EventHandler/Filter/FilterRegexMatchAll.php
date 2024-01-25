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

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\InlineQuery;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Query\ButtonQuery;
use danog\MadelineProto\EventHandler\Story\Story;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only messages or button queries matching the specified regex.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterRegexMatchAll extends Filter
{
    /** @param non-empty-string $regex */
    public function __construct(
        private readonly string $regex,
        private readonly int $flags = 0,
        private readonly int $offset = 0
    ) {
        preg_match_all($regex, '', $m, $flags, $offset);
        Assert::eq(preg_last_error_msg(), 'No error');
    }

    public function apply(Update $update): bool
    {
        if ($update instanceof Message && preg_match_all($this->regex, $update->message, $matches, $this->flags, $this->offset)) {
            /** @psalm-suppress InaccessibleProperty */
            $update->matchesAll = $matches;
            return true;
        }

        if ($update instanceof ButtonQuery && preg_match_all($this->regex, $update->data, $matches, $this->flags, $this->offset)) {
            /** @psalm-suppress InaccessibleProperty */
            $update->matchesAll = $matches;
            return true;
        }

        if ($update instanceof InlineQuery && preg_match_all($this->regex, $update->query, $matches, $this->flags, $this->offset)) {
            /** @psalm-suppress InaccessibleProperty */
            $update->matchesAll = $matches;
            return true;
        }

        if ($update instanceof Story && preg_match_all($this->regex, $update->caption, $matches, $this->flags, $this->offset)) {
            /** @psalm-suppress InaccessibleProperty */
            $update->matchesAll = $matches;
            return true;
        }
        return false;
    }
}
