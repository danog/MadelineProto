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

namespace danog\MadelineProto\EventHandler\Message\Service;

use danog\MadelineProto\EventHandler\BotApp;
use danog\MadelineProto\EventHandler\Message\ServiceMessage;
use danog\MadelineProto\MTProto;

/**
 * We have given the bot permission to send us direct messages.
 * The optional fields specify how did we authorize the bot to send us messages.
 */
final class DialogBotAllowed extends ServiceMessage
{
    /** We have authorized the bot to send us messages by installing the bot’s [attachment menu](https://core.telegram.org/api/bots/attach). */
    public readonly bool $attachMenu;

    /** We have authorized the bot to send us messages by logging into a website via [Telegram Login »](https://core.telegram.org/widgets/login); this field contains the domain name of the website on which the user has logged in. */
    public readonly ?string $domain;

    /** We have authorized the bot to send us messages by opening the specified [bot web app](https://core.telegram.org/api/bots/webapps). */
    public readonly ?BotApp $app;

    /** @internal */
    public function __construct(MTProto $API, array $rawMessage, array $info)
    {
        parent::__construct($API, $rawMessage, $info);
        $this->attachMenu = $rawMessage['action']['attach_menu'];
        $this->domain = $rawMessage['action']['domain'] ?? null;
        $this->app = isset($rawMessage['action']['app']) ? new BotApp($API, $rawMessage['action']['app']) : null;
    }
}
