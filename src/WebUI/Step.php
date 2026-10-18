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
 * The steps of the setup flow, shown in the progress rail of the builtin pages.
 */
enum Step: string
{
    /** Get the API ID and hash. */
    case APP = 'app';
    /** Choose the account or bot. */
    case ACCOUNT = 'account';
    /** Verify the login with a code, a password or a signup. */
    case VERIFY = 'verify';
    /** Logged in, running. */
    case DONE = 'done';

    /**
     * Get the localized label of the step.
     */
    public function getLabel(): string
    {
        return Template::translate(match ($this) {
            self::APP => 'webStepApp',
            self::ACCOUNT => 'webStepAccount',
            self::VERIFY => 'webStepVerify',
            self::DONE => 'webStepDone',
        });
    }
}
