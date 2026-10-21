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

namespace danog\MadelineProto;

/**
 * How to parse the text of a [rich message](https://core.telegram.org/bots/api#sendrichmessage).
 *
 * Unlike ParseMode, MadelineProto doesn't parse Markdown and HTML: Telegram does, with the syntax of rich messages (headings, lists, tables, media embedded by ID with `tg://photo?id=` and `tg://document?id=`, and so on).
 */
enum RichParseMode: string
{
    /** Markdown, parsed by Telegram */
    case MARKDOWN = 'markdown';
    /** HTML, parsed by Telegram */
    case HTML = 'html';
    /** Plain text, sent as a single paragraph */
    case TEXT = 'text';
}
