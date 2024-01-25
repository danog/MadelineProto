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

namespace danog\MadelineProto\EventHandler;

use danog\MadelineProto\EventHandler\Media\Document;
use danog\MadelineProto\EventHandler\Media\Photo;
use danog\MadelineProto\MTProto;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

/**
 * Represents information about a [named bot web app](https://core.telegram.org/api/bots/webapps#named-bot-web-apps).
 */
final class BotApp implements JsonSerializable
{
    /** App ID */
    public readonly int $id;

    /** Access hash*/
    public readonly int $accessHash;

    /** Bot web app short name, used to generate [named bot web app deep links](https://core.telegram.org/api/links#named-bot-web-app-links). */
    public readonly string $name;

    /** Bot web app title. */
    public readonly string $title;

    /** Bot web app description. */
    public readonly string $description;

    /** Bot web app photo. */
    public readonly ?Photo $photo;

    /** Bot web app animation. */
    public readonly ?Document $document;

    /** Hash to pass to [messages.getBotApp](https://docs.madelineproto.xyz/API_docs/methods/messages.getBotApp.html), to avoid refetching bot app info if it hasn’t changed. */
    public readonly int $hash;

    /** @internal */
    public function __construct(
        MTProto $API,
        array $rawBotApp,

        /** Whether the web app was never used by the user, and confirmation must be asked from the user before opening it. */
        public readonly ?bool $inactive = null,

        /** The bot is asking permission to send messages to the user: if the user agrees, set the write_allowed flag when invoking [messages.requestAppWebView](https://docs.madelineproto.xyz/API_docs/methods/messages.requestAppWebView.html). */
        public readonly ?bool $requestWriteAccess = null,
        public readonly ?bool $hasSettings = null,
    ) {
        $this->id = $rawBotApp['id'];
        $this->accessHash = $rawBotApp['access_hash'];
        $this->name = $rawBotApp['short_name'];
        $this->title = $rawBotApp['title'];
        $this->description = $rawBotApp['description'];
        $this->hash = $rawBotApp['hash'];
        $this->photo = isset($rawBotApp['photo']) ? $API->wrapMedia($rawBotApp['photo']) : null;
        $this->document = isset($rawBotApp['document']) ? $API->wrapMedia($rawBotApp['document']) : null;
    }

    /** @internal */
    public function jsonSerialize(): mixed
    {
        $res = ['_' => static::class];
        $refl = new ReflectionClass($this);
        foreach ($refl->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $res[$prop->getName()] = $prop->getValue($this);
        }
        return $res;
    }
}
