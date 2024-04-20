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

namespace danog\MadelineProto;

use danog\MadelineProto\EventHandler\Message\Entities\MessageEntity;
use danog\TelegramEntities\Entities as TelegramEntitiesEntities;

/**
 * Class that converts HTML or markdown to a message + set of entities.
 */
final class TextEntities
{
    /**
     * Creates an Entities container using a message and a list of entities.
     */
    public function __construct(
        /** Converted message */
        public string $message,
        /**
         * Converted entities.
         *
         * @var list<MessageEntity>
         */
        public array $entities,
    ) {
    }
    /**
     * Manually convert markdown to a message and a set of entities.
     *
     * @return self Object containing message and entities
     */
    public static function fromMarkdown(string $markdown): self
    {
        $res = TelegramEntitiesEntities::fromMarkdown($markdown);
        return new self(
            $res->message,
            MessageEntity::fromRawEntities($res->entities),
        );
    }

    /**
     * Manually convert HTML to a message and a set of entities.
     *
     * @return self Object containing message and entities
     */
    public static function fromHtml(string $html): self
    {
        $res = TelegramEntitiesEntities::fromHtml($html);
        return new self(
            $res->message,
            MessageEntity::fromRawEntities($res->entities),
        );
    }
}
