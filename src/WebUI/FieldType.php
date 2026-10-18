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

namespace danog\MadelineProto\WebUI;

/**
 * Type of a form field.
 */
enum FieldType: string
{
    case TEXT = 'text';
    case PHONE = 'tel';
    case PASSWORD = 'password';
    /** One-time login code, hidden like a password. */
    case CODE = 'code';
    case URL = 'url';
    case TEXTAREA = 'textarea';
    case HIDDEN = 'hidden';
    /** A choice between a few options, rendered as a segmented control. */
    case CHOICE = 'choice';
}
