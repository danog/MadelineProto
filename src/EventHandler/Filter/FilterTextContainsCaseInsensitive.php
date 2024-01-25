<?php declare(strict_types=1);

/**
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @copyright 2016-2023 Amir Hossein Jafari <amirhosseinjafari8228@gmail.com>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\EventHandler\Filter;

use Attribute;
use danog\MadelineProto\EventHandler\Message;
use danog\MadelineProto\EventHandler\Story\Story;
use danog\MadelineProto\EventHandler\Update;
use Webmozart\Assert\Assert;

/**
 * Allow only messages that contain a specific case-insensitive content.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FilterTextContainsCaseInsensitive extends Filter
{
    private readonly string $content;

    public function __construct(
        string $content,
    ) {
        Assert::notEmpty($content);
        $this->content = mb_strtolower($content);
    }
    public function apply(Update $update): bool
    {
        return ($update instanceof Message && str_contains(mb_strtolower($update->message), $this->content)) ||
            ($update instanceof Story && str_contains(mb_strtolower($update->caption), $this->content));
    }
}
