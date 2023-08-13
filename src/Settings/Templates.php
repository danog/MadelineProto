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

namespace danog\MadelineProto\Settings;

use danog\MadelineProto\SettingsAbstract;

/**
 * Web and CLI template settings for login.
 */
final class Templates extends SettingsAbstract
{
    /**
     * Web template used for querying app information.
     */
    protected string $htmlTemplate = '<!DOCTYPE html><html><head><title>MadelineProto</title></head><body><h1>MadelineProto</h1><p>%s</p><form method="POST">%s<button type="submit"/>%s</button></form>%s</body></html>';

    /**
     * Get web template used for querying app information.
     */
    public function getHtmlTemplate(): string
    {
        return $this->htmlTemplate;
    }

    /**
     * Set web template used for querying app information.
     *
     * @param string $htmlTemplate Web template used for querying app information.
     */
    public function setHtmlTemplate(string $htmlTemplate): self
    {
        $this->htmlTemplate = $htmlTemplate;

        return $this;
    }
}
